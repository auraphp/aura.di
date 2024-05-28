<?php
declare(strict_types=1);
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Di\Injection;

use Aura\Di\Container;
use Aura\Di\Resolver\Blueprint;
use Psr\Container\ContainerInterface;

/**
 *
 * A factory to create objects and values for injection into the Container.
 *
 * @package Aura.Di
 *
 */
class InjectionFactory
{
    /**
     *
     * Returns a new Factory.
     *
     * @param string $class The class to create.
     *
     * @param array $params Override params for the class.
     *
     * @param array $setters Override setters for the class.
     *
     * @return Factory
     *
     */
    public function newFactory(
        $class,
        array $params = [],
        array $setters = []
    ): Factory
    {
        return new Factory(new Blueprint($class, $params, $setters));
    }

    /**
     *
     * Returns a new Lazy.
     *
     * @param callable $callable The callable to invoke.
     *
     * @param array $params Arguments for the callable.
     *
     * @return Lazy
     *
     */
    public function newLazy($callable, array $params = []): Lazy
    {
        return new Lazy($callable, $params);
    }

    /**
     *
     * Returns a new LazyArray.
     *
     * @param array $callables The callables to invoke.
     *
     * @return LazyArray
     *
     */
    public function newLazyArray(array $callables): LazyArray
    {
        return new LazyArray($callables);
    }

    /**
     *
     * Returns a new LazyCallable.
     *
     * @param callable|array{0: LazyInterface, 1: string} $callable The callable to invoke.
     *
     * @return LazyCallable
     *
     */
    public function newLazyCallable(callable|array $callable): LazyCallable
    {
        return new LazyCallable($callable);
    }

    /**
     *
     * Returns a new LazyGet.
     *
     * @param Container $container The service container.
     *
     * @param string $service The service to retrieve.
     *
     * @return LazyGet
     *
     */
    public function newLazyGet(Container $container, string $service): LazyGet
    {
        return new LazyGet($service, $container->getDelegateContainer());
    }

    /**
     *
     * Returns a new LazyInclude.
     *
     * @param string $file The file to include.
     *
     * @return LazyInclude
     *
     */
    public function newLazyInclude(string $file): LazyInclude
    {
        return new LazyInclude($file);
    }

    /**
     *
     * Returns a new LazyNew.
     *
     * @param string $class The class to instantiate.
     *
     * @param array $params Params for the instantiation.
     *
     * @param array $setters Setters for the instantiation.
     *
     * @return LazyNew
     *
     */
    public function newLazyNew(
        string $class,
        array $params = [],
        array $setters = []
    ): LazyNew
    {
        return new LazyNew(new Blueprint($class, $params, $setters));
    }

    /**
     *
     * Returns a new LazyRequire.
     *
     * @param string $file The file to require.
     *
     * @return LazyRequire
     *
     */
    public function newLazyRequire(string $file): LazyRequire
    {
        return new LazyRequire($file);
    }

    /**
     *
     * Returns a new LazyValue.
     *
     * @param string $key The value key to use.
     *
     * @return LazyValue
     *
     */
    public function newLazyValue(string $key): LazyValue
    {
        return new LazyValue($key);
    }
}
