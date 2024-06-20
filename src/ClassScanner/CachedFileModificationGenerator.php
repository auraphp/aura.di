<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class CachedFileModificationGenerator implements MapGeneratorInterface
{
    private MapGeneratorInterface $delegatedGenerator;
    private string $cacheFilename;
    private int $debounceMs;

    public function __construct(
        MapGeneratorInterface $delegatedGenerator,
        string $cacheFilename,
        int $debounceMs = 500
    ) {
        $this->delegatedGenerator = $delegatedGenerator;
        $this->cacheFilename = $cacheFilename;
        $this->debounceMs = $debounceMs;
    }

    public function generate(?array $skipFiles = null): ClassMap
    {
        $cacheFileHandle = \fopen($this->cacheFilename, 'a+');
        if (\fstat($cacheFileHandle)['size'] > 0) {
            if (\filemtime($this->cacheFilename) + $this->debounceMs <= \time()) {
                \flock($cacheFileHandle, LOCK_EX);
                $cacheContents = \stream_get_contents($cacheFileHandle);
                $cacheContentsJson = \json_decode($cacheContents, true, 512, \JSON_THROW_ON_ERROR);
                $classMap = $this->readClassMapFromCacheJson($cacheContentsJson);

                if ($skipFiles === null) {
                    $skipFiles = [];
                }

                $deleted = [];
                foreach ($cacheContentsJson['filetimes'] as $filename => $cacheModTime) {
                    if (\is_file($filename) === false) {
                        $deleted[] = $filename;
                    } elseif (\filemtime($filename) === $cacheModTime) {
                        $skipFiles[] = $filename;
                    }
                }

                $classMap = $classMap->merge($this->delegatedGenerator->generate($skipFiles));
                foreach ($deleted as $filename) {
                    $classMap->remove($filename);
                }

                $this->writeClassMapToFileHandle($cacheFileHandle, $classMap);
            } else {
                \flock($cacheFileHandle, \LOCK_SH);
                $cacheContents = \stream_get_contents($cacheFileHandle);
                $cacheContentsJson = \json_decode($cacheContents, true, 512, \JSON_THROW_ON_ERROR);
                $classMap = $this->readClassMapFromCacheJson($cacheContentsJson);
            }
        } else {
            \flock($cacheFileHandle, LOCK_EX);
            $classMap = $this->delegatedGenerator->generate();
            $this->writeClassMapToFileHandle($cacheFileHandle, $classMap);
        }

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
                    fn (string $serializedTargetedAttribute) => \unserialize($serializedTargetedAttribute),
                    $cacheContentsJson['attributes'][$className]
                )
            );
        }

        return $classMap;
    }

    private function writeClassMapToFileHandle($fileHandle, ClassMap $classMap): void
    {
        $classMapJson = [
            'files' => [],
            'filetimes' => [],
            'attributes' => [],
        ];
        foreach ($classMap->getFileToClassMap() as $filename => $className) {
            $classMapJson['files'][$filename] = $className;
            $classMapJson['filetimes'][$filename] = \filemtime($filename);
            $classMapJson['attributes'][$className] = \array_map(
                fn (TargetedAttribute $attribute) => \serialize($attribute),
                $classMap->getTargetedAttributesFor($className)
            );
        }
        \ftruncate($fileHandle, 0);
        \fwrite($fileHandle, \json_encode($classMapJson, \JSON_THROW_ON_ERROR));
    }
}