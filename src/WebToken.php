<?php
declare(strict_types=1);

namespace Bjyyb\AccessToken;

use Bjyyb\JWT\JwtService;
use Hyperf\Utils\ApplicationContext;

/**
 * Note: WebToken (适配apiservice的验证)
 * Author: nf
 * Time: 2020/11/23 10:47
 */
class WebToken implements Contract\TokenInterface
{

    /**
     * @inheritDoc
     */
    public function iss(array $payload): array
    {
        // TODO: Implement iss() method.
    }

    /**
     * @inheritDoc
     */
    public function verify(string $token): array
    {
        // 解jwt
        $container = ApplicationContext::getContainer();
        $payload = $container->get(JwtService::class)->parse($token, 'webtoken');
        return (array) $payload->data;
    }

    /**
     * @inheritDoc
     */
    public function refresh(string $refreshToken): array
    {
        // TODO: Implement refresh() method.
    }

    /**
     * @inheritDoc
     */
    public function remove(string $token): bool
    {
        // TODO: Implement remove() method.
    }
}