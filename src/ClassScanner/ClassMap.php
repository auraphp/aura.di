<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class ClassMap
{
    private array $classes = [];

    public function addClass(string $class, string $filename, array $annotatedAttributes): void
    {
        $this->classes[$filename][$class] = $annotatedAttributes;
    }

    /**
     * @return array<class-string, array<int, AnnotatedAttribute>>
     */
    public function getClassMap(): array
    {
        return \array_merge([], ...\array_values($this->classes));
    }
}
