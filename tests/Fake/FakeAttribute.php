<?php
namespace Aura\Di\Fake;

#[\Attribute(\Attribute::TARGET_ALL)]
class FakeAttribute
{
    private int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}