<?php
declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Resolver\Reflector;
use Composer\ClassMapGenerator\ClassMapGenerator;
use Composer\ClassMapGenerator\FileList;

final class ComposerMapGenerator implements MapGeneratorInterface
{
    private array $paths;
    private ?string $excluded;
    private Reflector $reflector;

    public function __construct(
        array $paths,
        ?string $excluded = null,
    ) {
        $this->paths = $paths;
        $this->excluded = $excluded;
        $this->reflector = new Reflector();
    }

    public function generate(): ClassMap
    {
        $generator = new ClassMapGenerator();
        $generator->avoidDuplicateScans();

        foreach ($this->paths as $path) {
            $generator->scanPaths($path, $this->excluded);
        }

        return $this->convertToClassMap(new ClassMap(), $generator->getClassMap()->getMap());
    }

    private function convertToClassMap(ClassMap $classMap, array $composerMap): ClassMap
    {
        foreach ($composerMap as $class => $path) {
            $classMap->addClass(
                $class,
                $path,
                [...$this->reflector->yieldAttributes($class)]
            );
        }

        return $classMap;
    }

    public function update(ClassMap $classMap, array $updatedFiles): ClassMap
    {
        $deleted = [];
        $skip = [];

        $generator = new ClassMapGenerator();
        foreach ($classMap->getFiles() as $file) {
            if (!\in_array($file, $updatedFiles, true)) {
                $skip[$file] = true;
            }
        }

        foreach ($updatedFiles as $file) {
            if (!\is_file($file)) {
                $deleted[] = $file;
            }
        }

        $fileList = new FileList();
        $fileList->files = $skip;
        $generator->avoidDuplicateScans($fileList);

        foreach ($this->paths as $path) {
            $generator->scanPaths($path, $this->excluded);
        }

        $classMap = $this->convertToClassMap(new ClassMap(), $generator->getClassMap()->getMap());
        foreach ($deleted as $filename) {
            $classMap->remove($filename);
        }

        return $classMap;
    }
}