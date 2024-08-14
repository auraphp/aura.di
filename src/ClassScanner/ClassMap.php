<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class ClassMap
{
    private string $basePath;
    /** @var array<int, string> */
    private array $scanPaths;
    /** @var array<string, class-string> */
    private array $filesToClass = [];
    /** @var array<class-string, array<int, AttributeSpecification>> */
    private array $classesToAttributes = [];

    /**
     * @param array<int, string> $scanPaths
     */
    public function __construct(array $scanPaths, string $basePath)
    {
        $this->scanPaths = $scanPaths;
        $this->basePath = $basePath;
    }

    /**
     * @param array<int, AttributeSpecification> $attributeSpecifications
     */
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

    public function isAttributeClassFile(string $filename): bool
    {
        if (!\array_key_exists($filename, $this->filesToClass)) {
            return false;
        }

        $class = $this->filesToClass[$filename];
        if (!\array_key_exists($class, $this->classesToAttributes)) {
            return false;
        }

        $attributeSpecifications = $this->classesToAttributes[$class];
        foreach ($attributeSpecifications as $specification) {
            if ($specification->getAttributeInstance() instanceof \Attribute) {
                return true;
            }
        }

        return false;
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
            'scanPaths' => $this->scanPaths,
            'basePath' => $this->basePath,
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

        $classMap = new ClassMap($cacheContentsJson['scanPaths'], $cacheContentsJson['basePath']);

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
