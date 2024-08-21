<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Fake\FakeAllAttributes;
use Aura\Di\Fake\FakeWorkerAttribute;
use Aura\Di\Resolver\Reflector;
use PHPUnit\Framework\TestCase;

final class ClassSpecificationTest extends TestCase
{
    public function testClassInfo(): void
    {
        $reflector = new Reflector();
        $attributes = [...$reflector->yieldAttributes(FakeAllAttributes::class)];
        $spec = new ClassSpecification(FakeAllAttributes::class, FakeAllAttributes::FILE, $attributes);

        $this->assertSame(FakeAllAttributes::class, $spec->getClassName());
        $this->assertSame(FakeAllAttributes::FILE, $spec->getFilename());
        $this->assertCount(\count($attributes), $spec->getAttributes());
        $this->assertCount(1, $spec->getClassAttributes());
        $this->assertCount(1, $spec->getParameterAttributesForMethod('method'));
        $this->assertArrayHasKey('methodParameter', $spec->getParameterAttributesForMethod('method'));
    }

    public function testAttributeClass(): void
    {
        $reflector = new Reflector();

        $spec1 = new ClassSpecification(
            FakeAllAttributes::class,
            FakeAllAttributes::FILE,
            [...$reflector->yieldAttributes(FakeAllAttributes::class)]
        );

        $spec2 = new ClassSpecification(
            FakeWorkerAttribute::class,
            FakeWorkerAttribute::FILE,
            [...$reflector->yieldAttributes(FakeWorkerAttribute::class)]
        );

        $this->assertFalse($spec1->isAttributeClass());
        $this->assertTrue($spec2->isAttributeClass());
    }
}