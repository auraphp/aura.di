<?php
declare(strict_types=1);
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */

namespace Aura\Di\Attribute;

use Attribute;
use Aura\Di\Injection\Lazy;
use Aura\Di\Injection\LazyGet;
use Aura\Di\Injection\LazyInterface;
use Aura\Di\Resolver\Resolver;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Service implements InjectAttributeInterface
{
    private string $name;
    private ?string $methodName = null;

    public function __construct(string $name, ?string $methodName = null)
    {
        $this->name = $name;
        $this->methodName = $methodName;
    }

    public function apply(Resolver $resolver): LazyInterface
    {
        if ($this->methodName) {
            $callable = [new LazyGet($this->name), $this->methodName];
            return new Lazy($callable);
        }

        return new LazyGet($this->name);
    }
}