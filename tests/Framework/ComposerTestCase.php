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

        if (!is_dir($rootPath)) {
            mkdir($rootPath, 0777, true);
        }

        if ($composerJsonContents !== null) {
            file_put_contents($rootPath . '/composer.json', json_encode($composerJsonContents));
        }

        if ($dependencyComposerJsonContents !== null) {
            $composerLockContents = [
                'packages' => [],
            ];

            foreach ($dependencies as $dependency) {
                $dependencyPath = $rootPath . '/vendor/' . $dependency;
                if (!is_dir($dependencyPath)) {
                    mkdir($dependencyPath, 0777, true);
                }

                $composerLockContents['packages'][] = array_merge($dependencyComposerJsonContents, [
                    'name' => $dependency,
                ]);
            }

            file_put_contents($rootPath . '/composer.lock', json_encode($composerLockContents));
        }

        return $rootPath;
    }

    protected function setComposerJsonContents(string $dependency, array $contents): void
    {
        $rootPath = $this->getTestProjectRootPath();

        $composerLockPath = $rootPath . '/composer.lock';

        $composerLockContents = [
            'packages' => [],
        ];

        if (is_file($composerLockPath)) {
            $composerLockContents = json_decode(file_get_contents($composerLockPath), true);
        }

        $composerLockContents['packages'] = array_filter($composerLockContents['packages'], function ($dependencyInfo) use ($dependency) {
            return $dependencyInfo['name'] !== $dependency;
        });

        $composerLockContents['packages'][] = array_merge($contents, ['name' => $dependency]);

        $this->putTestProjectFile('composer.lock', json_encode($composerLockContents));
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
