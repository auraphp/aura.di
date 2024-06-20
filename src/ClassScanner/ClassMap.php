<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class ClassMap
{
    private array $filesToClass = [];
    private array $classesToAttributes = [];

    public function addClass(string $class, string $filename, array $annotatedAttributes): void
    {
        $this->filesToClass[$filename] = $class;
        $this->classesToAttributes[$class] = $annotatedAttributes;
    }

    public function remove(string $filename): void
    {
        if (\array_key_exists($filename, $this->filesToClass)) {
            $className = $this->filesToClass[$filename];
            unset($this->filesToClass[$filename]);
            unset($this->classesToAttributes[$className]);
        }
    }

    /**
     * @return array<int, class-string>
     */
    public function getClasses(): array
    {
        return \array_keys($this->classesToAttributes);
    }

    public function getFileToClassMap(): array
    {
        return $this->filesToClass;
    }

    /**
     * @return array<int, TargetedAttribute>
     */
    public function getTargetedAttributesFor(string $className): array
    {
        return $this->classesToAttributes[$className] ?? [];
    }

    public function merge(ClassMap $other): ClassMap
    {
        $classMap = clone $this;
        $classMap->filesToClass = \array_merge($classMap->filesToClass, $other->filesToClass);
        $classMap->classesToAttributes = \array_merge($classMap->classesToAttributes, $other->classesToAttributes);
        return $classMap;
    }
}
