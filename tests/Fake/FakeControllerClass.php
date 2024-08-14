<?php
namespace Aura\Di\Fake;

use Aura\Di\Attribute\Blueprint;

#[Blueprint]
class FakeControllerClass
{
    private $foo;

    public function __construct(FakeOtherClass $foo)
    {
        $this->foo = $foo;
    }

    public function process($param1)
    {
        return \pow($param1, 2);
    }
}
