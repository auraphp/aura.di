<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class CachedFileModificationGenerator implements MapGeneratorInterface
{
    private MapGeneratorInterface $delegatedGenerator;
    private string $cacheFilename;

    public function __construct(
        MapGeneratorInterface $delegatedGenerator,
        string $cacheFilename,
    ) {
        $this->delegatedGenerator = $delegatedGenerator;
        $this->cacheFilename = $cacheFilename;
    }

    public function generate(): ClassMap
    {
        $cacheFileHandle = \fopen($this->cacheFilename, 'a+');
        if (\fstat($cacheFileHandle)['size'] > 0) {
            $cacheContents = \stream_get_contents($cacheFileHandle);
            $cacheContentsJson = \json_decode($cacheContents, true, 512, \JSON_THROW_ON_ERROR);
            $classMap = $this->readClassMapFromCacheJson($cacheContentsJson);
        } else {
            \flock($cacheFileHandle, LOCK_EX);
            $classMap = $this->delegatedGenerator->generate();
            $this->writeClassMapToFileHandle($cacheFileHandle, $classMap);
        }

        \fclose($cacheFileHandle);
        return $classMap;
    }

    public function update(ClassMap $classMap, array $updatedFiles): ClassMap
    {
        $cacheFileHandle = \fopen($this->cacheFilename, 'a+');
        \flock($cacheFileHandle, LOCK_EX);

        $classMap = $this->delegatedGenerator->update($classMap, $updatedFiles);
        $this->writeClassMapToFileHandle($cacheFileHandle, $classMap);
        \fclose($cacheFileHandle);
        return $classMap;
    }

    /**
     * @throws \JsonException
     */
    private function readClassMapFromCacheJson(array $cacheContentsJson): ClassMap
    {
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

    private function writeClassMapToFileHandle($fileHandle, ClassMap $classMap): void
    {
        $classMapJson = [
            'files' => [],
            'attributes' => [],
        ];
        foreach ($classMap->getFileToClassMap() as $filename => $className) {
            $classMapJson['files'][$filename] = $className;

            if ($attributeSpecifications = $classMap->getAttributeSpecificationsFor($className)) {
                $classMapJson['attributes'][$className] = \array_map(
                    fn (AttributeSpecification $attribute) => \serialize($attribute),
                    $attributeSpecifications
                );
            }
        }
        \ftruncate($fileHandle, 0);
        \fwrite($fileHandle, \json_encode($classMapJson, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));
    }
}
