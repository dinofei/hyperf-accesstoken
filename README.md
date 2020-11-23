
<pre>
如果遇到下载次数限制，添加如下token：
token: 2a952f1942a9b4e0c0e5a9342eca194bf6483a1a
</pre>

## 安装

`composer require bjyyb/hyperf-accesstoken:dev-main`

## 发布配置文件 

`php bin/hyperf.php vendor:publish bjyyb/hyperf-accesstoken`

## 使用：

### 生成 access_token和refresh_token 

```php
$tokenService = $this->container->get(\Bjyyb\AccessToken\Contract\TokenInterface::class);
$payload = [
    "user_id" => 1,
    "name" => "xx",
];
$result = $tokenService->iss($payload);
/** 返回如下格式数据：*/
[
    "access_token" => "xxx",
    "refresh_token" => "xxx",
];
```

### 验证 access_token

```php
$tokenService = $this->container->get(\Bjyyb\AccessToken\Contract\TokenInterface::class);
$token = "xx";
$payload = $tokenService->verify($token);
/** 返回如下格式数据：*/
[
    "user_id" => 1,
    "name" => "xx",
];
/** 如果验证失败抛出异常 */
```

### 使用refresh_token 刷新 access_token 

```php
$tokenService = $this->container->get(\Bjyyb\AccessToken\Contract\TokenInterface::class);
$refreshToken = "xx";
$result = $tokenService->refresh($refreshToken);
/** 返回新的令牌：*/
[
    "access_token" => "xxx",
    "refresh_token" => "xxx",
];
```
### 删除 access_token和refresh_token 

```php
$tokenService = $this->container->get(\Bjyyb\AccessToken\Contract\TokenInterface::class);
$token = "xx";
$result = $tokenService->remove($token);
```

----
## 适配apiservice的token验证 

### 安装jwt组件 

`composer require bjyyb/hyperf-jwt:dev-main`

### 发布jwt配置文件
`php bin/hyperf.php vendor:publish bjyyb/hyperf-jwt`

### 添加配置 config/jwt.php
```php
[
'webtoken' => [
    // 签名算法
    'alg' => 'HS256',
    // 实体内容
    'payload' => [
    ],
    // 加密密钥
    'key' => 'abcd1234abcd',
    // 允许算法
    'allowed_algs' => ['HS256'],
],
];
```

### 解密token 

```php
$token = 'xxx';
$tokenService = $this->container->get(\Bjyyb\AccessToken\WebToken::class);
$data = $tokenService->verify($token);
var_dump($data);
``` 
