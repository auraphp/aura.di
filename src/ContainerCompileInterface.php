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
 * An interface for a Container configuration instruction that needs to be executed just before the
 * container is compiled.
 *
 * @package Aura.Di
 *
 */
interface ContainerCompileInterface extends ContainerConfigInterface
{
    /**
     *
     * Execute code after the Container is defined and before the Container is locked and compiled.
     *
     * @param Container $di The DI container.
     *
     */
    public function compile(Container $di): void;
}
