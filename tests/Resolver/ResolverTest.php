<?php
namespace Aura\Di\Resolver;

use Aura\Di\Fake\FakeInterfaceClass1;
use Aura\Di\Fake\FakeInterfaceClass2;
use Aura\Di\Fake\FakeInvokableClass;
use Aura\Di\Injection\Factory;
use Aura\Di\Injection\Lazy;
use Aura\Di\Injection\LazyGet;
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    /**
     * @var Resolver
     */
    protected $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new Resolver(new Reflector());
    }

    public function testReadsConstructorDefaults()
    {
        $expect = ['foo' => new DefaultValueParam('foo', 'bar')];
        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeParentClass');
        $this->assertEquals($expect, $blueprint->getParams());
    }

    public function testTwiceForMerge()
    {
        $expect = $this->resolver->getUnified('Aura\Di\Fake\FakeParentClass');
        $actual = $this->resolver->getUnified('Aura\Di\Fake\FakeParentClass');
        $this->assertSame($expect, $actual);
    }

    public function testHonorsParentParams()
    {
        $expect = [
            'foo' => new DefaultValueParam('foo', 'bar'),
            'zim' => new DefaultValueParam('zim', null),
        ];

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeChildClass');
        $this->assertEquals($expect, $blueprint->getParams());
    }

    public function testHonorsExplicitParams()
    {
        $this->resolver->params['Aura\Di\Fake\FakeParentClass'] = ['foo' => 'zim'];

        $expect = ['foo' => 'zim'];
        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeParentClass');
        $this->assertSame($expect, $blueprint->getParams());
    }

    public function testHonorsExplicitParentParams()
    {
        $this->resolver->params['Aura\Di\Fake\FakeParentClass'] = ['foo' => 'dib'];

        $expect = [
            'foo' => 'dib',
            'zim' => new DefaultValueParam('zim', null),
        ];

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeChildClass');
        $this->assertEquals($expect, $blueprint->getParams());

        // for test coverage of the mock class
        $child = new \Aura\Di\Fake\FakeChildClass('bar', new \Aura\Di\Fake\FakeOtherClass);
    }

    public function testHonorsParentSetter()
    {
        $this->resolver->setters['Aura\Di\Fake\FakeParentClass']['setFake'] = 'fake1';

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeChildClass');
        $expect = ['setFake' => 'fake1'];
        $this->assertSame($expect, $blueprint->getSetters());

    }

    public function testHonorsOverrideSetter()
    {
        $this->resolver->setters['Aura\Di\Fake\FakeParentClass']['setFake'] = 'fake1';
        $this->resolver->setters['Aura\Di\Fake\FakeChildClass']['setFake'] = 'fake2';

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeChildClass');
        $expect = ['setFake' => 'fake2'];
        $this->assertSame($expect, $blueprint->getSetters());
    }

    public function testHonorsTraitSetter()
    {
        $this->resolver->setters['Aura\Di\Fake\FakeTrait']['setFake'] = 'fake1';

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeClassWithTrait');
        $expect = ['setFake' => 'fake1'];
        $this->assertSame($expect, $blueprint->getSetters());
    }

    public function testHonorsChildTraitSetter()
    {
        $this->resolver->setters['Aura\Di\Fake\FakeChildTrait']['setChildFake'] = 'fake1';

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeClassWithTrait');
        $expect = ['setChildFake' => 'fake1'];
        $this->assertSame($expect, $blueprint->getSetters());
    }

    public function testHonorsGrandChildTraitSetter()
    {
        $this->resolver->setters['Aura\Di\Fake\FakeGrandchildTrait']['setGrandchildFake'] = 'fake1';

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeClassWithTrait');
        $expect = ['setGrandchildFake' => 'fake1'];
        $this->assertSame($expect, $blueprint->getSetters());
    }

    public function testHonorsParentClassTraits()
    {
        $this->resolver->setters['Aura\Di\Fake\FakeGrandchildTrait']['setGrandchildFake'] = 'fake1';
        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeClassWithParentTrait');
        $expect = ['setGrandchildFake' => 'fake1'];
        $this->assertSame($expect, $blueprint->getSetters());
    }

    public function testHonorsOverrideTraitSetter()
    {
        $this->resolver->setters['Aura\Di\Fake\FakeTrait']['setFake'] = 'fake1';
        $this->resolver->setters['Aura\Di\Fake\FakeChildTrait']['setChildFake'] = 'fake2';
        $this->resolver->setters['Aura\Di\Fake\FakeClassWithTrait']['setFake'] = 'fake3';
        $this->resolver->setters['Aura\Di\Fake\FakeClassWithTrait']['setChildFake'] = 'fake4';

        $blueprint = $this->resolver->getUnified('Aura\Di\Fake\FakeClassWithTrait');
        $expect = ['setChildFake' => 'fake4', 'setFake' => 'fake3'];
        $this->assertSame($expect, $blueprint->getSetters());
    }

    public function testReflectionOnMissingClass()
    {
        $this->expectException('ReflectionException');
        $this->resolver->resolve(new Blueprint('NoSuchClass'));
    }

    public function testHonorsLazyParams()
    {
        $this->resolver->params['Aura\Di\Fake\FakeParentClass']['foo'] = new Lazy(function () {
            return new \Aura\Di\Fake\FakeOtherClass();
        });
        $actual = $this->resolver->resolve(new Blueprint('Aura\Di\Fake\FakeParentClass'));
        $this->assertInstanceOf('Aura\Di\Fake\FakeOtherClass', $actual->getFoo());
    }

    public function testMissingParam()
    {
        $this->expectException('Aura\Di\Exception\MissingParam');
        $this->expectExceptionMessage('Aura\Di\Fake\FakeResolveClass::$fake');
        $this->resolver->resolve(new Blueprint('Aura\Di\Fake\FakeResolveClass'));
    }

    public function testUnresolvedParamAfterMergeParams()
    {
        $this->expectException('Aura\Di\Exception\MissingParam');
        $this->resolver->resolve(
            new Blueprint(
                'Aura\Di\Fake\FakeParamsClass',
                ['noSuchParam' => 'foo']
            )
        );
    }

    public function testPositionalParams()
    {
        $this->resolver->params['Aura\Di\Fake\FakeParentClass'][0] = 'val0';
        $this->resolver->params['Aura\Di\Fake\FakeChildClass'][1] = 'val1';

        $actual = $this->resolver->resolve(new Blueprint('Aura\Di\Fake\FakeChildClass'));
        $expect = [
            'foo' => 'val0',
            'zim' => 'val1',
        ];
        $this->assertSame($expect, ['foo' => $actual->getFoo(), 'zim' => $actual->getZim()]);
    }

    public function testFromFactory()
    {
        $resolver = new Resolver(new Reflector());
        $other = $resolver->resolve(new Blueprint('Aura\Di\Fake\FakeOtherClass'));

        $factory = new Factory(
            new Blueprint(
                'Aura\Di\Fake\FakeChildClass',
                [
                    'foo' => 'foofoo',
                    'zim' => $other,
                ],
                [
                    'setFake' => 'fakefake',
                ]
            )
        );

        $actual = $factory($resolver);

        $this->assertInstanceOf('Aura\Di\Fake\FakeChildClass', $actual);
        $this->assertInstanceOf('Aura\Di\Fake\FakeOtherClass', $actual->getZim());
        $this->assertSame('foofoo', $actual->getFoo());
        $this->assertSame('fakefake', $actual->getFake());

        // create another one, should not be the same
        $extra = $factory($resolver);
        $this->assertNotSame($actual, $extra);
    }

    public function testAttributes()
    {
        $fakeService = new FakeInterfaceClass1();
        $fakeServiceGet = new FakeInvokableClass();
        $fakeService->setFoo($fakeServiceGet);

        $this->resolver->setService('fake.service', $fakeService);
        $this->resolver->values['fake.value'] = 'value';

        $actual = $this->resolver->resolve(new Blueprint('Aura\Di\Fake\FakeConstructAttributeClass'));
        $this->assertSame($fakeService, $actual->getFakeService());
        $this->assertSame($fakeServiceGet, $actual->getFakeServiceGet());
        $this->assertInstanceOf('Aura\Di\Fake\FakeInterfaceClass2', $actual->getFakeInstance());
        $this->assertSame('value', $actual->getString());
    }

    public function testOverwriteAttribute()
    {
        $fakeService = new FakeInterfaceClass1();
        $fakeService2 = new FakeInterfaceClass2();
        $fakeServiceGet = new FakeInvokableClass();
        $fakeService->setFoo($fakeServiceGet);

        $this->resolver->setService('fake.service', $fakeService);
        $this->resolver->setService('fake.service2', $fakeService2);
        $this->resolver->values['fake.value'] = 'value';
        $this->resolver->params['Aura\Di\Fake\FakeConstructAttributeClass']['fakeService'] = new LazyGet('fake.service2');

        $actual = $this->resolver->resolve(new Blueprint('Aura\Di\Fake\FakeConstructAttributeClass'));
        $this->assertSame($fakeService2, $actual->getFakeService());
    }

    public function testCompile()
    {
        $fakeService = new FakeInterfaceClass1();
        $fakeService2 = new FakeInterfaceClass2();
        $fakeServiceGet = new FakeInvokableClass();
        $fakeService->setFoo($fakeServiceGet);

        $this->resolver->setService('fake.service', $fakeService);
        $this->resolver->setService('fake.service2', $fakeService2);
        $this->resolver->values['fake.value'] = 'value';
        $this->resolver->params['Aura\Di\Fake\FakeConstructAttributeClass']['fakeService'] = new LazyGet('fake.service2');
        $this->resolver->compile();

        $actual = $this->resolver->resolve(new Blueprint('Aura\Di\Fake\FakeConstructAttributeClass'));
        $this->assertSame($fakeService2, $actual->getFakeService());
        $this->assertSame($fakeServiceGet, $actual->getFakeServiceGet());
        $this->assertInstanceOf('Aura\Di\Fake\FakeInterfaceClass2', $actual->getFakeInstance());
        $this->assertSame('value', $actual->getString());
    }
}
