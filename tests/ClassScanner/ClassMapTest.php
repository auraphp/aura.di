<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Fake\FakeAllAttributes;
use Aura\Di\Fake\FakeWorkerAttribute;
use Aura\Di\Resolver\Reflector;
use PHPUnit\Framework\TestCase;

final class ClassMapTest extends TestCase
{
    public function testAddRemove(): void
    {
        $reflector = new Reflector();
        $attributes = [...$reflector->yieldAttributes(FakeAllAttributes::class)];
        $spec = new ClassSpecification(FakeAllAttributes::class, FakeAllAttributes::FILE, $attributes);

        $map = new ClassMap([], '/');
        $map->addClass($spec);

        $this->assertContains(FakeAllAttributes::class, $map->getClasses());
        $this->assertContains(FakeAllAttributes::FILE, $map->getFiles());
        $this->assertNotNull($map->getClassSpecificationFor(FakeAllAttributes::class));
        $this->assertSame($spec, $map->getClassSpecificationFor(FakeAllAttributes::class));
        $this->assertCount(\count($attributes), $map->getAttributeSpecifications());
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

        $map = new ClassMap([], '/');
        $map->addClass($spec1);
        $map->addClass($spec2);

        $this->assertFalse($map->isAttributeClassFile(FakeAllAttributes::FILE));
        $this->assertTrue($map->isAttributeClassFile(FakeWorkerAttribute::FILE));
    }

    public function testReconstituteFromFileHandle(): void
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

        $stream = fopen('php://temp', 'w');

        $map1 = new ClassMap([], '/');
        $map1->addClass($spec1);
        $map1->addClass($spec2);
        $map1->saveToFileHandle($stream);

        $map2 = ClassMap::fromFileHandle($stream);

        $this->assertContains(FakeAllAttributes::class, $map2->getClasses());
        $this->assertContains(FakeAllAttributes::FILE, $map2->getFiles());
        $this->assertContains(FakeWorkerAttribute::class, $map2->getClasses());
        $this->assertContains(FakeWorkerAttribute::FILE, $map2->getFiles());
        $this->assertCount(\count($spec1->getAttributes()), $map2->getClassSpecificationFor(FakeAllAttributes::class)->getAttributes());
        $this->assertCount(\count($spec2->getAttributes()), $map2->getClassSpecificationFor(FakeWorkerAttribute::class)->getAttributes());
    }
}