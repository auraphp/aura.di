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

use Aura\Di\Resolver\AutoResolver;
use Aura\Di\Resolver\Reflector;
use Aura\Di\Resolver\Resolver;

/**
 *
 * Creates and configures a new DI container.
 *
 * @package Aura.Di
 *
 */
class ContainerBuilder
{
    /**
     *
     * Use the auto-resolver.
     *
     * @const true
     *
     */
    const AUTO_RESOLVE = true;

    /**
     *
     * Returns a new Container instance.
     *
     * @param bool $autoResolve Use the auto-resolver?
     *
     * @return Container
     *
     */
    public function newInstance(bool $autoResolve = false): Container
    {
        return new Container($this->newResolver($autoResolve));
    }

    /**
     *
     * Returns a new Resolver instance.
     *
     * @param bool $autoResolve Use the auto-resolver?
     *
     * @return Resolver
     *
     */
    protected function newResolver(bool $autoResolve = false): Resolver
    {
        if ($autoResolve) {
            return new AutoResolver(new Reflector());
        }

        return new Resolver(new Reflector());
    }

    /**
     *
     * Creates a new Container, applies ContainerConfig classes to define()
     * services, locks the container, and applies the ContainerConfig instances
     * to modify() services.
     *
     * @param array $configClasses A list of ContainerConfig classes to
     * instantiate and invoke for configuring the Container.
     *
     * @param bool $autoResolve Use the auto-resolver?
     *
     * @return Container
     *
     */
    public function newConfiguredInstance(
        array $configClasses = [],
        bool $autoResolve = false
    ): Container {
        $di = $this->newInstance($autoResolve);
        $collection = $this->newConfigCollection($configClasses);

        $collection->define($di);
        $di->lock();
        $collection->modify($di);

        return $di;
    }

    /**
     *
     * Creates a new Container, applies ContainerConfig classes to define()
     * services, locks the container and compiles all classes into blueprints.
     * A compiled container is ready to serialize.
     *
     * @param array $configClasses A list of ContainerConfig classes to
     * instantiate and invoke for configuring the Container.
     *
     * @param array $extraCompileClasses A list of classes that should also be compiled, e.g. a list of classes that
     * might contain container annotations.
     *
     * @param bool $autoResolve Use the auto-resolver?
     *
     * @return Container
     */
    public function newCompiledInstance(
        array $configClasses = [],
        array $extraCompileClasses = [],
        bool $autoResolve = false,
    ): Container {
        $resolver = $this->newResolver($autoResolve);
        $di = new Container($resolver);
        $collection = $this->newConfigCollection($configClasses);

        $collection->define($di);
        $di->lock();
        $resolver->compile($extraCompileClasses);

        return $di;
    }

    /**
     *
     * Creates a new Container, applies ContainerConfig classes to define()
     * services, locks the container, and applies the ContainerConfig instances
     * to modify() services.
     *
     * @param Container $compiledContainer The container that has been compiled in an earlier step.
     *
     * @param array $configClasses A list of ContainerConfig classes to
     * instantiate and invoke for configuring the Container.
     *
     * @return Container
     */
    public function configureCompiledInstance(
        Container $compiledContainer,
        array $configClasses = [],
    ): Container {
        $this->newConfigCollection($configClasses)->modify($compiledContainer);
        return $compiledContainer;
    }

    /**
     *
     * Creates a new ContainerConfig for a collection of
     * ContainerConfigInterface classes
     *
     *
     * @param array $configClasses A list of ContainerConfig classes to
     * instantiate and invoke for configuring the Container.
     *
     * @return ConfigCollection
     */
    protected function newConfigCollection(array $configClasses = []): ConfigCollection
    {
        return new ConfigCollection($configClasses);
    }
}
