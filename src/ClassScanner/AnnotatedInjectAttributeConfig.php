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
    public static function define(Container $di, AttributeSpecification $attribute, ClassSpecification $class): void
    {
        if ($attribute->isConstructorParameterAttribute()) {
            /** @var AnnotatedInjectInterface $annotatedInject */
            $annotatedInject = $attribute->getAttributeInstance();
            $di->params[$attribute->getClassName()][$attribute->getTargetParameter()] = $annotatedInject->inject();
        }
    }
}