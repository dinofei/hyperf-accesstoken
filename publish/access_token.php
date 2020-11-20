<?php

return [
    // 保存令牌池键名
    'access_token_save_key' => 'hyperf-access_token:',
    // 保存令牌用户信息键名
    'access_token_payload_save_key' => 'access_token_payload:',
    // 保存刷新令牌池键名
    'refresh_token_save_key' => 'hyperf-refresh_token:',
    // 保存刷新令牌用户信息键名
    'refresh_token_payload_save_key' => 'refresh_token_payload:',
    // 令牌最大容量
    'access_token_max_length' => 90000,
    // 令牌有效时长
    'access_token_expire_at' => 3600 * 24 * 7,
    // 刷新令牌有效时长
    'refresh_token_expire_at' => 3600 * 24 * 30,
    // redis连接池
    'redis_pool' => 'default',
];