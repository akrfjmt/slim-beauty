<?php
namespace Akrfjmt\SlimBeauty;

use RuntimeException;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\CallableResolverInterface;

/**
 * Class CustomCallableResolver
 * @package Akrfjmt\SlimBeauty
 *
 * [__CLASS__, __METHOD__] 形式のCallbleに対してもControllerを実体化する
 * パラメータの解決も行う
 */
final class CustomCallableResolver implements CallableResolverInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var ParameterResolver */
    private $parameterResolver;

    /**
     * @param ContainerInterface $container
     * @param ParameterResolver $parameterResolver
     */
    public function __construct(ContainerInterface $container, ParameterResolver $parameterResolver)
    {
        $this->container = $container;
        $this->parameterResolver = $parameterResolver;
    }

    /**
     * Resolve toResolve into a closure so that the router can dispatch.
     *
     * If toResolve is of the format 'class:method', then try to extract 'class'
     * from the container otherwise instantiate it and then dispatch 'method'.
     *
     * @param mixed $toResolve
     *
     * @return callable
     *
     * @throws RuntimeException if the callable does not exist
     * @throws RuntimeException if the callable is not resolvable
     */
    public function resolve($toResolve)
    {
        if (is_callable($toResolve))
        {
            if  (is_array($toResolve) && is_string($toResolve[0])) {
                $resolved = $this->resolveCallable($toResolve[0], $toResolve[1]);
                $this->assertCallable($resolved);
                return $resolved;
            }

            return $toResolve;
        }

        $resolved = $this->resolveCallable($toResolve);
        $this->assertCallable($resolved);

        return $resolved;
    }

    /**
     * Check if string is something in the DIC
     * that's callable or is a class name which has an __invoke() method.
     *
     * @param string $class
     * @param string $method
     * @return callable
     *
     * @throws \RuntimeException if the callable does not exist
     */
    protected function resolveCallable($class, $method = '__invoke')
    {
        if ($this->container->has($class)) {
            return [$this->container->get($class), $method];
        }

        if (!class_exists($class)) {
            throw new RuntimeException(sprintf('Callable %s does not exist', $class));
        }

        $parameters = $this->parameterResolver->resolveConstructorParameter($class);
        if ($parameters === null) {
            return [new $class(), $method];
        } else {
            return [new $class(...$parameters), $method];
        }
    }

    /**
     * @param Callable $callable
     *
     * @throws \RuntimeException if the callable is not resolvable
     */
    protected function assertCallable($callable)
    {
        if (!is_callable($callable)) {
            throw new RuntimeException(sprintf(
                '%s is not resolvable',
                is_array($callable) || is_object($callable) ? json_encode($callable) : $callable
            ));
        }
    }
}
