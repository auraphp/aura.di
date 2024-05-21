<?php
namespace Aura\Di\Fake;

use Aura\Di\AttributeConfigInterface;
use Aura\Di\Container;

#[\Attribute]
class FakeWorkerAttribute implements AttributeConfigInterface
{
    private int $someSetting;

    public function __construct(int $someSetting = 1)
    {
        $this->someSetting = $someSetting;
    }

    public function define(Container $di, \ReflectionAttribute $attribute, \Reflector $annotatedTo): void
    {
        if ($annotatedTo instanceof \ReflectionClass) {
            $di->values['worker'] = $di->values['worker'] ?? [];
            $di->values['worker'][] = [
                'someSetting' => $this->someSetting,
                'className' => $annotatedTo->getName(),
            ];
        }
    }
}