<?php

namespace Tests\Unit;

use Akrfjmt\SlimBeauty\ParameterResolver;
use PHPUnit\Framework\TestCase;
use Slim\Container;

class ParameterClass{}
class ParameterClass2{}
class ParameterClass3{}
class ParameterClass4{
    public $param1;
    public $param2;
    /**
     * ParameterClass4 constructor.
     * @param int $param1
     * @param int $param2
     */
    public function __construct($param1 = 1, $param2 = 2)
    {
        $this->param1 = $param1;
        $this->param2 = $param2;
    }
}

class ResolveTarget
{
    public $value;
    public function __construct()
    {
    }
    public function emptyParameterMethod()
    {
    }
    public function classParameterMethod(ParameterClass $pc1, ParameterClass2 $pc2, ParameterClass3 $pc3,
        ParameterClass4 $pc4)
    {
    }
    public function scalarParameterMethod(int $num, string $str, float $fl = 4.0)
    {
    }
}

class ParameterResolverTest extends TestCase
{
    public function testResolve()
    {
        // コンテナ用意
        $container = new Container();
        $container['num'] = function() {
            return 1;
        };
        $container['str'] = function() {
            return 'hanoi';
        };

        $parameterClass = new ParameterClass();
        $container[ParameterClass::class] = function() use ($parameterClass) {
            return $parameterClass;
        };

        $parameterClass2 = new ParameterClass2();
        $container['pc2'] = function() use ($parameterClass2) {
            return $parameterClass2;
        };

        $container['param1'] = function() {
            return 3;
        };

        $resolver = new ParameterResolver($container);
        $target = new ResolveTarget();

        // テスト開始
        // パラメータなしの場合
        $this->assertSame([], $resolver->resolveCallableParameter([$target, 'emptyParameterMethod']));

        // パラメータがスカラー型の場合
        // コンテナに入っているnumという名前の値、strという名前の値、そして関数のデフォルト値を取得する
        $this->assertSame([1, 'hanoi', 4.0], $resolver->resolveCallableParameter([$target, 'scalarParameterMethod']));

        // パラメータがクラス型の場合
        $params = $resolver->resolveCallableParameter([$target, 'classParameterMethod']);
        $this->assertSame($parameterClass, $params[0]); // コンテナに入っているParameterClassという型名のインスタンスを取得する
        $this->assertSame($parameterClass2, $params[1]); // コンテナに入っているpc2という名前のインスタンスを取得する
        $this->assertInstanceOf(ParameterClass3::class, $params[2]); // 引数なしで新規にインスタンスが作成される
        $this->assertInstanceOf(ParameterClass4::class, $params[3]); // 引数ありで新規にインスタンスが作成される
        /** @var ParameterClass4 $pc4 */
        $pc4 = $params[3];
        $this->assertSame(3, $pc4->param1); // コンテナに入っているpc2という名前の値が利用される
        $this->assertSame(2, $pc4->param2); // デフォルト値が利用される
    }
}
