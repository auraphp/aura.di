# Getting Started

## Overview

The Aura.Di package provides a serializable dependency injection container
with the following features:

- constructor and setter injection

- inheritance of constructor parameter and setter method values from parent classes

- inheritance of setter method values from interfaces and traits

- lazy-loaded instances, services, includes/requires, and values

- instance factories

- optional auto-resolution of typehinted constructor parameter values

Fully describing the nature and benefits of dependency injection, while
desirable, is beyond the scope of this document. For more information about
"inversion of control" and "dependency injection" please consult
<http://martinfowler.com/articles/injection.html> by Martin Fowler.

Finally, please note that this package is intended for use as a **dependency injection** system, not as a **service locator** system. If you use it as a service locator, that's bad, and you should feel bad.

### Intended Usage

The intent behind Aura.Di is for it be used like so:

1. Instantiate a container.

2. Do **all** configuration for **all** classes and services.

3. Lock the container so it cannot be modified further.

4. Retrieve objects from the locked container.

Note that calling `get()` or `newInstance()` will automatically lock the container, preventing further configuration changes. This is true *even inside configuration code*, so use the `lazy*()` methods instead while configuring the container.


## Container Instantiation

We instantiate a _Container_ like so:

```php
use Aura\Di\ContainerBuilder;
$builder = new ContainerBuilder();
$di = $builder->newInstance();
```

## Creating Object Instances

The most straightforward way is to create an object through the _Container_ is via the `newInstance()` method:

```
$object = $di->newInstance('Vendor\Package\ClassName');
```

> N.b.: The _Container_ locks itself once a new instance is produced; this ensures that the _Container_ configuration cannot be modified once objects have been created.

However, this is a relatively naive way to create objects with the _Container_. It is better to specify the various constructor parameters, setter methods, and so on, and let the _Container_ inject those values for us only when the object is used as a dependency for something else.

## Full-featured instantiation

A full-featured container can use [attributes](attributes.md) for injection and container modification. Moreover, for
maximum performance, we would have to compile the container, serialize it and save it to a cache layer like the filesystem.
Subsequent processes would only have to unserialize to have a compiled container. 

The `ClassScanner` scans for classes and annotations inside your project. This does require, 
however, to add a package to your dependencies.

```sh
composer require composer/class-map-generator
``` 

Creating a fully-featured container could look as follows:

```php
use Aura\Di\ClassScanner\ClassScannerConfig;
use Aura\Di\ContainerBuilder;

$serializedContainerFile = '/var/compiled.ser';
$config_classes = [
    new \MyApp\Config1,
    new \MyApp\Config2,
    ClassScannerConfig::newScanner(
        [$rootDir . '/app/src'], // these directories should be scanned for classes and annotations
        ['MyApp\\'], // classes inside these namespaces should be compiled
    )
];

if (file_exists($serializedContainerFile)) {
    $di = \unserialize(file_get_contents($serializedContainerFile));
} else {
    $builder = new ContainerBuilder();
    $di = $builder->newCompiledInstance($config_classes);
    
    $serialized = \serialize($di);
    file_put_contents($serializedContainerFile, $serialized); // atomic for concurrency
}

$di = $builder->configureCompiledInstance($di, $config_classes);
```

From this point on you can call `newInstance` or `get` on the container.