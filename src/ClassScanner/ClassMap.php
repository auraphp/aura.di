<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class ClassMap
{
    private array $filesToClass = [];
    private array $classesToAttributes = [];

    public function addClass(string $class, string $filename, array $attributeSpecifications): void
    {
        $this->filesToClass[$filename] = $class;
        $this->classesToAttributes[$class] = $attributeSpecifications;
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

    public function getFiles(): array
    {
        return \array_keys($this->filesToClass);
    }

    /**
     * @return array<int, AttributeSpecification>
     */
    public function getAttributeSpecificationsFor(string $className): array
    {
        return $this->classesToAttributes[$className] ?? [];
    }

    /**
     * @return array<int, AttributeSpecification>
     */
    public function getAttributeSpecifications(): array
    {
        return \array_merge([], ...array_values($this->classesToAttributes));
    }

    public function merge(ClassMap $other): ClassMap
    {
        $classMap = clone $this;
        $classMap->filesToClass = \array_merge($classMap->filesToClass, $other->filesToClass);
        $classMap->classesToAttributes = \array_merge($classMap->classesToAttributes, $other->classesToAttributes);
        return $classMap;
    }

    public function saveToFileHandle($fileHandle): void
    {
        $classMapJson = [
            'files' => [],
            'attributes' => [],
        ];
        foreach ($this->filesToClass as $filename => $className) {
            $classMapJson['files'][$filename] = $className;

            if ($attributeSpecifications = $this->getAttributeSpecificationsFor($className)) {
                $classMapJson['attributes'][$className] = \array_map(
                    fn (AttributeSpecification $attribute) => \serialize($attribute),
                    $attributeSpecifications
                );
            }
        }
        \ftruncate($fileHandle, 0);
        \fwrite($fileHandle, \json_encode($classMapJson, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));
    }

    /**
     * @throws \JsonException
     */
    public static function fromFile(string $filename): self
    {
        return self::fromFileHandle(\fopen($filename, 'r'));
    }

    /**
     * @param resource $fileHandle
     * @return ClassMap
     * @throws \JsonException
     */
    public static function fromFileHandle($fileHandle): ClassMap
    {
        $cacheContents = \stream_get_contents($fileHandle);
        $cacheContentsJson = \json_decode($cacheContents, true, 512, \JSON_THROW_ON_ERROR);

        $classMap = new ClassMap();

        foreach ($cacheContentsJson['files'] as $filename => $className) {
            $classMap->addClass(
                $className,
                $filename,
                \array_map(
                    fn (string $serializedAttributeSpecification) => \unserialize($serializedAttributeSpecification),
                    $cacheContentsJson['attributes'][$className] ?? []
                )
            );
        }

        return $classMap;
    }
}
