# Container Builder and Config Classes

## The ContainerBuilder

The _ContainerBuilder_ builds fully-configured _Container_ objects using _ContainerConfig_ classes. It works 
using a [two-stage configuration system](http://auraphp.com/blog/2014/04/07/two-stage-config).

The two stages are "define" and "modify". In the "define" stage, the _ContainerConfig_ object defines constructor 
parameter values, setter method values, services, and so on. The _ContainerBuilder_ then locks the _Container_ so that 
these definitions cannot be changed, and begins the "modify" stage. In the "modify" stage, we may `get()` services from 
the _Container_ and modify them programmatically if needed.

To build a fully-configured _Container_ using the _ContainerBuilder_, we do something like the following:

```php
use Aura\Di\ContainerBuilder;

$container_builder = new ContainerBuilder();

// use the builder to create and configure a container
// using an array of ContainerConfig classes
$di = $container_builder->newConfiguredInstance([
    Aura\Cli\_Config\Common::class,
    Aura\Router\_Config\Common::class,
    Aura\Web\_Config\Common::class,
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
    Aura\Cli\_Config\Common::class,
    $routerConfig,
    Aura\Web\_Config\Common::class,
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
container does all the class metadata collection and creates a `Blueprint` class for every class that has been
configured in the container. The methodology for creating a compiled container is similar to creating 
a configured instance.

Instead of `newConfiguredInstance`, you now call the `newCompiledInstance` method.

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

The resulting container is ready for serialization. You can find a more real-world example below, which checks for
a serialized container on the filesystem. When it does not exist, it uses the `ContainerBuilder` to create a container
and save it to the filesystem.

```php
use Aura\Di\ContainerBuilder;

$serializedContainerFile = '/var/compiled.ser';

if (file_exists($serializedContainerFile)) {
    $di = \unserialize(file_get_contents($serializedContainerFile));
} else {
    $builder = new ContainerBuilder();
    $di = $builder->newCompiledInstance($config_classes);
    
    $serialized = \serialize($di);
    file_put_contents($serializedContainerFile, $serialized);
}
```

Please note, serialization won't work with closures. Serializing a container with following configuration throws an 
exception.

```php
$di->params[VendorClass::class] = [
    'param' => $di->lazy(
        function () {
            return new VendorParamClass();
        }
    ),
];
```

## Scan for classes and annotations

The `ClassScannerConfig` class uses a generated-file to extract all classes and annotations from your project. You will 
need that if you want to [modify the container using attributes](attributes.md#modify-the-container-using-attributes) or
you want to compile blueprints.

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

Then execute the scan, and see the file `vendor/aura.di.scan.json` as result.

```shell
# scan inside the classmap paths, but if cache exits, it returns the cache
vendor/bin/auradi scan

# force a complete new scan of all classes and annotations inside the classmap paths 
vendor/bin/auradi scan --force
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
    ClassScannerConfig::fromCacheFile('vendor/aura.di.scan.json') // reference the correct path here
];

$di = $builder->newCompiledInstance($config_classes);
```

During development, you will have to rescan if you have annotated classes with attributes that modify the 
container. Also, if you have dependencies with those attributes, you probably want to attach 
[a 'post-install-cmd' and/or a 'post-update-cmd'](https://getcomposer.org/doc/articles/scripts.md#command-events)
script to your composer.json.

```json
{
    "scripts": {
        "post-install-cmd": "vendor/bin/auradi scan --force",
        "post-update-cmd": "vendor/bin/auradi scan --force"
    }
}
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