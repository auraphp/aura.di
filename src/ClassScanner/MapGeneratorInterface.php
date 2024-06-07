<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

interface MapGeneratorInterface
{
    public function generate(): ClassMap;
}