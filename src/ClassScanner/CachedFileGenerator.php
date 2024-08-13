<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class CachedFileGenerator implements MapGeneratorInterface
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
            $classMap = ClassMap::fromFileHandle($cacheFileHandle);
        } else {
            \flock($cacheFileHandle, LOCK_EX);
            $classMap = $this->delegatedGenerator->generate();
            $classMap->saveToFileHandle($cacheFileHandle);
        }

        \fclose($cacheFileHandle);
        return $classMap;
    }

    public function update(ClassMap $classMap, array $updatedFiles): ClassMap
    {
        $cacheFileHandle = \fopen($this->cacheFilename, 'a+');
        \flock($cacheFileHandle, LOCK_EX);

        $classMap = $this->delegatedGenerator->update($classMap, $updatedFiles);
        $classMap->saveToFileHandle($cacheFileHandle);
        \fclose($cacheFileHandle);
        return $classMap;
    }
}
