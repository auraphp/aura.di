<?php
namespace Aura\Di\Fake;

#[FakeAttribute(1)]
class FakeAllAttributes
{
    #[FakeAttribute(2)]
    public int $property;

    #[FakeAttribute(3)]
    public const CONSTANT = 1;

    public function __construct(
        #[FakeAttribute(4)]
        $parameter
    ) {
    }

    #[FakeAttribute(5)]
    public function method()
    {
    }
}