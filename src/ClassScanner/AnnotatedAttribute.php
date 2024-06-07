<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class AnnotatedAttribute
{
    private object $attributeInstance;
    private string $className;
    private int $attributeTarget;
    private array $targetConfig;

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

    public function getTargetConfig(): array
    {
        return $this->targetConfig;
    }
}