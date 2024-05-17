<?php
namespace Aura\Di\Fake;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class FakeCompilationTestConfig extends ContainerConfig
{
    public function define(Container $di): void
    {
        parent::define($di);
        $fakeService = new FakeInterfaceClass1();
        $fakeServiceGet = new FakeInterfaceClass1();
        $fakeService->setFoo($fakeServiceGet);

        $di->set('fake.service', $fakeService);
        $di->values['fake.value'] = 'value';
    }
}
