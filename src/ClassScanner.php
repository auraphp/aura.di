<?php

declare(strict_types=1);

namespace Aura\Di;

use Aura\Di\Resolver\Reflector;
use Composer\ClassMapGenerator\ClassMapGenerator;

class ClassScanner implements ContainerConfigInterface
{
    /**
     *
     * The directories that will be scanned for classes and attributes.
     *
     * @var array
     *
     */
    private array $classMapDirectories;

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
     * A map containing attribute class name as key and how to configure the container with an
     * AttributeConfigInterface implementation.
     *
     * @var array<class-string, AttributeConfigInterface>
     *
     */
    private array $configuration;

    /**
     *
     * Regex that matches file paths to be excluded from the classmap
     *
     * @var ?string
     *
     */
    private ?string $excluded;

    /**
     *
     * A reflector instance to help reflect classes
     *
     * @var Reflector
     *
     */
    private Reflector $reflector;

    /**
     *
     * Constructor.
     *
     * @param array $injectNamespaces The namespaces that will be scanned for classes the
     * Resolver needs to create a Blueprint for.
     *
     * @param array $classMapDirectories The directories that will be scanned for classes and
     * attributes.
     *
     * @param array<class-string, AttributeConfigInterface> $annotationConfiguration A map containing attribute class
     * name as key and how to configure the container with an AttributeConfigInterface implementation.
     *
     * @param ?string $excluded Regex that matches file paths to be excluded from the classmap
     */
    public function __construct(
        array $classMapDirectories,
        array $injectNamespaces = [],
        array $annotationConfiguration = [],
        ?string $excluded = null
    ) {
        $this->classMapDirectories = $classMapDirectories;
        $this->configuration = $annotationConfiguration;
        $this->excluded = $excluded;
        $this->injectNamespaces = $injectNamespaces;
        $this->reflector = new Reflector();
    }

    public function define(Container $di): void
    {
        $inner = new ClassMapGenerator();
        $inner->avoidDuplicateScans();
        foreach ($this->classMapDirectories as $directory) {
            $inner->scanPaths($directory, $this->excluded);
        }

        $classmap = array_keys($inner->getClassMap()->getMap());

        foreach ($classmap as $className) {
            foreach ($this->injectNamespaces as $namespace) {
                if (\str_starts_with($className, $namespace)) {
                    $di->params[$className] = $di->params[$className] ?? [];
                }
            }

            $reflectionClass = $this->reflector->getClass($className);
            foreach ($reflectionClass->getAttributes(AttributeConfigInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $this->configureAttribute($di, $attribute, $reflectionClass);
            }

            $methods = $reflectionClass->getMethods();
            foreach ($methods as $method) {
                $parameters = $method->getParameters();
                foreach ($parameters as $parameter) {
                    foreach ($parameter->getAttributes(AttributeConfigInterface::class) as $attribute) {
                        $this->configureAttribute($di, $attribute, $method);
                    }
                }
            }

            $properties = $reflectionClass->getProperties();
            foreach ($properties as $property) {
                foreach ($property->getAttributes(AttributeConfigInterface::class) as $attribute) {
                    $this->configureAttribute($di, $attribute, $property);
                }
            }

            $constants = $reflectionClass->getConstants();
            /** @var \ReflectionClassConstant $constant */
            foreach ($constants as $constant) {
                foreach ($constant->getAttributes(AttributeConfigInterface::class) as $attribute) {
                    $this->configureAttribute($di, $attribute, $constant);
                }
            }
        }
    }

    public function configureAttribute(Container $di, \ReflectionAttribute $attribute, \Reflector $annotatedTo): void
    {
        if (\is_a($attribute->getName(), AttributeConfigInterface::class, true)) {
            /** @var AttributeConfigInterface $instance */
            $instance = $attribute->newInstance();
            $instance->define($di, $attribute, $annotatedTo);
            return;
        }

        if (\array_key_exists($attribute->getName(), $this->configuration)) {
            $this->configuration[$attribute->getName()]->define($di, $attribute, $annotatedTo);
        }
    }

    public function modify(Container $di): void
    {
    }
}