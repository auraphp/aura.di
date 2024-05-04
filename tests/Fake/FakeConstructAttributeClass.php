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
    private FakeInterface $fakeSetter;
    private string $string;

    public function __construct(
        #[Service('fake.service')]
        FakeInterface $fakeService,
        #[Service('fake.service', 'getFoo')]
        FakeInterface $fakeServiceGet,
        #[Instance(FakeInterfaceClass1::class)]
        FakeInterface $fakeInstance,
        #[Value('fake.value')]
        string $string,
    ) {
        $this->fakeService = $fakeService;
        $this->fakeServiceGet = $fakeServiceGet;
        $this->fakeInstance = $fakeInstance;
        $this->string = $string;
    }

    public function setFake(
        #[Service('fake.setter')]
        FakeInterface $fakeSetter
    ) {
        $this->fakeSetter = $fakeSetter;
    }
}
