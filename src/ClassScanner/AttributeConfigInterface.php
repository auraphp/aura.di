<?php
declare(strict_types=1);
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */

namespace Aura\Di\ClassScanner;

use Aura\Di\Container;

interface AttributeConfigInterface
{
    public static function define(
        Container $di,
        AttributeSpecification $attributeSpecification,
        ClassSpecification $classSpecification
    ): void;
}