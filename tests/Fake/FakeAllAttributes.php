<?php
namespace Aura\Di\Fake;

#[FakeAttribute(\Attribute::TARGET_CLASS)]
class FakeAllAttributes
{
    #[FakeAttribute(\Attribute::TARGET_PROPERTY)]
    public int $property;

    #[FakeAttribute(\Attribute::TARGET_CLASS_CONSTANT)]
    public const CONSTANT = 1;

    public const FILE = __FILE__;

    public function __construct(
        #[FakeAttribute(\Attribute::TARGET_PARAMETER)]
        $parameter,
        #[FakeAttribute(\Attribute::TARGET_PARAMETER + \Attribute::TARGET_PROPERTY)]
        public $promotedProperty
    ) {
    }

    #[FakeAttribute(\Attribute::TARGET_METHOD)]
    public function method(
        #[FakeAttribute(\Attribute::TARGET_PARAMETER)]
        $methodParameter
    )
    {
    }
}