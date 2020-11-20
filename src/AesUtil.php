<?php
declare(strict_types=1);

namespace Bjyyb\AccessToken;

use RuntimeException;


/**
 * Note: Aes加解密工具
 * Author: nf
 * Time: 2020/11/20 10:08
 */
class AesUtil
{

    protected $method = 'AES-128-CBC';

    protected $key = 'bjyyb-hyperf-aes';

    protected $iv = '1234567890000000';

    protected $options = 0;

    public function encrypt(string $data): string
    {
        $secureStr = openssl_encrypt($data, $this->method, $this->key, $this->options, $this->iv);
        if ($secureStr === false) {
            throw new RuntimeException('aes加密失败');
        }
        return base64_encode($secureStr);
    }

    public function decrypt(string $data): string
    {
        $plainStr = openssl_decrypt(base64_decode($data), $this->method, $this->key, $this->options, $this->iv);
        if ($plainStr === false) {
            throw new RuntimeException('aes解密失败');
        }
        return $plainStr;
    }

}