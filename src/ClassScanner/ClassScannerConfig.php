<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Attribute\AttributeConfigFor;
use Aura\Di\Container;
use Aura\Di\ContainerConfigInterface;

class ClassScannerConfig implements ContainerConfigInterface
{
    /**
     *
     * The class that generates classes linked to files.
     *
     * @var MapGeneratorInterface
     *
     */
    private MapGeneratorInterface $mapGenerator;

    /**
     *
     * The namespaces that will be scanned for classes the Resolver needs to create a Blueprint for.
     *
     * @var array
     *
     */
    private array $compileNamespaces;

    /**
     *
     * Constructor.
     *
     * @param MapGeneratorInterface $classMapGenerator The class that generates classes linked to files.
     *
     * @param array $compileNamespaces The namespaces that will be scanned for classes the
     * Resolver needs to create a Blueprint for during compilation.
     *
     */
    public function __construct(MapGeneratorInterface $classMapGenerator, array $compileNamespaces = [])
    {
        $this->mapGenerator = $classMapGenerator;
        $this->compileNamespaces = $compileNamespaces;
    }

    public function define(Container $di): void
    {
        $classMap = $this->mapGenerator->generate();

        $configuration = [];
        foreach ($classMap->getAttributeSpecifications() as $specification) {
            $attribute = $specification->getAttributeInstance();
            $attributeConfigClass = $specification->getClassName();
            if ($attribute instanceof AttributeConfigFor && \is_a($attributeConfigClass, AttributeConfigInterface::class, true)) {
                $configFor = $attribute->getClassName();
                $configuration[$configFor] = $attributeConfigClass;
            }
        }

        foreach ($classMap->getClasses() as $className) {
            foreach ($this->compileNamespaces as $namespace) {
                if (\str_starts_with($className, $namespace)) {
                    $di->params[$className] = $di->params[$className] ?? [];
                }
            }

            foreach ($classMap->getAttributeSpecificationsFor($className) as $specification) {
                $attribute = $specification->getAttributeInstance();
                if (\array_key_exists($attribute::class, $configuration)) {
                    $configuredBy = $configuration[$attribute::class];
                    $configuredBy::define(
                        $di,
                        $specification,
                    );
                }
            }
        }
    }

    public function modify(Container $di): void
    {
    }

    /**
     * @param array $classMapPaths Paths to scan for classes and attributes.
     *
     * @param array $injectNamespaces Namespaces to create blueprints for.
     *
     * @param string|null $excluded Regex for file exclusions.
     *
     * @return self
     */
    public static function newScanner(
        array $classMapPaths,
        array $injectNamespaces = [],
        ?string $excluded = null
    ): self {
        return new self(
            new ComposerMapGenerator($classMapPaths, $excluded),
            $injectNamespaces,
        );
    }

    /**
     * @param string $cacheFile File that keeps the file modification times of all classes.
     *
     * @param array $classMapPaths Namespaces to create blueprints for.
     *
     * @param array $injectNamespaces Regex for file exclusions.
     *
     * @param string|null $excluded
     *
     * @return self
     */
    public static function newCachedScanner(
        string $cacheFile,
        array $classMapPaths,
        array $injectNamespaces = [],
        ?string $excluded = null
    ): self {
        return new self(
            new CachedFileModificationGenerator(
                new ComposerMapGenerator($classMapPaths, $excluded),
                $cacheFile
            ),
            $injectNamespaces,
        );
    }
}