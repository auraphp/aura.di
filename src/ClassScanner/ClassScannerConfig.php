<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Attribute\DefineAttribute;
use Aura\Di\AttributeConfigInterface;
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
    private array $injectNamespaces;


    /**
     *
     * Constructor.
     *
     * @param MapGeneratorInterface $classMapGenerator TThe class that generates classes linked to files.
     *
     * @param array $injectNamespaces The namespaces that will be scanned for classes the
     * Resolver needs to create a Blueprint for during compilation.
     *
     */
    public function __construct(MapGeneratorInterface $classMapGenerator, array $injectNamespaces = [])
    {
        $this->mapGenerator = $classMapGenerator;
        $this->injectNamespaces = $injectNamespaces;
    }

    public function define(Container $di): void
    {
        $classMap = $this->mapGenerator->generate();
        foreach ($classMap->getClasses() as $className) {
            $annotatedAttributes = $classMap->getTargetedAttributesFor($className);
            foreach ($this->injectNamespaces as $namespace) {
                if (\str_starts_with($className, $namespace)) {
                    $di->params[$className] = $di->params[$className] ?? [];
                }
            }

            $configuration = [];
            foreach ($annotatedAttributes as $annotatedAttribute) {
                $attribute = $annotatedAttribute->getAttributeInstance();
                if ($attribute instanceof DefineAttribute) {
                    $attributeConfigClass = $annotatedAttribute->getClassName();
                    $configuration[$attribute->getClassName()] = new $attributeConfigClass();
                }
            }

            foreach ($annotatedAttributes as $annotatedAttribute) {
                $this->configureAttribute($di, $annotatedAttribute, $configuration);
            }
        }
    }

    private function configureAttribute(Container $di, TargetedAttribute $annotatedAttribute, array $configuration): void
    {
        $attribute = $annotatedAttribute->getAttributeInstance();
        if ($attribute instanceof AttributeConfigInterface) {
            $attribute->define(
                $di,
                $attribute,
                $annotatedAttribute->getClassName(),
                $annotatedAttribute->getAttributeTarget(),
                $annotatedAttribute->getTargetConfig()
            );
            return;
        }

        if (\array_key_exists($attribute::class, $configuration)) {
            $configuration[$attribute->getName()]->define(
                $di,
                $attribute,
                $annotatedAttribute->getClassName(),
                $annotatedAttribute->getAttributeTarget(),
                $annotatedAttribute->getTargetConfig()
            );
        }
    }

    public function modify(Container $di): void
    {
    }

    /**
     * @param array $classMapPaths
     *
     * @param array $injectNamespaces
     *
     * @param string|null $excluded
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
}