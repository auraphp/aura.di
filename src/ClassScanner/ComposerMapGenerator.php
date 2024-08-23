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
        $deleted = [];
        $skip = [];

        $shouldFullGenerate = false;
        foreach ($updatedFiles as $index => $updatedFile) {
            if ($this->basePath !== '' && \str_starts_with($updatedFile, $this->basePath)) {
                $checkFile = \substr($updatedFile, \strlen($this->basePath));

                if (!\is_file($updatedFile)) {
                    $deleted[] = $checkFile;
                } else {
                    $shouldFullGenerate = $shouldFullGenerate || $classMap->isAttributeClassFile($checkFile);
                }

                $updatedFiles[$index] = $checkFile;
            }
        }

        if ($shouldFullGenerate) {
            return $this->generate();
        }

        foreach ($classMap->getFiles() as $file) {
            if (!\in_array($file, $updatedFiles, true)) {
                $skip[$this->basePath . $file] = true;
            }
        }

        $fileList = new FileList();
        $fileList->files = $skip;

        $generator = new ClassMapGenerator();
        $generator->avoidDuplicateScans($fileList);

        foreach ($this->paths as $path) {
            $generator->scanPaths($path, $this->excluded);
        }

        $classMap = $classMap->merge(
            $this->convertToClassMap(
                new ClassMap(
                    $this->paths,
                    $this->basePath
                ),
                $generator->getClassMap()->getMap()
            )
        );

        foreach ($deleted as $filename) {
            $classMap->remove($filename);
        }

        return $classMap;
    }
}
