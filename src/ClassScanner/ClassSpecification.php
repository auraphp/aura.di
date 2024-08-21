<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class ClassSpecification
{
    private string $className;
    private string $filename;
    /**
     * @param array<int, AttributeSpecification> $attributes
     */
    private array $attributes;

    /**
     * @param array<int, AttributeSpecification> $attributes
     */
    public function __construct(
        string $className,
        string $filename,
        array $attributes = [],
    )
    {
        $this->className = $className;
        $this->filename = $filename;
        $this->attributes = $attributes;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return array<int, AttributeSpecification>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function isAttributeClass(): bool
    {
        foreach ($this->attributes as $specification) {
            if ($specification->getAttributeInstance() instanceof \Attribute) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, AttributeSpecification>
     */
    public function getClassAttributes(): array
    {
        return \array_filter(
            $this->attributes,
            function (AttributeSpecification $specification) {
                return $specification->getAttributeTarget() === \Attribute::TARGET_CLASS;
            }
        );
    }

    /**
     * @return array<int, AttributeSpecification>
     */
    public function getParameterAttributesForMethod(string $methodName): array
    {
        return \array_filter(
            $this->attributes,
            function (AttributeSpecification $specification) use ($methodName) {
                return $specification->getTargetMethod() === $methodName;
            }
        );
    }
}