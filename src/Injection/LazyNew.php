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

use Aura\Di\Resolver\Blueprint;

/**
 *
 * Returns a new instance of an object when invoked.
 *
 * @package Aura.Di
 *
 */
class LazyNew extends Factory implements LazyInterface
{
    public static function fromClassName(string $className): self
    {
        return new self(new Blueprint($className));
    }
}
