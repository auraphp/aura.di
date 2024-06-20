<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class ClassMap
{
    private array $files = [];
    private array $classes = [];

    public function addClass(string $class, string $filename, array $annotatedAttributes): void
    {
        $this->files[$filename] = $class;
        $this->classes[$class] = $annotatedAttributes;
    }

    /**
     * @return array<int, class-string>
     */
    public function getClasses(): array
    {
        return \array_keys($this->classes);
    }

    /**
     * @return array<int, TargetedAttribute>
     */
    public function getTargetedAttributesFor(string $className): array
    {
        return $this->classes[$className] ?? [];
    }
}
