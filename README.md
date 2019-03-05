# slim-beauty
dependency injector for slim3

## 設定

`dependency.php` を以下のように設定する

```php
<?php
use Akrfjmt\SlimBeauty\CustomCallableResolver;
use Akrfjmt\SlimBeauty\ParameterResolver;
use Akrfjmt\SlimBeauty\RequestResponseAutoParams;

$c = $app->getContainer();

// parameter resolver
$c[ParameterResolver::class] = function () use ($c) {
    return new ParameterResolver($c);
};

// customize foundHandler
$c['foundHandler'] = function() use ($c) {
    return new RequestResponseAutoParams($c->get(ParameterResolver::class), $c);
};

// customize callableResolver
$c['callableResolver'] = function() use ($c) {
    return new CustomCallableResolver($c, $c->get(ParameterResolver::class));
};
```

## Controllerに依存コンポーネントを注入する

型または引数名でコンテナからインスタンスを取り出してControllerの引数に注入される。  
コンテナにインスタンスが存在しない場合、コンポーネントをnewする。  
このとき、コンストラクタの型または引数名で再帰的に依存コンポーネントが注入される。  
新しく作成されたインスタンスはクラス名をキーにしてコンテナに格納される。  

```php
<?php
class UserController
{
    /** @var CustomPhpRenderer */
    public $renderer;

    public function __construct(CustomPhpRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function showUser(Request $request, Response $response, array $args, UserService $userService)
    {
        $user = $userService->getUser($args['id']);
        return $this->renderer->render($response, 'index.phtml', ['username' => $user->getName()]);
    }
}
```

## routes.phpでRoutesを定義する

```php
<?php
$app->get('/@{name}', [UserController::class, 'showUser'])->setName('show_user');
```

`[UserController::class, 'showUser']` のような形式でroutes定義を書ける。  
この形式はIntelliJで定義元に飛べるという利点がある。
