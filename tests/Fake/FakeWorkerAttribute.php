<?php
namespace Aura\Di\Fake;

use Aura\Di\Attribute\AttributeConfigFor;
use Aura\Di\Attribute\BlueprintNamespace;
use Aura\Di\ClassScanner\AttributeConfigInterface;
use Aura\Di\ClassScanner\AttributeSpecification;
use Aura\Di\ClassScanner\ClassSpecification;
use Aura\Di\Container;

#[\Attribute]
#[AttributeConfigFor(FakeWorkerAttribute::class)]
#[BlueprintNamespace(__NAMESPACE__)]
class FakeWorkerAttribute implements AttributeConfigInterface
{
    private int $someSetting;

    public function __construct(int $someSetting = 1)
    {
        $this->someSetting = $someSetting;
    }

    public static function define(
        Container $di,
        AttributeSpecification $attributeSpecification,
        ClassSpecification $classSpecification
    ): void
    {
        /** @var self $attribute */
        $attribute = $attributeSpecification->getAttributeInstance();
        if ($attributeSpecification->getAttributeTarget() === \Attribute::TARGET_CLASS) {
            $di->values['worker'] = $di->values['worker'] ?? [];
            $di->values['worker'][] = [
                'someSetting' => $attribute->someSetting,
                'className' => $attributeSpecification->getClassName(),
            ];
        }
    }
}