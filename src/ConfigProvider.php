<?php


namespace Bjyyb\AccessToken;



use Bjyyb\AccessToken\Contract\TokenInterface;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => [
                TokenInterface::class => Token::class,
            ],
            'publish' => [
                [
                    'id' => 'access_token',
                    'description' => 'token参数配置',
                    'source' => __DIR__ . '/../publish/access_token.php',
                    'destination' => BASE_PATH . '/config/autoload/access_token.php',
                ],
            ],
        ];
    }


}