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

/**
 *
 * A set of Container configuration instructions.
 *
 * @package Aura.Di
 *
 */
class ContainerConfig implements ContainerCompileInterface
{
    /**
     *
     * Define params, setters, and services before the Container is locked.
     *
     * @param Container $di The DI container.
     *
     */
    public function define(Container $di): void
    {
    }

    /**
     *
     * Execute code after the Container is defined and before the Container is locked and compiled.
     *
     * @param Container $di The DI container.
     *
     */
    public function compile(Container $di): void
    {
    }

    /**
     *
     * Modify service objects after the Container is locked.
     *
     * @param Container $di The DI container.
     *
     */
    public function modify(Container $di): void
    {
    }
}
