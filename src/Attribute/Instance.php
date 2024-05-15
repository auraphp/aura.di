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
use Aura\Di\Injection\LazyInterface;
use Aura\Di\Injection\LazyNew;
use Aura\Di\Resolver\Blueprint;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Instance implements InjectAttributeInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function inject(): LazyInterface
    {
        return new LazyNew(new Blueprint($this->name));
    }
}