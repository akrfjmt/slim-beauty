<?php

namespace Akrfjmt\SlimBeauty;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

/**
 * コンテナ経由でパラメータを勝手に解決してくれるやつ
 */
final class RequestResponseAutoParams implements InvocationStrategyInterface
{
    /** @var ParameterResolver */
    public $parameterResolver;

    /** @var ContainerInterface */
    public $container;

    public function __construct(ParameterResolver $parameterResolver, ContainerInterface $container)
    {
        $this->parameterResolver = $parameterResolver;
        $this->container = $container;
    }

    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    )
    {
        foreach ($routeArguments as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }

        if (!is_array($callable) || !is_object($callable[0])) {
            return call_user_func($callable, $request, $response, $routeArguments);
        }

        if (!$this->container->has('args')) {
            $this->container['args'] = $routeArguments;
        }

        $params = $this->parameterResolver->resolveCallableParameter($callable);
        return call_user_func($callable, ...$params);
    }
}
