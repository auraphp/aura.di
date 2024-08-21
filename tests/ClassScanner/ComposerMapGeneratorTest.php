<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use PHPUnit\Framework\TestCase;

class ComposerMapGeneratorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = \sys_get_temp_dir() . '/aura_di_' . \bin2hex(random_bytes(4));
        \mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $files = new \FilesystemIterator($this->dir);

        foreach ($files as $fileinfo) {
            \unlink($fileinfo->getPathname());
        }

        \rmdir($this->dir);
    }

    public function testAddingClass()
    {
        $generator = new ComposerMapGenerator([__DIR__ . '/../Fake', $this->dir]);

        $classSuffix = \bin2hex(\random_bytes(4));
        $newClassName = 'CacheTest\\NewFile' . $classSuffix;

        $classMap = $generator->generate();
        $this->assertNotContains($newClassName, $classMap->getClasses());

        $newFile = $this->createRandomClassFile($newClassName);

        $classMap2 = $generator->update($classMap, [$newFile]);
        $this->assertContains($newClassName, $classMap2->getClasses());
        $this->assertCount(1, $classMap2->getClassSpecificationFor($newClassName)->getAttributes());
    }

    public function testRemovingClass()
    {
        $generator = new ComposerMapGenerator([
            __DIR__ . '/../Fake',
            $this->dir
        ]);

        $classSuffix = \bin2hex(\random_bytes(4));
        $newClassName = 'CacheTest\\NewFile' . $classSuffix;
        $this->createRandomClassFile($newClassName);

        $classMap = $generator->generate();
        $this->assertContains($newClassName, $classMap->getClasses());

        \unlink($this->dir . '/NewFile' . $classSuffix . '.php');

        $classMap2 = $generator->update($classMap, [$this->dir . '/NewFile' . $classSuffix . '.php']);
        $this->assertNotContains($newClassName, $classMap2->getClasses());
    }

    private function createRandomClassFile(string $className, int $value = 0): string
    {
        $bareName = \array_reverse(\explode('\\', $className))[0];
        $phpFile = $this->dir . '/' . $bareName . '.php';
        $phpClass = "<?php\nnamespace CacheTest;\nuse Aura\Di\Attribute\Service;use Aura\Di\Fake\FakeAttribute;class {$bareName} { public function __construct(#[FakeAttribute({$value})] \$var) { } }";

        file_put_contents($phpFile, $phpClass);
        require_once $phpFile;
        return $phpFile;
    }
}
