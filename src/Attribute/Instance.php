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

#[Attribute(Attribute::TARGET_PARAMETER)]
class Instance implements AnnotatedInjectInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function inject(): LazyInterface
    {
        return LazyNew::fromClassName($this->name);
    }
}