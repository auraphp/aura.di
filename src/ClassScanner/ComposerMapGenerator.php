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

    public function generate(?array $skipFiles = null): ClassMap
    {
        $generator = new ClassMapGenerator();
        if ($skipFiles === null) {
            $generator->avoidDuplicateScans();
        } else {
            $fileList = new FileList();
            $fileList->files = $skipFiles;
            $generator->avoidDuplicateScans($fileList);
        }

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
}