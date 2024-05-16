<?php
declare(strict_types=1);
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Di\Resolver;

use Aura\Di\Exception;
use Aura\Di\Injection\LazyInterface;
use Aura\Di\Injection\MutationInterface;
use ReflectionClass;
use function array_values;

final class Blueprint
{
    /**
     * @var string
     */
    private string $className;

    /**
     * @var array
     */
    private array $params;

    /**
     * @var array
     */
    private array $setters;

    /**
     * @var array
     */
    private array $mutations;

    /**
     * @var array
     */
    private array $paramSettings = [];

    /**
     * @param string $className
     * @param array $params
     * @param array $setters
     * @param array $mutations
     */
    public function __construct(
        string $className,
        array $params = [],
        array $setters = [],
        array $mutations = [],
    )
    {
        $this->className = $className;
        $this->params = $params;
        $this->setters = $setters;
        $this->mutations = $mutations;
    }

    /**
     * Merges all parameters and invokes the lazy ones.
     *
     * @param Blueprint $mergeBlueprint The overrides during merging
     *
     * @return Blueprint The merged blueprint
     */
    public function merge(Blueprint $mergeBlueprint): Blueprint
    {
        $blueprint = new Blueprint(
            $this->className,
            $this->mergeParams($mergeBlueprint),
            $this->mergeSetters($mergeBlueprint),
            $this->mergeMutations($mergeBlueprint)
        );
        $blueprint->paramSettings = array_merge($this->paramSettings, $mergeBlueprint->paramSettings);
        return $blueprint;
    }

    /**
     * Instantiates a new object based on the current blueprint.
     *
     * @return object
     */
    public function __invoke(Resolver $resolver): object
    {
        $className = $this->className;

        $object = new $className(
            ...array_map(
                function ($val) use ($resolver) {
                    // is the param missing?
                    if ($val instanceof UnresolvedParam) {
                        throw Exception::missingParam($this->className, $val->getName());
                    }

                    // load lazy objects as we go
                    if ($val instanceof LazyInterface) {
                        $val = $val($resolver);
                    }

                    return $val;
                },
                array_values($this->expandParams())
            )
        );

        foreach ($this->setters as $method => $value) {
            if (! method_exists($this->className, $method)) {
                throw Exception::setterMethodNotFound($this->className, $method);
            }
            if ($value instanceof LazyInterface) {
                $value = $value($resolver);
            }
            $object->$method($value);
        }

        /** @var MutationInterface $mutation */
        foreach ($this->mutations as $mutation) {
            if ($mutation instanceof LazyInterface) {
                $mutation = $mutation($resolver);
            }

            if ($mutation instanceof MutationInterface === false) {
                throw Exception::mutationDoesNotImplementInterface($mutation);
            }

            $object = $mutation($object);
        }

        return $object;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return array
     */
    public function getSetters(): array
    {
        return $this->setters;
    }

    /**
     * @return array
     */
    public function getMutations(): array
    {
        return $this->mutations;
    }

    /**
     * @param array $paramSettings
     * @return Blueprint
     */
    public function withParamSettings(array $paramSettings): self
    {
        $clone = clone $this;
        $clone->paramSettings = $paramSettings;
        return $clone;
    }

    /**
     *
     * Merges the setters with overrides; also invokes Lazy values.
     *
     * @param Blueprint $mergeBlueprint A blueprint containing override setters.
     *
     * @return array The merged setters
     */
    private function mergeSetters(Blueprint $mergeBlueprint): array
    {
        return array_merge($this->setters, $mergeBlueprint->setters);
    }

    /**
     *
     * Merges the setters with overrides; also invokes Lazy values.
     *
     * @param Blueprint $mergeBlueprint A blueprint containing additional mutations.
     *
     * @return array The merged mutations
     */
    private function mergeMutations(Blueprint $mergeBlueprint): array
    {
        return array_merge($this->mutations, $mergeBlueprint->mutations);
    }

    /**
     *
     * Merges the params with overides; also invokes Lazy values.
     *
     * @param Blueprint $mergeBlueprint A blueprint containing override parameters; the key may
     * be the name *or* the numeric position of the constructor parameter, and
     * the value is the parameter value to use.
     *
     * @return array The merged params
     *
     */
    private function mergeParams(Blueprint $mergeBlueprint): array
    {
        if (! $mergeBlueprint->params) {
            // no params to merge, micro-optimize the loop
            return $this->params;
        }

        $params = $this->params;

        $pos = 0;
        foreach ($params as $key => $val) {

            // positional overrides take precedence over named overrides
            if (array_key_exists($pos, $mergeBlueprint->params)) {
                // positional override
                $val = $mergeBlueprint->params[$pos];
            } elseif (array_key_exists($key, $mergeBlueprint->params)) {
                // named override
                $val = $mergeBlueprint->params[$key];
            }

            // retain the merged value
            $params[$key] = $val;

            // next position
            $pos += 1;
        }

        return $params;
    }

    /**
     * Expands variadic parameters onto the end of a contructor parameters array.
     */
    private function expandParams(): array
    {
        $params = $this->getParams();

        $variadicParams = [];
        foreach ($this->paramSettings as $paramName => $isVariadic) {
            if ($isVariadic && is_array($params[$paramName])) {
                $variadicParams = array_merge($variadicParams, $params[$paramName]);
                unset($params[$paramName]);
                break; // There can only be one
            }

            if ($params[$paramName] instanceof DefaultValueParam) {
                $params[$paramName] = $params[$paramName]->getValue();
            }
        }

        return array_merge($params, array_values($variadicParams));
    }
}