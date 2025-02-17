<?php
declare(strict_types=1);
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Di;

use Aura\Di\Injection\Factory;
use Aura\Di\Injection\InjectionFactory;
use Aura\Di\Injection\Lazy;
use Aura\Di\Injection\LazyArray;
use Aura\Di\Injection\LazyCallable;
use Aura\Di\Injection\LazyGet;
use Aura\Di\Injection\LazyInclude;
use Aura\Di\Injection\LazyInterface;
use Aura\Di\Injection\LazyLazy;
use Aura\Di\Injection\LazyNew;
use Aura\Di\Injection\LazyRequire;
use Aura\Di\Injection\LazyValue;
use Aura\Di\Resolver\Blueprint;
use Aura\Di\Resolver\Resolver;
use Closure;
use Psr\Container\ContainerInterface;

/**
 *
 * Dependency injection container.
 *
 * @package Aura.Di
 *
 * @property array $params A reference to the Resolver $params.
 *
 * @property array $setters A reference to the Resolver $setters.
 *
 * @property array $mutations A reference to the Resolver $mutates.
 *
 * @property array $types A reference to the Resolver $types.
 *
 * @property array $values A reference to the Resolver $values.
 *
 */
class Container implements ContainerInterface
{
    /**
     *
     * A factory to create objects and values for injection.
     *
     * @var InjectionFactory
     *
     */
    protected InjectionFactory $injectionFactory;

    /**
     *
     * A container that will be used instead of the main container
     * to fetch dependencies.
     *
     * @var ?ContainerInterface
     *
     */
    protected ?ContainerInterface $delegateContainer;

    /**
     *
     * A Resolver obtained from the InjectionFactory.
     *
     * @var Resolver
     *
     */
    protected Resolver $resolver;

    /**
     *
     * Is the Container locked?  (When locked, you cannot access configuration
     * properties from outside the object, and cannot set services.)
     *
     * @var bool
     *
     * @see __get()
     *
     * @see set()
     *
     */
    protected bool $locked = false;

    /**
     * @param Resolver $resolver Resolves new objects based on its Blueprint.
     *
     * @param ?ContainerInterface $delegateContainer An optional container
     * that will be used to fetch dependencies (i.e. lazy gets)
     *
     */
    public function __construct(
        Resolver $resolver,
        ?ContainerInterface $delegateContainer = null
    ) {
        $this->injectionFactory = new InjectionFactory();
        $this->resolver = $resolver;
        $this->delegateContainer = $delegateContainer;
    }

    /**
     *
     * Magic get to provide access to the Resolver properties.
     *
     * @param string $key The Resolver property to retrieve.
     *
     * @return array
     *
     * @throws Exception\ContainerLocked
     *
     */
    public function &__get(string $key): array
    {
        if ($this->locked) {
            throw Exception::containerLocked();
        }

        return $this->resolver->__get($key);
    }

    /**
     *
     * Returns the InjectionFactory.
     *
     * @return InjectionFactory
     *
     */
    public function getInjectionFactory(): InjectionFactory
    {
        return $this->injectionFactory;
    }

    /**
     *
     * Returns the secondary delegate container, if applicable.
     *
     * @return ?ContainerInterface
     *
     */
    public function getDelegateContainer(): ?ContainerInterface
    {
        return $this->delegateContainer;
    }

    /**
     *
     * Locks the Container so that is it read-only.
     *
     * @return void
     *
     */
    public function lock(): void
    {
        $this->locked = true;
    }

    /**
     *
     * Is the Container locked?
     *
     * @return bool
     *
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     *
     * Does a particular service definition exist?
     *
     * @param string $id The service key to look up.
     *
     * @return bool
     *
     */
    public function has(string $id): bool
    {
        if ($this->resolver->hasService($id)) {
            return true;
        }

        return isset($this->delegateContainer)
            && $this->delegateContainer->has($id);
    }

    /**
     *
     * Sets a service definition by name. If you set a service as a Closure,
     * it is automatically treated as a Lazy. (Note that is has to be a
     * Closure, not just any callable, to be treated as a Lazy; this is
     * because the actual service object itself might be callable via an
     * __invoke() method.)
     *
     * @param string $service The service key.
     *
     * @param object|callable $val The service object; if a Closure, is treated as a
     * Lazy.
     *
     * @throws Exception\ContainerLocked when the Container is locked.
     *
     * @throws Exception\ServiceNotObject
     *
     * @return $this
     *
     */
    public function set(string $service, object|callable $val): Container
    {
        if ($this->locked) {
            throw Exception::containerLocked();
        }

        if (! is_object($val)) {
            throw Exception::serviceNotObject($service, $val);
        }

        if ($val instanceof Closure) {
            $val = $this->injectionFactory->newLazy($val);
        }

        $this->resolver->setService($service, $val);

        return $this;
    }

    /**
     *
     * Gets a service object by key.
     *
     * @param string $id The service to get.
     *
     * @return object
     *
     * @throws Exception\ServiceNotFound when the requested service
     * does not exist.
     *
     */
    public function get(string $id): object
    {
        $this->locked = true;
        return $this->getServiceInstance($id);
    }

    /**
     *
     * Instantiates a service object by key, lazy-loading it as needed.
     *
     * @param string $service The service to get.
     *
     * @return object
     *
     * @throws Exception\ServiceNotFound when the requested service
     * does not exist.
     *
     */
    protected function getServiceInstance(string $service): object
    {
        if ($this->resolver->hasService($service)) {
            return $this->resolver->getServiceInstance($service);
        }

        if ($this->has($service)) {
            return $this->delegateContainer->get($service);
        }

        throw Exception::serviceNotFound($service);
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
        return $this->resolver->getInstances();
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
        return $this->resolver->getServices();
    }

    /**
     *
     * Returns a lazy object that calls a callable, optionally with arguments.
     *
     * @param callable|mixed $callable The callable.
     *
     * @param array $params
     *
     * @return Lazy
     */
    public function lazy($callable, ...$params): Lazy
    {
        return $this->injectionFactory->newLazy($callable, $params);
    }

    /**
     *
     * Returns a LazyLazy object that can be called directly without the requirement of a Resolver or Container.
     *
     * @param LazyInterface $lazy The lazy object generated by this Container.
     *
     * @return LazyLazy
     */
    public function lazyLazy(LazyInterface $lazy): LazyLazy
    {
        return new LazyLazy($this->resolver, $lazy);
    }

    /**
     *
     * Returns a lazy object that wraps an array that may contain
     * (potentially lazy) callables that get invoked at calltime.
     *
     * @param array $callables The (potentially lazy) array of callables to invoke.
     *
     * @return LazyArray
     *
     */
    public function lazyArray(array $callables): LazyArray
    {
        return $this->injectionFactory->newLazyArray($callables);
    }

    /**
     *
     * Returns a lazy object that invokes a (potentially lazy) callable with
     * parameters supplied at calltime.
     *
     * @param callable|array{0: LazyInterface, 1: string} $callable The (potentially lazy) callable.
     *
     * @return LazyCallable
     *
     */
    public function lazyCallable(callable|array $callable): LazyCallable
    {
        return $this->injectionFactory->newLazyCallable($callable);
    }

    /**
     *
     * Returns a lazy object that gets a service.
     *
     * @param string $service The service name; it does not need to exist yet.
     *
     * @return LazyGet
     *
     */
    public function lazyGet(string $service): LazyGet
    {
        return $this->injectionFactory->newLazyGet($this, $service);
    }

    /**
     *
     * Returns a lazy object that gets a service and calls a method on it,
     * optionally with paramters.
     *
     * @param string $service The service name.
     *
     * @param string $method The method to call on the service object.
     *
     * @param ...$params mixed Parameters to use in the method call.
     *
     * @return Lazy
     *
     */
    public function lazyGetCall(string $service, string $method, ...$params): Lazy
    {
        $callable = [$this->lazyGet($service), $method];

        return $this->injectionFactory->newLazy($callable, $params);
    }

    /**
     *
     * Returns a lazy object that creates a new instance.
     *
     * @param string $class The type of class of instantiate.
     *
     * @param array $params Override parameters for the instance.
     *
     * @param array $setters Override setters for the instance.
     *
     * @return LazyNew
     *
     */
    public function lazyNew(
        $class,
        array $params = [],
        array $setters = []
    ): LazyNew
    {
        return $this->injectionFactory->newLazyNew($class, $params, $setters);
    }

    /**
     *
     * Returns a lazy that requires a file.
     *
     * @param string $file The file to require.
     *
     * @return LazyRequire
     *
     */
    public function lazyRequire(string $file): LazyRequire
    {
        return $this->injectionFactory->newLazyRequire($file);
    }

    /**
     *
     * Returns a lazy that includes a file.
     *
     * @param string $file The file to include.
     *
     * @return LazyInclude
     *
     */
    public function lazyInclude(string $file): LazyInclude
    {
        return $this->injectionFactory->newLazyInclude($file);
    }

    /**
     *
     * Returns a lazy for an arbitrary value.
     *
     * @param string $key The arbitrary value key.
     *
     * @return LazyValue
     *
     */
    public function lazyValue(string $key): LazyValue
    {
        return $this->injectionFactory->newLazyValue($key);
    }

    /**
     *
     * Returns a factory that creates an object over and over again (as vs
     * creating it one time like the lazyNew() or newInstance() methods).
     *
     * @param string $class The factory will create an instance of this class.
     *
     * @param array $params Override parameters for the instance.
     *
     * @param array $setters Override setters for the instance.
     *
     * @return Factory
     *
     */
    public function newFactory(
        string $class,
        array $params = [],
        array $setters = []
    ): Factory
    {
        return $this->injectionFactory->newFactory(
            $class,
            $params,
            $setters
        );
    }

    /**
     *
     * Creates and returns a new instance of a class using reflection and
     * the configuration parameters, optionally with overrides, invoking Lazy
     * values along the way.
     *
     * Note the that container must be locked before creating a new instance.
     * This prevents premature resolution of params and setters.
     *
     * @phpstan-template T
     *
     * @param class-string<T> $class The class to instantiate.
     *
     * @param array $mergeParams An array of override parameters; the key may
     * be the name *or* the numeric position of the constructor parameter, and
     * the value is the parameter value to use.
     *
     * @param array $mergeSetters An array of override setters; the key is the
     * name of the setter method to call and the value is the value to be
     * passed to the setter method.
     *
     * @return object&T
     *
     */
    public function newInstance(
        string $class,
        array $mergeParams = [],
        array $mergeSetters = []
    ): object
    {
        $this->locked = true;
        return $this->resolver->resolve(
            new Blueprint(
                $class,
                $mergeParams,
                $mergeSetters
            )
        );
    }

    /**
     *
     * Returns a callable object to resolve a service or new instance of a class
     *
     * @return ResolutionHelper
     */
    public function newResolutionHelper(): ResolutionHelper
    {
        return new ResolutionHelper($this);
    }
}
