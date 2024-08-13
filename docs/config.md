# Container Builder and Config Classes

## The ContainerBuilder

The _ContainerBuilder_ also builds fully-configured _Container_ objects using _ContainerConfig_ classes. It works 
using a [two-stage configuration system](http://auraphp.com/blog/2014/04/07/two-stage-config).

The two stages are "define" and "modify". In the "define" stage, the _ContainerConfig_ object defines constructor parameter values, setter method values, services, and so on. The _ContainerBuilder_ then locks the _Container_ so that these definitions cannot be changed, and begins the "modify" stage. In the "modify" stage, we may `get()` services from the _Container_ and modify them programmatically if needed.

To build a fully-configured _Container_ using the _ContainerBuilder_, we do something like the following:

```php
use Aura\Di\ContainerBuilder;

$container_builder = new ContainerBuilder();

// use the builder to create and configure a container
// using an array of ContainerConfig classes
$di = $container_builder->newConfiguredInstance([
    'Aura\Cli\_Config\Common',
    'Aura\Router\_Config\Common',
    'Aura\Web\_Config\Common',
]);
```

**Note:** As with the `newInstance` method of the `ContainerBuilder`, you will have to 
pass `$container_builder::AUTO_RESOLVE` to `newConfiguredInstance` (as the second parameter) if you want to enable 
auto-resolution.

## Container Config classes

A configuration class looks like the following:

```php
namespace Vendor\Package;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;

class Config extends ContainerConfig
{
    public function define(Container $di)
    {
        $di->set('log_service', $di->lazyNew('Logger'));
        $di->params['Logger']['dir'] = '/path/to/logs';
    }

    public function modify(Container $di)
    {
        $log = $di->get('log_service');
        $log->debug('Finished config.');
    }
}
```

Here are some example _ContainerConfig_ classes from earlier Aura packages:

- [Aura.Cli](https://github.com/auraphp/Aura.Cli/blob/2.0.0/config/Common.php)
- [Aura.Html](https://github.com/auraphp/Aura.Html/blob/2.0.0/config/Common.php)
- [Aura.Router](https://github.com/auraphp/Aura.Router/blob/2.0.0/config/Common.php)
- [Aura.View](https://github.com/auraphp/Aura.View/blob/2.0.0/config/Common.php)

Alternatively, if you already have a ContainerConfig object created, you can pass it directly to the ContainerBuilder instead of a string class name:

```php
$routerConfig = new Aura\Router\_Config\Common();

// use the builder to create and configure a container
// using an array of ContainerConfig classes
$di = $container_builder->newConfiguredInstance([
    'Aura\Cli\_Config\Common',
    $routerConfig,
    'Aura\Web\_Config\Common',
]);
```

If you have a package which combines a number of disparate components that
each provide a `ContainerConfig` you could bundle them together using the
`ConfigCollection` class. This class takes an array of `ContainerConfig`s or
`ContainerConfig` class names and implements `ContainerConfigInterface` itself.
```php

namespace My\App;

use Aura\Di\ConfigCollection;

use My\Domain;
use My\WebInterface;
use My\DataSource;

class Config extends ConfigCollection
{
    public function __construct()
    {
        parent::__construct(
            [
                Domain\Config::class,
                WebInterface\Config::class,
                DataSource\Config::class,
            ]
        );
    }
}
```

You can then use the Collection and it will instantiate (if necessary) and call
the `define` and `modify` methods of each of the other ContainerConfigs.
```php
$di = $container_builder->newConfiguredInstance([\My\App\Config::class])
```

## Compiling and serializing the container

With the _ContainerBuilder_, you can also create a compiled container that is ready for serialization. A compiled 
container does all the class metadata collection and creates a `Blueprint` class for every class that can be instantiated.
The methodology for creating a compiled container is similar to creating a configured instance.

```php
use Aura\Di\ContainerBuilder;

$config_classes = [
    new Aura\Router\_Config\Common(),
];

$container_builder = new ContainerBuilder();

$di = $container_builder->newCompiledInstance(
    $config_classes,
    ContainerBuilder::AUTO_RESOLVE
);
```

We can then serialize and unserialize the container:

```php
$serialized = serialize($di);
$di = unserialize($serialized);
```

This serialized container might be saved to a file, as cache layer before continuing. Finally, we must configure the 
compiled instance. 

```php
$di = $builder->configureCompiledInstance($di, $config_classes);

$fakeService = $di->get('fake');
```

Please note, serializing won't work with closures. Serializing a container with following throws an exception.

```php
$di->params[VendorClass::class] = [
    'param' => $di->lazy(
        function () {
            return new VendorParamClass();
        }
    ),
];
```

## Compiled Blueprints

When classes are instantiated by the container, it uses reflection to get information of the class, e.g. what 
parameters are used by the constructor. This information is used to create a class that in this package is called
a Blueprint. 

In order to prevent that blueprints have to created for every PHP-run, you can improve performance by compiling 
blueprints. You do this by annotating a class, typically an `Application`, `Kernel` or `Plugin` class. The following
example demonstrates how to use compiled blueprints for your `Controller` and `Command` namespace.

```php
namespace MyPlugin;

use Aura\Di\Attribute\CompileNamespace;

#[CompileNamespace(__NAMESPACE__ . '\\Controllers')]
#[CompileNamespace(__NAMESPACE__ . '\\Command')]
class Plugin {

}
```

Typically, you should not compile namespace your complete plugin. That would be overkill, because there are classes
like entities, models and DTOs that are never being instantiated by the container.

Working with the `#[CompileNamespace]` attribute requires using the `ClassScannerConfig` which is documented below.

## Scan for classes and annotations

The `ClassScannerConfig` class uses a generated-file to extract all classes and annotations from your project. You will 
need that if you want to [modify the container using attributes](attributes.md#modify-the-container-using-attributes).

First of all, this does require to add a package to your dependencies.

```sh
composer require composer/class-map-generator
``` 

Then add to the `"extra"` of your composer.json a new key `"aura/di"` with subkey `"classmap-paths"` to indicate which
paths should be scanned for classes and annotations. This is also true for dependencies. Those `"classmap-paths"` will
be picked up by the scanner too.

```json
{
    "require": {
        "aura/di": "^5.0",
        ...
    },
    "extra": {
        "aura/di": {
            "classmap-paths": [
                "./lib",
                "./src",
                "./app/Controller",
                "./app/Services",
                "./app/Commands"
            ]
        }
    }
}
```

Then execute the scan.

```shell
vendor/bin/auradi scan
```

Then add the `ClassScannerConfig` to your Container Config classes. This example will generate a container in which 
the container was modified by using attributes and with compiled blueprints as explained above. 

```php
use Aura\Di\ClassScanner\ClassScannerConfig;
use Aura\Di\ContainerBuilder;

$builder = new ContainerBuilder();
$config_classes = [
    new \MyApp\Config1,
    new \MyApp\Config2,
    ClassScannerConfig::newScanner(__DIR__ . '/../../aura.di.scan.json') // reference the correct path here
];

$di = $builder->newCompiledInstance($config_classes);
```

## Compiled objects inside the container

There might be other objects that you want to compile before serializing the container. A good example might be a 
router. All routes might be compiled into a performant route dispatcher, if your routing package supports this.

When creating a compiled instance you can pass config classes that implement the `ContainerCompileInterface` to the 
`newCompiledInstance` and `configureCompiledInstance` methods of the _ContainerBuilder_. This
interface is an extension of the `ContainerConfigInterface` and has a single method `compile(Container $di): void`. 
That method is executed after the `define(Container $di): void` method from the `ContainerConfigInterface` and just 
before the container is compiled.

An implementation might look as follows:

```php
use Aura\Di\Attribute\Value;
use Aura\Di\ContainerCompileInterface;

class RouterContainerConfig implements ContainerCompileInterface 
{
    public function define(Container $di): void
    {
        $container->set('router.factory', $container->lazyNew(MyRouterFactory::class));
    }

    public function compile(Container $di): void
    {
        $container->set('router', $container->get('router.factory')->compile());
    }

    public function modify(Container $di): void
    {
    }
}

class MyRouterFactory {
    public function __construct(
        #[Value('routes')]
        private array $routes
    ) {
    }

    public function compile(): Router
    {
        $router = new Router();
        foreach ($this->routes as $route) {
            $router->addRoute($route);
        }
        $router->compile();
        return $router;
    }
}
```