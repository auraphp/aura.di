<?php
namespace Aura\Di\Fake;

class FakeControllerClass
{
    private $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }

    public function process($param1)
    {
        return \pow($param1, 2);
    }
}
