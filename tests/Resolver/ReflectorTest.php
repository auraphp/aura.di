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

            if ($specification->isClassConstantAttribute()) {
                $this->assertTrue($specification->isClassConstantAttribute());
                $this->assertFalse($specification->isConstructorParameterAttribute());
                $this->assertFalse($specification->isMethodAttribute());
                $this->assertFalse($specification->isPropertyAttribute());
                $this->assertFalse($specification->isClassAttribute());
            }

            if ($specification->isClassAttribute()) {
                $this->assertTrue($specification->isClassAttribute());
                $this->assertFalse($specification->isClassConstantAttribute());
                $this->assertFalse($specification->isConstructorParameterAttribute());
                $this->assertFalse($specification->isMethodAttribute());
                $this->assertFalse($specification->isPropertyAttribute());
            }

            if ($specification->isPropertyAttribute()) {
                $this->assertTrue($specification->isPropertyAttribute());
                $this->assertFalse($specification->isClassAttribute());
                $this->assertFalse($specification->isClassConstantAttribute());
                $this->assertFalse($specification->isConstructorParameterAttribute());
                $this->assertFalse($specification->isMethodAttribute());
            }

            if ($specification->getTargetParameter() === 'methodParameter') {
                $this->assertSame('method', $specification->getTargetMethod());
                $this->assertFalse($specification->isConstructorParameterAttribute());
                $this->assertFalse($specification->isMethodAttribute());
                $this->assertFalse($specification->isPropertyAttribute());
                $this->assertFalse($specification->isClassConstantAttribute());
                $this->assertFalse($specification->isClassAttribute());
                $this->assertTrue($specification->isParameterAttribute());
            }

            if ($specification->getTargetParameter() === 'parameter') {
                $this->assertSame('__construct', $specification->getTargetMethod());
                $this->assertTrue($specification->isConstructorParameterAttribute());
                $this->assertFalse($specification->isMethodAttribute());
                $this->assertFalse($specification->isPropertyAttribute());
                $this->assertFalse($specification->isClassConstantAttribute());
                $this->assertFalse($specification->isClassAttribute());
                $this->assertTrue($specification->isParameterAttribute());
            }
        }
    }
}