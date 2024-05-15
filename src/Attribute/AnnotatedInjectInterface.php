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

use Aura\Di\Injection\LazyInterface;

interface AnnotatedInjectInterface
{
    public function inject(): LazyInterface;
}