# Injection and container modification via attributes

Rather than defining injection via `$di->params`, `$di->setters`, `$di->values` or `$di->types`, you can also annotate 
parameters using the [native attributes available from PHP 8.0](https://www.php.net/manual/en/language.attributes.overview.php).
For now, only constructor parameters can be annotated.

The advantage would be that you have the injection decisions included in the class itself, keeping it all 
together. Moreover, when your application grows, the length of your code constructing the container might grow to such size
that maintaining it becomes a problem. This might even be the case when you have separated it with `ContainerConfigInterface` instances.
With attributes such a problem does not exist.

Moreover, you can define attributes that modify the container. These will be discussed in the end of this chapter.

## Inject with the Service Attribute

If a parameter is annotated with the `#[Service]` attribute, you define that a service should be injected. 

For example, look at the following class; the `$foo` constructor parameter has such an annotation:

```php
use Aura\Di\Attribute\Service;

class Example
{
    public function __construct(
        #[Service('foo.service')]
        Foo $foo
    ) {
        // ...
    }
}
```

The _Container_ will inject `foo.service` for `$foo`. It is basically the same as if you would write the following:

```php
$di->params['Example']['foo'] = $di->lazyGet('foo.service');
```

## Inject with the Instance Attribute

If the parameter is annotated with the `#[Instance]` attribute, you define a new instance of a class that should be injected. 

For example, look at the following class; the `$foo` constructor parameter has such an annotation:

```php
use Aura\Di\Attribute\Instance;

class Example
{
    public function __construct(
        #[Instance(Foo::class)]
        FooInterface $foo
    ) {
        // ...
    }
}
```

The _Container_ will inject a new instance `Foo::class` for `$foo`. It is basically the same as if you would write the following:

```php
$di->params['Example']['foo'] = $di->lazyNew(Foo::class);
```

## Inject with the Value Attribute

If a parameter is annotated with the `#[Value]` attribute, you define that a value should be injected. 

For example, look at the following class; the `$foo` constructor parameter has such an annotation:

```php
use Aura\Di\Attribute\Instance;

class Example
{
    public function __construct(
        #[Value('foo.value')]
        string $foo
    ) {
        // ...
    }
}
```

The _Container_ will inject the value `foo.value` for `$foo`. It is basically the same as if you would write the following:

```php
$di->params['Example']['foo'] = $di->lazyValue('foo.value');
```

## Inject with Custom Attributes

It is also possible to create your own custom attribute. All you have to do is create a class using [the native PHP 8.0 attribute
syntax](https://www.php.net/manual/en/language.attributes.syntax.php). On top, it has to implement the `Aura\Di\Attribute\AnnotatedInjectInterface` class.

Suppose you want to inject a config key coming from the `ConfigBag` object below, into other classes.

```php
namespace MyApp;

class ConfigBag {
    private array $bag = [];
    
    public function __construct(array $bag) 
    {
        $this->bag = $bag;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->bag[$key] ?? $default;
    }
}
```

You could create an `Config` attribute class like this.

```php
namespace MyApp\Attribute\Config;

use Attribute;
use Aura\Di\Injection\Lazy;
use Aura\Di\Injection\LazyGet;
use Aura\Di\Injection\LazyInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Config implements AnnotatedInjectInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function inject(): LazyInterface
    {
        $callable = [new LazyGet('config'), 'get'];
        return new Lazy($callable, [$this->name]);
    }
}
```

You set the service `config` in the container.

```php
$di->set('config', new ConfigBag(['foo' => 'bar']));
```

And then you could annotate a constructor parameter with your own `#[Config]` attribute.

```php
use MyApp\Attribute\Config;

class Example
{
    public function __construct(
        #[Config('foo')]
        string $foo
    ) {
        // ...
    }
}
```

It is basically the same as if you would write the following:

```php
$di->params['Example']['foo'] = $di->lazyGetCall('config', 'get', 'foo');
```

## Overwriting attribute injection

When you define both an annotation and an injection via code, the injection via code has precedence over the annotation.

Using the last example of the custom attribute, the following code will overwrite the `#[Config('foo')]` annotation on
the `$foo` constructor parameter, and hence inject the value `"bravo"` and not `"bar"`.

```php
$di->set('config', new ConfigBag(['foo' => 'bar', 'alpha' => 'bravo']));
$di->params['Example']['foo'] = $di->lazyGetCall('config', 'get', 'alpha');
```

## Configure the Container using attributes

The above annotations for injecting the right parameters into a class work out-of-the-box when working with the 
container. But there might be occasions that you want to an annotation to change the configuration of the container.

Examples are a `#[Route('GET', '/order/{id}')]` attribute that adds a route to your routes, or a 
`#[ListenFor(OrderWasPlaced::class)]` annotation that adds a listener to your event manager.

Configuring the container with attributes requires building the container with the
[`ClassScannerConfig`](config.md#scan-for-classes-and-annotations). When done so, the builder will scan the
passed directories for classes and annotations. Every class that is annotated with `#[AttributeConfigFor]`
and implements `AttributeConfigInterface` can modify the container.

In the following example we create our own a `#[Route]` attribute that also implements the `AttributeConfigInterface`. 
The attribute `#[AttributeConfigFor]` is referencing the Route class. It is basically a self-reference because the attribute is
attached to the Route class. Now, methods annotated with the new `#[Route]`
will cause a `RealRoute` to be appended in the routes array.


```php
use Aura\Di\Attribute\AttributeConfigFor;
use Aura\Di\ClassScanner\AttributeConfigInterface;
use Aura\Di\ClassScanner\AttributeSpecification;
use Aura\Di\Container;

#[\Attribute]
#[AttributeConfigFor(Route::class)]
class Route implements AttributeConfigInterface {
    public function __construct(private string $method, private string $uri) {
    }
    
    public static function define(Container $di, AttributeSpecification $specification): void
    {
        if ($specification->getAttributeTarget() === \Attribute::TARGET_METHOD) {
            /** @var self $attribute */
            $attribute = $specification->getAttributeInstance();
            // considering the routes key is an array, defined like this
            // $resolver->values['routes'] = [];
            $di->values['routes'][] = new RealRoute(
                $attribute->method, 
                $attribute->uri,
                $container->lazyLazy(
                    $di->lazyCallable([
                        $di->lazyNew($specification->getClassName()),
                        $specification->getTargetConfig()['method']
                    ])
                )
            );
        }
    }
}

class Controller {
    #[Route('GET', '/method1')]
    public function method1() {}
    
    #[Route('GET', '/method2')]
    public function method2() {}
}

class RouterFactory {
    public function __construct(
        #[Value('routes')]
        private array $routes
    ) {
        // $routes contains an array of RealRoute objects
    }
}
```

If your attribute cannot implement the `AttributeConfigInterface`, e.g. the attribute is defined in an external package,
you can create an implementation of `AttributeConfigInterface` yourself, and annotate it with `#[AttributeConfigFor(ExternalAttribute::class)]`.

```php
use Aura\Di\Attribute\AttributeConfigFor;
use Aura\Di\ClassScanner\AttributeConfigInterface;
use Aura\Di\ClassScanner\AttributeSpecification;
use Aura\Di\Container;
use Symfony\Component\Routing\Attribute\Route;

#[AttributeConfigFor(Route::class)]
class SymfonyRouteAttributeConfig implements AttributeConfigInterface
{
    public static function define(Container $di, AttributeSpecification $specification): void
    {
        if ($specification->getAttributeTarget() === \Attribute::TARGET_METHOD) {
            /** @var Route $attribute */
            $attribute = $specification->getAttributeInstance();
            
            $invokableRoute = $di->lazyCallable([
                $container->lazyNew($annotatedClassName),
                $specification->getTargetConfig()['method']
            ]);
            
            // these are not real parameters, but just examples
            $di->values['routes'][] = new Symfony\Component\Routing\Route(
                $attribute->getPath(),
                $attribute->getMethods(),
                $attribute->getName(),
                $invokableRoute
            );
        }
    }
}
```

## Compiled Blueprints

[Reflection](https://www.php.net/reflection) is used by the container to get information of the class, e.g. what
parameters are used by the constructor. This information is used to create a class that in this package is called
a `Blueprint`.

When you annotate a constructor parameter with `#[Service]`, `#[Instance]`, `#[Value]` or with an attribute implementing
`Aura\Di\Attribute\AnnotatedInjectInterface` then the class automatically gets the marker that it needs to compiled
into a `Blueprint` when you call `newCompiledInstance`  method on the `ContainerBuilder`. This is also true that use
the code method like `$container->params` and `$container->setters`.

There might be classes however, that are not configured using attributes or code but need to be instantiated by the 
container somewhere in your code anyhow. Take for instance the class below. This class does not have an injection 
attribute like `#[Service]`, `#[Config]` or `#[Value]`, and there might not also be a `$di->params[]` call to configure 
this class. So the class is unknown to the container.

If you want to create a `Blueprint` for this class during container compilation, annotate it with `#[Blueprint]`.

```php
use Aura\Di\Attribute\Blueprint;

#[Blueprint]
class OrderController 
{
    public function __construct(private Connection $databaseConnection) 
    {
    }
}
```

To prevent, many classes have to be annotated with the `#[Blueprint]` attribute, you can also use the
`#[BlueprintNamespace]` attribute, typically annotated to be an `Application`, `Kernel` or `Plugin` class.

```php
namespace MyPlugin;

use Aura\Di\Attribute\BlueprintNamespace;

#[BlueprintNamespace(__NAMESPACE__ . '\\Controllers')]
#[BlueprintNamespace(__NAMESPACE__ . '\\Command')]
class Plugin {

}
```

Typically, you should not compile all namespace in your application or plugin. That would be overkill, because there 
are classes like entities, models and DTOs that are never being instantiated by the container.

Working with compiled blueprints require using the [`ClassScannerConfig`](config.md#scan-for-classes-and-annotations).