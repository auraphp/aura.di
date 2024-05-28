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

use Aura\Di\Resolver\Resolver;

/**
 *
 * Returns any value resulting from a LazyInterface when invoked.
 *
 * @package Aura.Di
 *
 */
class LazyLazy
{
    /**
     *
     * The Resolver to invoke the LazyInterface with.
     *
     * @var Resolver
     *
     */
    protected Resolver $resolver;

    /**
     *
     * The LazyInterface to be invoked.
     *
     * @var LazyInterface
     *
     */
    protected LazyInterface $lazy;

    /**
     *
     * Constructor.
     *
     * @param Resolver $resolver The service to retrieve.
     *
     * @param LazyInterface $lazy The service container.
     *
     */
    public function __construct(Resolver $resolver, LazyInterface $lazy)
    {
        $this->resolver = $resolver;
        $this->lazy = $lazy;
    }

    /**
     *
     * Invokes the lazy object with the resolver.
     *
     * @return mixed The value created by the lazy object
     *
     */
    public function __invoke(...$params): mixed
    {
        return \call_user_func($this->lazy, $this->resolver, ...$params);
    }
}
