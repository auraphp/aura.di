<?php
namespace Aura\Di;

use Aura\Di\ClassScanner\ClassScannerConfig;
use Aura\Di\Fake\FakeInjectAnnotatedWithClass;
use Aura\Di\Resolver\Reflector;
use Aura\Di\Resolver\Resolver;
use PHPUnit\Framework\TestCase;

class ClassScannerTest extends TestCase
{
    /**
     * @var ClassScannerConfig
     */
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = ClassScannerConfig::newScanner(
            [__DIR__ . '/Fake'],
            ['Aura\Di\Fake'],
        );
    }

    public function testAttributes()
    {
        $container = new Container(new Resolver(new Reflector()));
        $this->config->define($container);
        $this->assertCount(1, $container->values['worker']);

        $annotation = $container->values['worker'][0];
        $this->assertSame(3, $annotation['someSetting']);
        $this->assertSame('Aura\Di\Fake\FakeConstructAttributeClass', $annotation['className']);

        /** @var FakeInjectAnnotatedWithClass $injectedWith */
        $injectedWith = $container->newInstance(FakeInjectAnnotatedWithClass::class);
        $this->assertCount(1, $injectedWith->getWorkers());
        $this->assertSame($annotation, $injectedWith->getWorkers()[0]);
    }
}
