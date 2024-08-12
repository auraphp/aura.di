<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

final class StaticFileGenerator implements MapGeneratorInterface
{
    private string $filename;

    public function __construct(string $filename) {
        $this->filename = $filename;
    }

    /**
     * @throws \JsonException
     */
    public function generate(): ClassMap
    {
        return ClassMap::fromFile($this->filename);
    }

    public function update(ClassMap $classMap, array $updatedFiles): ClassMap
    {
        throw new \RuntimeException('StaticFileGenerator does not support updating, it is static');
    }
}
