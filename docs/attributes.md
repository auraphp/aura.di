# Injection via attributes

Rather than defining injection via `$di->params`, `$di->setters`, `$di->values` or `$di->types`, you can also annotate 
parameters using the native attributes available from PHP 8.0.

The advantage would be that you have the injection decisions included in the class itself, keeping it all 
together. Moreover, it reduces the size of the construction of your container, even if you have separated it with 
many `ContainerConfigInterface` instances.

For now, only constructor parameters can be annotated.

## Service Attribute

If the parameter is annotated with the `#[Service]` attribute, you define that a service should be injected. 

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

## Instance Attribute

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

## Value Attribute

If the parameter is annotated with the `#[Value]` attribute, you define a new value that should be injected. 

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

The _Container_ will inject a new instance `Foo::class` for `$foo`. It is basically the same as if you would write the following:

```php
$di->params['Example']['foo'] = $di->lazyValue('foo.value');
```

## Custom Attributes

It is also possible to create your attribute. All you have to do is create a class using [the native PHP 8.0 attribute
annotation](https://www.php.net/manual/en/language.attributes.overview.php), and it has to implement the `Aura\Di\Attribute\AnnotatedInjectInterface` class.

Suppose you want to inject a string coming from the `ConfigBag` object below, into other classes.

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

## Overwriting attributes

When you define an annotation and an injection via code, the injection via code has precedence over the annotation.

Using the last example of the custom attribute, the following code will overwrite the `#[Config('foo')]` annotation,
and hence inject the value `"bravo"` and not `"bar"`.

```php
$di->set('config', new ConfigBag(['foo' => 'bar', 'alpha' => 'bravo']));
$di->params['Example']['foo'] = $di->lazyGetCall('config', 'get', 'alpha');
```