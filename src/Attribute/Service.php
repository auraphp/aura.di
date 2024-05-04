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

#[Attribute(Attribute::TARGET_PARAMETER)]
class Service
{
    private string $name;

    public function __construct(string $name, ?string $methodName = null, ...$params)
    {
        $this->name = $name;
    }
}