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

        $this->assertCount(8, $attributes);
        foreach ($attributes as $specification) {
            $instance = $specification->getAttributeInstance();
            $this->assertSame($specification->getAttributeTarget(), $instance->getValue() & $specification->getAttributeTarget());

            if ($specification->getTargetParameter() === 'methodParameters') {
                $this->assertSame('method', $specification->getTargetMethod());
            }

            if ($specification->getTargetParameter() === 'parameter') {
                $this->assertSame('__construct', $specification->getTargetMethod());
            }
        }
    }
}