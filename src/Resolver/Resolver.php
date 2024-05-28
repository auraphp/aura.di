<?php
declare(strict_types=1);
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Di\Resolver;

use Aura\Di\Attribute\AnnotatedInjectInterface;
use Aura\Di\Exception;
use Aura\Di\Injection\LazyInterface;
use ReflectionParameter;
use function class_exists;
use function get_parent_class;

/**
 *
 * Resolves class creation specifics based on constructor params and setter
 * definitions, unified across class defaults, inheritance hierarchies, and
 * configuration.
 *
 * @package Aura.Di
 *
 * @property array $params
 *
 * @property array $setters
 *
 * @property array $mutations
 *
 * @property array $types
 *
 * @property array $values
 *
 */
class Resolver
{
    /**
     *
     * A Reflector.
     *
     * @var Reflector
     *
     */
    protected Reflector $reflector;

    /**
     *
     * Retains named service definitions.
     *
     * @var array
     *
     */
    protected array $services = [];

    /**
     *
     * Constructor params in the form `$params[$class][$name] = $value`.
     *
     * @var array
     *
     */
    protected array $params = [];

    /**
     *
     * Setter definitions in the form of `$setters[$class][$method] = $value`.
     *
     * @var array
     *
     */
    protected array $setters = [];

    /**
     *
     * Setter definitions in the form of `$mutations[$class][] = $value`.
     *
     * @var array
     *
     */
    protected array $mutations = [];

    /**
     *
     * Arbitrary values in the form of `$values[$key] = $value`.
     *
     * @var array
     *
     */
    protected array $values = [];

    /**
     *
     * Constructor params and setter definitions, unified across class
     * defaults, inheritance hierarchies, and configuration.
     *
     * @var array|Blueprint[]
     *
     */
    protected array $unified = [];

    /**
     *
     * Retains the actual service object instances.
     *
     * @var array
     *
     */
    protected array $instances = [];

    /**
     *
     * Constructor.
     *
     * @param Reflector $reflector A collection point for Reflection data.
     *
     */
    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    /**
     *
     * Returns a reference to various property arrays.
     *
     * @param string $key The property name to return.
     *
     * @return array
     *
     * @throws Exception\NoSuchProperty
     *
     */
    public function &__get($key): array
    {
        if (isset($this->$key)) {
            return $this->$key;
        }
        throw Exception::noSuchProperty($key);
    }

    /**
     *
     * Sets a service definition by name.
     *
     * @param string $service The service key.
     *
     * @param object $val The service object; or callable.
     *
     */
    public function setService(string $service, object|callable $val): void
    {
        $this->services[$service] = $val;
    }

    /**
     *
     * Does a particular service definition exist?
     *
     * @param string $service The service key to look up.
     *
     * @return bool
     *
     */
    public function hasService(string $service): bool
    {
        return isset($this->services[$service]);
    }

    /**
     *
     * Instantiates a service object by key, lazy-loading it as needed.
     *
     * @param string $id The service to get.
     *
     * @return object
     *
     * @throws Exception\ServiceNotFound when the requested service
     * does not exist.
     *
     */
    public function getServiceInstance(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (! isset($this->services[$id])) {
            throw Exception::serviceNotFound($id);
        }

        // instantiate it from its definition
        $instance = $this->services[$id];

        // lazy-load as needed
        if ($instance instanceof LazyInterface) {
            $instance = $instance($this);
        }

        // done
        $this->instances[$id] = $instance;
        return $this->instances[$id];
    }

    /**
     *
     * Gets the list of instantiated services.
     *
     * @return array
     *
     */
    public function getInstances(): array
    {
        return array_keys($this->instances);
    }

    /**
     *
     * Gets the list of service definitions.
     *
     * @return array
     *
     */
    public function getServices(): array
    {
        return array_keys($this->services);
    }

    /**
     *
     * Creates and returns a new instance of a class using reflection and
     * the configuration parameters, optionally with overrides, invoking Lazy
     * values along the way.
     *
     * @param Blueprint $blueprint The blueprint to be resolved containing
     * its overrides for this specific case.
     *
     * @param array $contextualBlueprints
     *
     * @return object
     */
    public function resolve(Blueprint $blueprint, array $contextualBlueprints = []): object
    {
        if ($contextualBlueprints === []) {
            return call_user_func(
                $this->getUnified($blueprint->getClassName())->merge($blueprint),
                $this,
            );
        }

        $remember = new self($this->reflector);

        foreach ($contextualBlueprints as $contextualBlueprint) {
            $className = $contextualBlueprint->getClassName();

            $remember->params[$className] = $this->params[$className] ?? [];
            $remember->setters[$className] = $this->setters[$className] ?? [];
            $remember->mutations[$className] = $this->mutations[$className] ?? [];

            $this->params[$className] = \array_merge(
                $this->params[$className] ?? [],
                $contextualBlueprint->getParams()
            );

            $this->setters[$className] = \array_merge(
                $this->setters[$className] ?? [],
                $contextualBlueprint->getSetters()
            );

            $this->setters[$className] = \array_merge(
                $this->setters[$className] ?? [],
                $contextualBlueprint->getMutations()
            );

            unset($this->unified[$className]);
        }

        $resolved = call_user_func(
            $this->getUnified($blueprint->getClassName())->merge($blueprint),
            $this,
        );

        foreach ($contextualBlueprints as $contextualBlueprint) {
            $className = $contextualBlueprint->getClassName();
            $this->params[$className] = $remember->params[$className] ?? [];
            $this->setters[$className] = $remember->setters[$className] ?? [];
            $this->mutations[$className] = $remember->mutations[$className] ?? [];

            if (isset($remember->unified[$className])) {
                $this->unified[$className] = $remember->unified[$className];
            } else {
                unset($this->unified[$className]);
            }
        }

        return $resolved;
    }

    public function compile(): void
    {
        $classes = \array_unique([
            ...\array_keys($this->params),
            ...\array_keys($this->setters),
            ...\array_keys($this->mutations),
        ]);

        foreach ($classes as $class) {
            $this->getUnified($class);
        }

        $this->params = [];
        $this->setters = [];
        $this->mutations = [];
    }

    /**
     *
     * Returns the unified constructor params and setters for a class.
     *
     * @param string $class The class name to return values for.
     *
     * @return Blueprint A blueprint how to construct an object
     *
     */
    public function getUnified(string $class): Blueprint
    {
        // have values already been unified for this class?
        if (isset($this->unified[$class])) {
            return $this->unified[$class];
        }

        // fetch the values for parents so we can inherit them
        $parent = class_exists($class) ? get_parent_class($class) : null;
        if ($parent) {
            $spec = $this->getUnified($parent);
        } else {
            $spec = new Blueprint($class);
        }

        $unified = new Blueprint(
            $class,
            $this->getUnifiedParams($class, $spec->getParams()),
            $this->getUnifiedSetters($class, $spec->getSetters()),
            $this->getUnifiedMutations($class, $spec->getMutations()),
        );

        // stores and returns the unified params and setters
        return $this->unified[$class] = $unified->withParamSettings($this->getParamSettings($class));
    }

    /**
     *
     * Returns the unified constructor params for a class.
     *
     * @param string $class The class name to return values for.
     *
     * @param array $parent The parent unified params.
     *
     * @return array The unified params.
     *
     */
    protected function getUnifiedParams(string $class, array $parent): array
    {
        // reflect on what params to pass, in which order
        $unified = [];
        $rparams = $this->reflector->getParams($class);
        foreach ($rparams as $rparam) {
            $unified[$rparam->name] = $this->getUnifiedParam(
                $rparam,
                $class,
                $parent
            );
        }

        return $unified;
    }

    /**
     *
     * Returns a unified param.
     *
     * @param ReflectionParameter $rparam A parameter reflection.
     *
     * @param string $class The class name to return values for.
     *
     * @param array $parent The parent unified params.
     *
     * @return mixed The unified param value.
     *
     */
    protected function getUnifiedParam(ReflectionParameter $rparam, string $class, array $parent)
    {
        $name = $rparam->getName();
        $pos = $rparam->getPosition();

        // is there a positional value explicitly from the current class?
        $explicitPos = isset($this->params[$class])
            && array_key_exists($pos, $this->params[$class])
            && ! $this->params[$class][$pos] instanceof UnresolvedParam;
        if ($explicitPos) {
            return $this->params[$class][$pos];
        }

        // is there a named value explicitly from the current class?
        $explicitNamed = isset($this->params[$class])
            && array_key_exists($name, $this->params[$class])
            && ! $this->params[$class][$name] instanceof UnresolvedParam;
        if ($explicitNamed) {
            return $this->params[$class][$name];
        }

        foreach ($rparam->getAttributes(AnnotatedInjectInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            /** @var AnnotatedInjectInterface $attributeInstance */
            $attributeInstance = $attribute->newInstance();
            return $attributeInstance->inject();
        }

        // is there a named value implicitly inherited from the parent class?
        // (there cannot be a positional parent. this is because the unified
        // values are stored by name, not position.)
        $implicitNamed = array_key_exists($name, $parent)
            && ! $parent[$name] instanceof UnresolvedParam
            && ! $parent[$name] instanceof DefaultValueParam;
        if ($implicitNamed) {
            return $parent[$name];
        }

        // is a default value available for the current class?
        if ($rparam->isDefaultValueAvailable()) {
            return new DefaultValueParam($name, $rparam->getDefaultValue());
        }

        // is a default value available for the parent class?
        $parentDefault = array_key_exists($name, $parent)
            && $parent[$name] instanceof DefaultValueParam;
        if ($parentDefault) {
            return $parent[$name];
        }

        // param is missing
        return new UnresolvedParam($name);
    }

    /**
     *
     * Returns the unified mutations for a class.
     *
     * Class-specific mutations are executed last before trait-based mutations and before interface-based mutations.
     *
     * @param string $class The class name to return values for.
     *
     * @param array $parent The parent unified setters.
     *
     * @return array The unified mutations.
     *
     */
    protected function getUnifiedMutations(string $class, array $parent): array
    {
        $unified = $parent;

        // look for interface mutations
        $interfaces = class_implements($class);
        foreach ($interfaces as $interface) {
            if (isset($this->mutations[$interface])) {
                $unified = array_merge(
                    $this->mutations[$interface],
                    $unified
                );
            }
        }

        // look for trait mutations
        $traits = $this->reflector->getTraits($class);
        foreach ($traits as $trait) {
            if (isset($this->mutations[$trait])) {
                $unified = array_merge(
                    $this->mutations[$trait],
                    $unified
                );
            }
        }

        // look for class mutations
        if (isset($this->mutations[$class])) {
            $unified = array_merge(
                $unified,
                $this->mutations[$class]
            );
        }

        return $unified;
    }

    /**
     *
     * Returns the unified setters for a class.
     *
     * Class-specific setters take precendence over trait-based setters, which
     * take precedence over interface-based setters.
     *
     * @param string $class The class name to return values for.
     *
     * @param array $parent The parent unified setters.
     *
     * @return array The unified setters.
     *
     */
    protected function getUnifiedSetters(string $class, array $parent): array
    {
        $unified = $parent;

        // look for interface setters
        $interfaces = class_implements($class);
        foreach ($interfaces as $interface) {
            if (isset($this->setters[$interface])) {
                $unified = array_merge(
                    $this->setters[$interface],
                    $unified
                );
            }
        }

        // look for trait setters
        $traits = $this->reflector->getTraits($class);
        foreach ($traits as $trait) {
            if (isset($this->setters[$trait])) {
                $unified = array_merge(
                    $this->setters[$trait],
                    $unified
                );
            }
        }

        // look for class setters
        if (isset($this->setters[$class])) {
            $unified = array_merge(
                $unified,
                $this->setters[$class]
            );
        }

        return $unified;
    }

    private function getParamSettings(string $class): array
    {
        $unified = [];
        $rparams = $this->reflector->getParams($class);
        foreach ($rparams as $rparam) {
            $unified[$rparam->getName()] = $rparam->isVariadic();
        }

        return $unified;
    }
}
