<?php
declare(strict_types=1);

namespace Bjyyb\AccessToken\Contract;

/**
 * Note: TokenInterface
 * Author: nf
 * Time: 2020/11/16 22:58
 */
interface TokenInterface
{
    /**
     * 颁发token令牌
     * @param array $payload 实体内容
     * @return array
     * Author: nf
     * Time: 2020/11/16 23:01
     */
    public function iss(array $payload): array;

    /**
     * 验证令牌有效性
     * @param string $token
     * @return array
     * Author: nf
     * Time: 2020/11/16 23:02
     */
    public function verify(string $token): array;

    /**
     * 刷新令牌
     * @param string $refreshToken
     * @return array
     * Author: nf
     * Time: 2020/11/16 23:03
     */
    public function refresh(string $refreshToken): array;

    /**
     * 移除令牌
     * @param string $token
     * @return bool
     * Author: nf
     * Time: 2020/11/16 23:04
     */
    public function remove(string $token): bool;
}