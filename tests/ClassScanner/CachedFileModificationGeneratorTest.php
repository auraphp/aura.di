<?php

declare(strict_types=1);

namespace Aura\Di\ClassScanner;

use PHPUnit\Framework\TestCase;

class CachedFileModificationGeneratorTest extends TestCase
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
        $cacheFile = $this->dir . '/cache_class_map.json';

        $cachedFileModificationGenerator = new CachedFileModificationGenerator(
            new ComposerMapGenerator([
                __DIR__ . '/../Fake',
                $this->dir
            ]),
            $cacheFile,
            0
        );

        $classSuffix = \bin2hex(\random_bytes(4));
        $newClassName = 'CacheTest\\NewFile' . $classSuffix;

        $classMap = $cachedFileModificationGenerator->generate();
        $this->assertNotContains($newClassName, $classMap->getClasses());

        $this->createRandomClassFile($newClassName);

        $classMap2 = $cachedFileModificationGenerator->generate();
        $this->assertContains($newClassName, $classMap2->getClasses());
        $this->assertCount(1, $classMap2->getTargetedAttributesFor($newClassName));
    }

    public function testDebounce()
    {
        $cacheFile = $this->dir . '/cache_class_map.json';

        $cachedFileModificationGenerator = new CachedFileModificationGenerator(
            new ComposerMapGenerator([
                __DIR__ . '/../Fake',
                $this->dir
            ]),
            $cacheFile,
            1
        );

        $classSuffix = \bin2hex(\random_bytes(4));
        $newClassName = 'CacheTest\\NewFile' . $classSuffix;

        $classMap = $cachedFileModificationGenerator->generate();
        $this->assertNotContains($newClassName, $classMap->getClasses());

        $this->createRandomClassFile($newClassName);

        $classMap2 = $cachedFileModificationGenerator->generate();
        $this->assertNotContains($newClassName, $classMap2->getClasses());

        \sleep(1);

        $classMap3 = $cachedFileModificationGenerator->generate();
        $this->assertContains($newClassName, $classMap3->getClasses());
        $this->assertCount(1, $classMap3->getTargetedAttributesFor($newClassName));
    }

    public function testUpdatingClass()
    {
        $cacheFile = $this->dir . '/cache_class_map.json';

        $cachedFileModificationGenerator = new CachedFileModificationGenerator(
            new ComposerMapGenerator([
                __DIR__ . '/../Fake',
                $this->dir
            ]),
            $cacheFile,
            0
        );

        $classSuffix = \bin2hex(\random_bytes(4));
        $newClassName = 'CacheTest\\NewFile' . $classSuffix;
        $this->createRandomClassFile($newClassName);

        $classMap = $cachedFileModificationGenerator->generate();
        $this->assertContains($newClassName, $classMap->getClasses());

        $beforeTouchTime = \json_decode(\file_get_contents($cacheFile), true)['filetimes'][$this->dir . '/NewFile' . $classSuffix . '.php'];

        \touch($this->dir . '/NewFile' . $classSuffix . '.php', $beforeTouchTime + 1);

        $classMap2 = $cachedFileModificationGenerator->generate();
        $this->assertContains($newClassName, $classMap2->getClasses());
        $this->assertCount(1, $classMap2->getTargetedAttributesFor($newClassName));

        $afterTouchTime = \json_decode(\file_get_contents($cacheFile), true)['filetimes'][$this->dir . '/NewFile' . $classSuffix . '.php'];
        $this->assertNotSame($beforeTouchTime, $afterTouchTime);
    }

    public function testRemovingClass()
    {
        $cacheFile = $this->dir . '/cache_class_map.json';

        $cachedFileModificationGenerator = new CachedFileModificationGenerator(
            new ComposerMapGenerator([
                __DIR__ . '/../Fake',
                $this->dir
            ]),
            $cacheFile,
            0
        );

        $classSuffix = \bin2hex(\random_bytes(4));
        $newClassName = 'CacheTest\\NewFile' . $classSuffix;
        $this->createRandomClassFile($newClassName);

        $classMap = $cachedFileModificationGenerator->generate();
        $this->assertContains($newClassName, $classMap->getClasses());

        \unlink($this->dir . '/NewFile' . $classSuffix . '.php');

        $classMap2 = $cachedFileModificationGenerator->generate();
        $this->assertNotContains($newClassName, $classMap2->getClasses());
    }

    private function createRandomClassFile(string $className): void
    {
        $bareName = \array_reverse(\explode('\\', $className))[0];
        $phpFile = $this->dir . '/' . $bareName . '.php';
        $phpClass = "<?php\nnamespace CacheTest;\nuse Aura\Di\Attribute\Service;class {$bareName} { public function __construct(#[Service('test')] \$var) { } }";

        file_put_contents($phpFile, $phpClass);
        require_once $phpFile;
    }
}
