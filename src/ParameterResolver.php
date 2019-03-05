<?php

namespace Akrfjmt\SlimBeauty;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class ParameterResolver
{
    /** @var ContainerInterface */
    private $container;

    /**
     * ParameterResolver constructor.
     * @param ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * メソッドの引数をコンテナ等から取得する。
     * @param callable $toResolve
     * @param int $offset 先頭のパラメータをいくつ無視するのか
     * @return array|null
     */
    public function resolveCallableParameter($toResolve)
    {
        if (!is_callable($toResolve)) {
            return null;
        }
        if (!is_array($toResolve) || !is_object($toResolve[0])) {
            return null;
        }
        /** @var ReflectionParameter $parameters */
        try {
            $reflMethod = new ReflectionMethod($toResolve[0], $toResolve[1]);
        } catch (\ReflectionException $e) {
            return null;
        }

        return $this->resolveReflMethodParameter($reflMethod);
    }

    /**
     * コンストラクタの引数をコンテナ等から取得する。
     * [$className, '__construct'] はcallble扱いされないので専用メソッドを用意
     * @param string|object $className
     * @return array|null
     */
    public function resolveConstructorParameter($className) {
        /** @var ReflectionParameter $parameters */
        try {
            $reflClass = new ReflectionClass($className);
        } catch (\ReflectionException $e) {
            return null;
        }

        $ctor = $reflClass->getConstructor();
        return $this->resolveReflMethodParameter($ctor);
    }

    /**
     * メソッドの引数をコンテナ等から取得する。
     * @param ReflectionMethod $reflMethod
     * @return array
     */
    public function resolveReflMethodParameter($reflMethod)
    {
        $reflParameters = $reflMethod->getParameters();
        $params = [];

        foreach ($reflParameters as $reflParameter) {
            $reflClass = $reflParameter->getClass();
            // クラス以外の場合
            if ($reflClass === null) {
                if ($this->container->has($reflParameter->getName())) {
                    $params[] = $this->container->get($reflParameter->getName());
                    continue;
                }

                if ($reflParameter->isOptional()) {
                    try {
                        $params[] = $reflParameter->getDefaultValue();
                        continue;
                    } catch (\ReflectionException $e) {
                        // never comes here.
                    }
                }

                $params[] = null;
                continue;
            }

            // クラス名でコンテナから値を取得
            if ($this->container->has($reflClass->getName())) {
                $instance = $this->container->get($reflClass->getName());
                if ($reflClass->isInstance($instance)) {
                    $params[] = $instance;
                    continue;
                }
            }

            // パラメータ名でコンテナから値を取得
            if ($this->container->has($reflParameter->getName())) {
                $instance = $this->container->get($reflParameter->getName());
                if ($reflClass->isInstance($instance)) {
                    $params[] = $instance;
                    continue;
                }
            }

            // nullデフォルト値はサポートしない

            // コンテナに値がなかった場合、新しくインスタンスを作成する。
            $ctor = $reflClass->getConstructor();
            if ($ctor === null) {
                $instance = $reflClass->newInstance();
            } else {
                $ctorParams = $this->resolveReflMethodParameter($ctor);
                $instance = $reflClass->newInstanceArgs($ctorParams);
            }
            $this->container[$reflClass->getName()] = $instance;
            $params[] = $instance;
        }

        return $params;
    }
}