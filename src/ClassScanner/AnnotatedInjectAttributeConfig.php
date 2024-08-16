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

use Aura\Di\Attribute\AnnotatedInjectInterface;
use Aura\Di\Container;

final class AnnotatedInjectAttributeConfig implements AttributeConfigInterface
{
    private const CONSTRUCTOR_NAME = '__construct';

    public static function define(Container $di, AttributeSpecification $specification): void
    {
        /** @var AnnotatedInjectInterface $attribute */
        $attribute = $specification->getAttributeInstance();
        if ($specification->getTargetMethod() === self::CONSTRUCTOR_NAME && $specification->getAttributeTarget() === \Attribute::TARGET_PARAMETER) {
            $di->params[$specification->getClassName()][$specification->getTargetParameter()] = $attribute->inject();
        }
    }
}