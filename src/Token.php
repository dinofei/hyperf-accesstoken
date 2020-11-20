<?php
declare(strict_types=1);

namespace Bjyyb\AccessToken;

use Bjyyb\AccessToken\Contract\TokenInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Hyperf\Utils\Codec\Json;
use Psr\Container\ContainerInterface;
use Redis;

/**
 * Note: Token
 * Author: nf
 * Time: 2020/11/16 23:05
 */
class Token implements TokenInterface
{
    /**
     * @var array
     */
    protected $config = [
        'access_token_save_key' => 'hyperf-access_token:',
        'access_token_payload_save_key' => 'access_token_payload:',
        'refresh_token_save_key' => 'hyperf-refresh_token:',
        'refresh_token_payload_save_key' => 'refresh_token_payload:',
        'access_token_max_length' => 10000,
        'access_token_expire_at' => 3600,
        'refresh_token_expire_at' => 3600 * 24 * 30,
        'redis_pool' => 'default',
    ];

    /**
     * @var RedisProxy|Redis
     */
    protected $redis;
    /**
     * @var AesUtil
     */
    protected $aes;

    public function __construct(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);
        $redisFactory = $container->get(RedisFactory::class);
        $this->aes = $container->get(AesUtil::class);
        $this->config = array_merge($this->config, $config->get('access_token', []));
        $this->redis = $redisFactory->get($this->config['redis_pool'] ?? 'default');
    }

    /**
     * 发放令牌和刷新令牌
     * @param array $payload
     * @return string[]
     * Author: nf
     * Time: 2020/11/17 10:40
     */
    public function iss(array $payload): array
    {
        $this->checkMaxLimitLength();

        return $this->create($payload);
    }

    /**
     * 验证令牌 并读取存储的用户信息
     * @param string $token
     * @return array
     * Author: nf
     * Time: 2020/11/17 11:02
     */
    public function verify(string $token): array
    {
        $score = $this->getAccessTokenScore($token);

        if (!$score) {
            throw new \RuntimeException('令牌不存在', 1001);
        }

        if (!$this->checkAccessTokenExpireAt($score)) {
            /** 移除过期令牌 */
            $handle = $this->redis->multi(Redis::PIPELINE);
            $this->removeAccessToken($handle, $token);
            $handle->exec();

            throw new \RuntimeException('令牌已失效', 1001);
        }

        $payload = $this->getAccessTokenPayload($token);

        if (!$payload) {
            throw new \RuntimeException('读取用户信息失败', 1001);
        }

        /** 更新令牌有效时间 */
        $this->updateTokenScore($token);

        return $payload;

    }

    /**
     * 刷新令牌
     * @param string $refreshToken
     * @return string[]
     * Author: nf
     * Time: 2020/11/17 11:33
     */
    public function refresh(string $refreshToken): array
    {
        $score = $this->getRefreshTokenScore($refreshToken);

        if (!$score) {
            throw new \RuntimeException('刷新令牌不存在', 1000);
        }

        if (!$this->checkRefreshTokenExpireAt($score)) {
            /** 移除过期令牌 */
            $handle = $this->redis->multi(Redis::PIPELINE);
            $this->removeRefreshToken($handle, $refreshToken);
            $this->removeAccessToken($handle, $this->getAccessTokenByRefreshToken($refreshToken));
            $handle->exec();

            throw new \RuntimeException('刷新令牌已失效', 1000);
        }

        $payload = $this->getRefreshTokenPayload($refreshToken);

        if (!$payload) {
            throw new \RuntimeException('读取用户信息失败', 1000);
        }

        /** 删除旧令牌 并重新颁发 */
        $handle = $this->redis->multi(Redis::PIPELINE);
        $this->removeRefreshToken($handle, $refreshToken);
        $this->removeAccessToken($handle, $this->getAccessTokenByRefreshToken($refreshToken));
        $handle->exec();

        return $this->create($payload);
    }

    /**
     * 删除令牌
     * @param string $token
     * @return bool
     * Author: nf
     * Time: 2020/11/17 12:59
     */
    public function remove(string $token): bool
    {
        $handle = $this->redis->multi(Redis::PIPELINE);
        $this->removeAccessToken($handle, $token);
        $this->removeRefreshToken($handle, $this->getRefreshTokenByAccessToken($token));
        $handle->exec();

        return true;
    }

    /**
     * 保存用户信息
     * @param array $payload
     * @return string[]
     * Author: nf
     * Time: 2020/11/17 10:41
     */
    protected function create(array $payload): array
    {
        $time = microtime(true);

        [$token, $refreshToken] = $this->createToken($payload);

        $handle = $this->redis->multi(Redis::PIPELINE);
        $handle->zAdd($this->config['access_token_save_key'], $time, $token);
        $handle->hSet($this->config['access_token_payload_save_key'], $token, Json::encode($payload));
        $handle->zAdd($this->config['refresh_token_save_key'], $time, $refreshToken);
        $handle->hSet($this->config['refresh_token_payload_save_key'], $refreshToken, Json::encode($payload));
        $handle->exec();

        return ['access_token' => $token, 'refresh_token' => $refreshToken];
    }

    /**
     * 生成令牌
     * @param array $payload
     * @return array
     * Author: nf
     * Time: 2020/11/17 10:41
     */
    protected function createToken(array $payload): array
    {
        $userKey = implode(':', array_values($payload));
        $accessToken = md5($userKey . (string) microtime(true) . rand(1000, 9999));
        $refreshToken = $this->getRefreshTokenByAccessToken($accessToken);
        return [$accessToken, $refreshToken];
    }

    /**
     * 获取access_token的分数
     * @param string $token
     * @return float
     * Author: nf
     * Time: 2020/11/17 11:23
     */
    protected function getAccessTokenScore(string $token)
    {
        return $this->redis->zScore($this->config['access_token_save_key'], $token);
    }

    /**
     * 获取refresh_token的分数
     * @param string $token
     * @return float
     * Author: nf
     * Time: 2020/11/17 11:23
     */
    protected function getRefreshTokenScore(string $token)
    {
        return $this->redis->zScore($this->config['refresh_token_save_key'], $token);
    }

    /**
     * 读取access_token保存的用户信息
     * @param string $token
     * @return mixed|string
     * Author: nf
     * Time: 2020/11/17 11:14
     */
    protected function getAccessTokenPayload(string $token)
    {
        $payload = $this->redis->hGet($this->config['access_token_payload_save_key'], $token);
        if ($payload) {
            $payload = Json::decode($payload);
        }
        return $payload;
    }

    /**
     * 读取refresh_token保存的用户信息
     * @param string $token
     * @return mixed|string
     * Author: nf
     * Time: 2020/11/17 11:14
     */
    protected function getRefreshTokenPayload(string $token)
    {
        $payload = $this->redis->hGet($this->config['refresh_token_payload_save_key'], $token);
        if ($payload) {
            $payload = Json::decode($payload);
        }
        return $payload;
    }

    /**
     * refresh_token转为access_token
     * @param string $token
     * @return string
     * Author: nf
     * Time: 2020/11/17 11:42
     */
    protected function getAccessTokenByRefreshToken(string $token): string
    {
        return $this->aes->decrypt($token);
    }

    /**
     * access_token转为refresh_token
     * @param string $token
     * @return string
     * Author: nf
     * Time: 2020/11/17 11:43
     */
    protected function getRefreshTokenByAccessToken(string $token): string
    {
        return $this->aes->encrypt($token);
    }

    /**
     * 更新token有效期时间
     * @param string $token
     * Author: nf
     * Time: 2020/11/20 9:49
     */
    protected function updateTokenScore(string $token): void
    {
        $time = microtime(true);
        $this->redis->zAdd($this->config['access_token_save_key'], $time, $token);
        $this->redis->zAdd($this->config['refresh_token_save_key'], $time, $this->getRefreshTokenByAccessToken($token));
    }

    /**
     * 删除access_token
     * @param Redis|null $redis
     * @param mixed ...$token
     * Author: nf
     * Time: 2020/11/17 11:20
     */
    protected function removeAccessToken(?Redis $redis = null, ...$token): void
    {
        $handle = $redis ?? $this->redis;
        $handle->zRem($this->config['access_token_save_key'], ...$token);
        $handle->hDel($this->config['access_token_payload_save_key'], ...$token);
    }

    /**
     * 删除refresh_token
     * @param Redis|null $redis
     * @param mixed ...$token
     * Author: nf
     * Time: 2020/11/17 11:20
     */
    protected function removeRefreshToken(?Redis $redis = null, ...$token): void
    {
        $handle = $redis ?? $this->redis;
        $handle->zRem($this->config['refresh_token_save_key'], ...$token);
        $handle->hDel($this->config['refresh_token_payload_save_key'], ...$token);
    }

    /**
     * 检查令牌容量 移除过期令牌
     * @return bool
     * Author: nf
     * Time: 2020/11/17 10:28
     */
    protected function checkMaxLimitLength(): bool
    {
        if ($this->redis->exists($this->config['access_token_save_key'])) {
            if (!$this->isAccessTokenCapNormal()) {
                /** 令牌签发数量大于最大值 移除最近7天没有使用的用户令牌 */
                /** 移除最近30天没有使用的刷新令牌 */

                $endTime = strtotime('-7 day');

                /** 获取过期令牌 */
                $items1 = $this->redis->zRangeByScore($this->config['access_token_save_key'], '0', (string) $endTime);
                /** 获取过期刷新令牌 */
                $items2 = $this->redis->zRangeByScore($this->config['refresh_token_save_key'], '0', (string) $endTime);

                $handle = $this->redis->multi(Redis::PIPELINE);
                /** 移除令牌 */
                $this->removeAccessToken($handle, ...$items1);
                /** 移除刷新令牌 */
                $this->removeRefreshToken($handle, ...$items2);
                $handle->exec();

                /** 再次判断 如果依然不够 则报上报警告 */
                if (!$this->isAccessTokenCapNormal()) {
                    throw new \RuntimeException('用户令牌容量达到上限，无法继续发放令牌');
                }
            }
        }

        return true;
    }

    /**
     * 令牌容量是否达到上限
     * @return bool
     * Author: nf
     * Time: 2020/11/17 10:25
     */
    protected function isAccessTokenCapNormal(): bool
    {
        return $this->redis->zCard($this->config['access_token_save_key']) < $this->config['access_token_max_length'];
    }

    /**
     * 检查access_token是否过期
     * @param float $score
     * @param int|null $now
     * @return bool
     * Author: nf
     * Time: 2020/11/17 11:10
     */
    protected function checkAccessTokenExpireAt(float $score, ?int $now = null)
    {
        $time = $now ?? time();
        return ($time - $score) <= $this->config['access_token_expire_at'];
    }

    /**
     * 检查refresh_token是否过期
     * @param float $score
     * @param int|null $now
     * @return bool
     * Author: nf
     * Time: 2020/11/17 11:11
     */
    protected function checkRefreshTokenExpireAt(float $score, ?int $now = null)
    {
        $time = $now ?? time();
        return ($time - $score) <= $this->config['refresh_token_expire_at'];
    }

}