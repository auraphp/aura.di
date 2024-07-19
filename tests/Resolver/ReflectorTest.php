<?php
namespace Aura\Di\Resolver;

use Aura\Di\ClassScanner\AttributeSpecification;
use Aura\Di\Fake\FakeAllAttributes;
use PHPUnit\Framework\TestCase;

class ReflectorTest extends TestCase
{
    public function testYieldAttributes()
    {
        $reflector = new Reflector();

        /** @var array<int, AttributeSpecification> $attributes */
        $attributes = [...$reflector->yieldAttributes(FakeAllAttributes::class)];

        $this->assertCount(5, $attributes);
        foreach ($attributes as $attribute) {
            $instance = $attribute->getAttributeInstance();
            match ($attribute->getAttributeTarget()) {
                 \Attribute::TARGET_CLASS => $this->assertSame(1, $instance->getValue()),
                 \Attribute::TARGET_PROPERTY => $this->assertSame(2, $instance->getValue()),
                 \Attribute::TARGET_CLASS_CONSTANT => $this->assertSame(3, $instance->getValue()),
                 \Attribute::TARGET_PARAMETER => $this->assertSame(4, $instance->getValue()),
                 \Attribute::TARGET_METHOD => $this->assertSame(5, $instance->getValue()),
            };
        }
    }
}