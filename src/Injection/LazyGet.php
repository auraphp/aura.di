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

use Aura\Di\Exception\ServiceNotFound;
use Aura\Di\Resolver\Resolver;
use Psr\Container\ContainerInterface;

/**
 *
 * Returns a Container service when invoked.
 *
 * @package Aura.Di
 *
 */
class LazyGet implements LazyInterface
{
    /**
     *
     * The service name to retrieve.
     *
     * @var string
     *
     */
    protected $service;

    /**
     *
     * If applicable a delegated container
     *
     * @var ?ContainerInterface
     *
     */
    private ?ContainerInterface $delegatedContainer = null;

    /**
     *
     * Constructor.
     *
     * @param string $service The service to retrieve.
     *
     * @param ?ContainerInterface $delegatedContainer The service container.
     *
     */
    public function __construct(string $service, ?ContainerInterface $delegatedContainer = null)
    {
        $this->service = $service;
        $this->delegatedContainer = $delegatedContainer;
    }

    /**
     *
     * Invokes the closure to create the instance.
     *
     * @return object The object created by the closure.
     *
     */
    public function __invoke(Resolver $resolver): object
    {
        try {
            return $resolver->getServiceInstance($this->service);
        } catch (ServiceNotFound $e) {
            if ($this->delegatedContainer?->has($this->service)) {
                return $this->delegatedContainer->get($this->service);
            }

            throw $e;
        }
    }
}
