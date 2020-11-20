## composer添加版本仓库
```
"bjyyb-hyperf-accesstoken": {
    "type": "path",
    "url": "../hyperf-accesstoken"
}
```
## 安装

`composer require bjyyb/hyperf-accesstoken`

## 发布配置文件 

`php bin/hyperf vendor:publish bjyyb/hyperf-accesstoken`

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
