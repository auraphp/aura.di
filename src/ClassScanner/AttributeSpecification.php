<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class AttributeSpecification
{
    private object $attributeInstance;
    private string $className;
    private int $attributeTarget;
    /**
     * @param array{method?: string, parameter?: string, property?: string, constant?: string} $targetConfig
     */
    private array $targetConfig;

    /**
     * @param array{method?: string, parameter?: string, property?: string, constant?: string} $targetConfig
     */
    public function __construct(
        object $attributeInstance,
        string $className,
        int $attributeTarget,
        array $targetConfig = [],
    )
    {
        $this->attributeInstance = $attributeInstance;
        $this->className = $className;
        $this->attributeTarget = $attributeTarget;
        $this->targetConfig = $targetConfig;
    }

    public function getAttributeInstance(): object
    {
        return $this->attributeInstance;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getAttributeTarget(): int
    {
        return $this->attributeTarget;
    }

    public function getTargetMethod(): ?string
    {
        return $this->targetConfig['method'] ?? null;
    }

    public function getTargetParameter(): ?string
    {
        return $this->targetConfig['parameter'] ?? null;
    }

    public function getTargetProperty(): ?string
    {
        return $this->targetConfig['property'] ?? null;
    }

    public function getTargetConstant(): ?string
    {
        return $this->targetConfig['constant'] ?? null;
    }
}