<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Attribute\AttributeConfigFor;
use Aura\Di\Attribute\CompileNamespace;
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
     * Constructor.
     *
     * @param MapGeneratorInterface $classMapGenerator The class that generates classes linked to files.
     *
     */
    public function __construct(MapGeneratorInterface $classMapGenerator)
    {
        $this->mapGenerator = $classMapGenerator;
    }

    public function define(Container $di): void
    {
        $classMap = $this->mapGenerator->generate();

        $configuration = [];
        $compileNamespaces = [];
        foreach ($classMap->getAttributeSpecifications() as $specification) {
            $attribute = $specification->getAttributeInstance();
            $attributeConfigClass = $specification->getClassName();
            if ($attribute instanceof AttributeConfigFor && \is_a($attributeConfigClass, AttributeConfigInterface::class, true)) {
                $configFor = $attribute->getClassName();
                $configuration[$configFor] = $attributeConfigClass;
            }

            if ($attribute instanceof CompileNamespace) {
                $compileNamespaces[] = $attribute->getNamespace();
            }
        }

        foreach ($classMap->getClasses() as $className) {
            foreach ($compileNamespaces as $namespace) {
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
     * @param string $classMapFile Generated classmap file
     *
     * @return self
     */
    public static function newScanner(string $classMapFile): self
    {
        return new self(new StaticFileGenerator($classMapFile));
    }
}