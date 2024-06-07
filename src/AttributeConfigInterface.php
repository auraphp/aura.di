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

interface AttributeConfigInterface
{
    public function define(
        Container $di,
        object $attribute,
        string $className,
        int $attributeTarget,
        array $targetConfig
    ): void;
}