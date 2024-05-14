<?php
namespace Aura\Di\Fake;

use Aura\Di\Attribute\Instance;
use Aura\Di\Attribute\Service;
use Aura\Di\Attribute\Value;

class FakeConstructAttributeClass
{
    private FakeInterface $fakeService;
    private FakeInterface $fakeServiceGet;
    private FakeInterface $fakeInstance;
    private string $string;

    public function __construct(
        #[Service('fake.service')]
        FakeInterface $fakeService,
        #[Service('fake.service', 'getFoo')]
        FakeInterface $fakeServiceGet,
        #[Instance(FakeInterfaceClass2::class)]
        FakeInterface $fakeInstance,
        #[Value('fake.value')]
        string $string,
    ) {
        $this->fakeService = $fakeService;
        $this->fakeServiceGet = $fakeServiceGet;
        $this->fakeInstance = $fakeInstance;
        $this->string = $string;
    }

    public function getFakeService(): FakeInterface
    {
        return $this->fakeService;
    }

    public function getFakeServiceGet(): FakeInterface
    {
        return $this->fakeServiceGet;
    }

    public function getFakeInstance(): FakeInterface
    {
        return $this->fakeInstance;
    }

    public function getString(): string
    {
        return $this->string;
    }
}
