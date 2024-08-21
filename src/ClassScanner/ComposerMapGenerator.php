<?php
declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Resolver\Reflector;
use Composer\ClassMapGenerator\ClassMapGenerator;
use Composer\ClassMapGenerator\FileList;

final class ComposerMapGenerator implements MapGeneratorInterface
{
    private array $paths;
    private string $basePath;
    private ?string $excluded;
    private Reflector $reflector;

    public function __construct(
        array $paths,
        string $basePath = '',
        ?string $excluded = null,
    ) {
        $this->paths = $paths;
        $this->basePath = $basePath;
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

        return $this->convertToClassMap(
            new ClassMap(
                $this->paths,
                $this->basePath
            ),
            $generator->getClassMap()->getMap()
        );
    }

    private function convertToClassMap(ClassMap $classMap, array $composerMap): ClassMap
    {
        foreach ($composerMap as $class => $path) {
            if ($this->basePath !== '' && \str_starts_with($path, $this->basePath)) {
                $path = \substr($path, \strlen($this->basePath));
            }

            $classMap->addClass(
                new ClassSpecification(
                    $class,
                    $path,
                    [...$this->reflector->yieldAttributes($class)]
                )
            );
        }

        return $classMap;
    }

    public function update(ClassMap $classMap, array $updatedFiles): ClassMap
    {
        $shouldFullGenerate = false;
        foreach ($updatedFiles as $index => $updatedFile) {
            if ($this->basePath !== '' && \str_starts_with($updatedFile, $this->basePath)) {
                $updatedFiles[$index] = \substr($updatedFile, \strlen($this->basePath));
                $shouldFullGenerate = $shouldFullGenerate || $classMap->isAttributeClassFile($updatedFiles[$index]);
            }
        }

        if ($shouldFullGenerate) {
            return $this->generate();
        }

        $deleted = [];
        $skip = [];

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

        $generator = new ClassMapGenerator();
        $generator->avoidDuplicateScans($fileList);

        foreach ($this->paths as $path) {
            $generator->scanPaths($path, $this->excluded);
        }

        $classMap = $this->convertToClassMap(
            new ClassMap($this->paths, $this->basePath),
            $generator->getClassMap()->getMap()
        );

        foreach ($deleted as $filename) {
            $classMap->remove($filename);
        }

        return $classMap;
    }
}