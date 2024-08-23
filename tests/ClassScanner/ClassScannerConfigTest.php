<?php
namespace Aura\Di\ClassScanner;

use Aura\Di\Container;
use Aura\Di\Fake\FakeCompilationTestConfig;
use Aura\Di\Fake\FakeConstructAttributeClass;
use Aura\Di\Fake\FakeControllerClass;
use Aura\Di\Fake\FakeInjectAnnotatedWithClass;
use Aura\Di\Resolver\Reflector;
use Aura\Di\Resolver\Resolver;
use PHPUnit\Framework\TestCase;

class ClassScannerConfigTest extends TestCase
{
    /**
     * @var ClassScannerConfig
     */
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new ClassScannerConfig(
            new ComposerMapGenerator([__DIR__ . '/../Fake']),
        );
    }

    public function testAttributes()
    {
        $container = new Container(new Resolver(new Reflector()));
        $this->config->define($container);
        $this->assertCount(1, $container->values['worker']);

        $annotation = $container->values['worker'][0];
        $this->assertSame(3, $annotation['someSetting']);
        $this->assertSame(FakeConstructAttributeClass::class, $annotation['className']);

        /** @var FakeInjectAnnotatedWithClass $injectedWith */
        $injectedWith = $container->newInstance(FakeInjectAnnotatedWithClass::class);
        $this->assertCount(1, $injectedWith->getWorkers());
        $this->assertSame($annotation, $injectedWith->getWorkers()[0]);
    }

    public function testBlueprintApplied()
    {
        $resolver = new Resolver(new Reflector());
        $container = new Container($resolver);
        $this->config->define($container);

        $this->assertTrue(\array_key_exists(FakeControllerClass::class, $resolver->params));
    }

    public function testOrder()
    {
        $resolver = new Resolver(new Reflector());
        $container = new Container($resolver);
        $container->params[FakeConstructAttributeClass::class]['string'] = 'this_value_should_override_the_attribute';

        $testConfig = new FakeCompilationTestConfig();
        $testConfig->define($container);
        $this->config->define($container);

        $instance = $container->newInstance(FakeConstructAttributeClass::class);
        $this->assertSame('this_value_should_override_the_attribute', $instance->getString());
    }
}
