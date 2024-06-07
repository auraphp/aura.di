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
    /**
     * @param Container $di The container.
     *
     * @param object $attribute The instance of the attribute
     *
     * @param string $annotatedClassName The class that has been annotated.
     *
     * @param int $attributeTarget One the \Attribute::TARGET_ constants.
     *
     * @param array{'method'?: string, 'constant'?: string, 'parameter'?: string, 'property'?: string} $targetConfig The
     * target configuration depends on the $attributeTarget
     */
    public function define(
        Container $di,
        object $attribute,
        string $annotatedClassName,
        int $attributeTarget,
        array $targetConfig
    ): void;
}