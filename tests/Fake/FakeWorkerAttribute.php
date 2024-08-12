<?php
namespace Aura\Di\Fake;

use Aura\Di\Attribute\AttributeConfigFor;
use Aura\Di\Attribute\CompileNamespace;
use Aura\Di\ClassScanner\AttributeConfigInterface;
use Aura\Di\ClassScanner\AttributeSpecification;
use Aura\Di\Container;

#[\Attribute]
#[AttributeConfigFor(FakeWorkerAttribute::class)]
#[CompileNamespace(__NAMESPACE__)]
class FakeWorkerAttribute implements AttributeConfigInterface
{
    private int $someSetting;

    public function __construct(int $someSetting = 1)
    {
        $this->someSetting = $someSetting;
    }

    public static function define(Container $di, AttributeSpecification $specification): void
    {
        /** @var self $attribute */
        $attribute = $specification->getAttributeInstance();
        if ($specification->getAttributeTarget() === \Attribute::TARGET_CLASS) {
            $di->values['worker'] = $di->values['worker'] ?? [];
            $di->values['worker'][] = [
                'someSetting' => $attribute->someSetting,
                'className' => $specification->getClassName(),
            ];
        }
    }
}