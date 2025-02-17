<?php
namespace Aura\Di;

class ContainerConfigTestCase extends AbstractContainerConfigTestCase
{
    protected function getConfigClasses()
    {
        return [
            'Aura\Di\Fake\FakeLibraryConfig',
            'Aura\Di\Fake\FakeProjectConfig',
        ];
    }

    protected function getAutoResolve()
    {
        return false;
    }

    public function provideGet()
    {
        return [
            ['library_service', 'StdClass'],
            ['project_service', 'StdClass'],
        ];
    }

    public function provideNewInstance()
    {
        return [
            ['StdClass'],
        ];
    }
}
