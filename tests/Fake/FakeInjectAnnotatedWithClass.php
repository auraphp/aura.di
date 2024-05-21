<?php
namespace Aura\Di\Fake;

use Aura\Di\Attribute\Value;

class FakeInjectAnnotatedWithClass
{
    private array $workers;

    public function __construct(
        #[Value('worker')]
        array $workers,
    ) {
        $this->workers = $workers;
    }

    public function getWorkers(): array
    {
        return $this->workers;
    }
}
