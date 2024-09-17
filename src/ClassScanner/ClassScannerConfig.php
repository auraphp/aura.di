<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use Aura\Di\Attribute\AnnotatedInjectInterface;
use Aura\Di\Attribute\AttributeConfigFor;
use Aura\Di\Attribute\Blueprint;
use Aura\Di\Attribute\BlueprintNamespace;
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

        $attributeConfigs = [
            AnnotatedInjectInterface::class => AnnotatedInjectAttributeConfig::class,
            Blueprint::class => BlueprintAttributeConfig::class,
        ];

        $blueprintNamespaces = [];
        foreach ($classMap->getAttributeSpecifications() as $specification) {
            $attribute = $specification->getAttributeInstance();
            $attributeConfigClass = $specification->getClassName();
            if ($attribute instanceof AttributeConfigFor && \is_a($attributeConfigClass, AttributeConfigInterface::class, true)) {
                $configFor = $attribute->getClassName();
                $attributeConfigs[$configFor] = $attributeConfigClass;
            }

            if ($attribute instanceof BlueprintNamespace) {
                $blueprintNamespaces[] = $attribute->getNamespace();
            }
        }

        foreach ($this->newClassIterator($classMap) as $className) {
            foreach ($blueprintNamespaces as $namespace) {
                if (\str_starts_with($className, $namespace)) {
                    $di->params[$className] = $di->params[$className] ?? [];
                }
            }

            $classSpecification = $classMap->getClassSpecificationFor($className);
            foreach ($classSpecification->getAttributes() as $specification) {
                $attribute = $specification->getAttributeInstance();
                if (\array_key_exists($attribute::class, $attributeConfigs)) {
                    $configuredBy = $attributeConfigs[$attribute::class];
                    $configuredBy::define(
                        $di,
                        $specification,
                        $classSpecification
                    );
                }

                $interfaces = \class_implements($attribute);
                foreach ($interfaces as $interface) {
                    if (\array_key_exists($interface, $attributeConfigs)) {
                        $configuredBy = $attributeConfigs[$interface];
                        $configuredBy::define(
                            $di,
                            $specification,
                            $classSpecification
                        );
                    }
                }
            }
        }
    }

    /**
     * @return \Iterator<int, class-string>
     */
    protected function newClassIterator(ClassMap $classMap): \Iterator
    {
        return new \ArrayIterator($classMap->getClasses());
    }

    public function modify(Container $di): void
    {
    }

    /**
     * @param string $classMapFile Generated classmap file
     *
     * @return self
     */
    public static function fromCacheFile(string $classMapFile): self
    {
        return new self(new StaticFileGenerator($classMapFile));
    }
}