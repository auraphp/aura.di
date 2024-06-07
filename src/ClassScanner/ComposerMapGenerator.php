<?php
declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Resolver\Reflector;
use Composer\ClassMapGenerator\ClassMapGenerator;

final class ComposerMapGenerator implements MapGeneratorInterface
{
    private array $paths;
    private ?string $excluded;
    private Reflector $reflector;

    public function __construct(
        array $paths,
        ?string $excluded,
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
        $composerMap = $generator->getClassMap()->getMap();

        $map = new ClassMap();
        foreach ($composerMap as $class => $path) {
            $map->addClass(
                $class,
                $path,
                [...$this->reflector->yieldAttributes($class)]
            );
        }

        return $map;
    }
}