<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\Framework;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class ComposerTestCase extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeTestProject();
    }

    protected function setUpTestProject(?array $composerJsonContents, array $dependencies, ?array $dependencyComposerJsonContents): string
    {
        $this->removeTestProject();

        $rootPath = $this->getTestProjectRootPath();

        if ($composerJsonContents !== null) {
            mkdir($rootPath, 0777, true);
            file_put_contents($rootPath . '/composer.json', json_encode($composerJsonContents));
        }

        foreach ($dependencies as $dependency) {
            mkdir($rootPath . '/vendor/' . $dependency, 0777, true);
            if ($dependencyComposerJsonContents != null) {
                file_put_contents($rootPath . '/vendor/' . $dependency . '/composer.json', json_encode($dependencyComposerJsonContents));
            }
        }

        return $rootPath;
    }

    protected function removeTestProject(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->getTestProjectRootPath());
    }

    protected function getTestProjectRootPath(): string
    {
        return __DIR__ . '/test-project';
    }

    protected function putTestProjectFile(string $relativePath, string $contents)
    {
        $path = $this->getTestProjectRootPath() . '/' . $relativePath;
        @mkdir(dirname($path), 0777, true);
        $this->assertNotFalse(file_put_contents($path, $contents));
    }
}
