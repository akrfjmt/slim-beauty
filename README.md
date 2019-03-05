# slim-beauty
dependency injector for slim3

## 設定

`dependency.php` を以下のように設定する

```php
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

型または引数名でControllerの引数を注入できる。  
他のコンポーネントに依存している場合、再帰的にコンポーネントをnewする。  
見えない場所で新しく作成されたインスタンスはクラス名をキーにしてコンテナに格納される。  

```php
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

### Callable

`[UserController::class, 'showUser']` のような形式でCallableを書ける。  
この形式はIntelliJで定義元に飛べるという利点がある。
