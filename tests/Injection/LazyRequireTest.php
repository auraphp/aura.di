<?php
namespace Aura\Di\Injection;

use Aura\Di\Resolver\Reflector;
use Aura\Di\Resolver\Resolver;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class LazyRequireTest extends TestCase
{
    public function test__invoke()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lazy_array.php';
        $lazyInclude = new LazyRequire($file);
        $actual = $lazyInclude->__invoke(new Resolver(new Reflector()));
        $expected = ['foo' => 'bar'];
        $this->assertSame($expected, $actual);
    }
}
