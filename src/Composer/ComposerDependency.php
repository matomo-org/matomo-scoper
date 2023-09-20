<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Composer;

use Matomo\Scoper\Utilities\Paths;

class ComposerDependency
{
    private string $rootPath;

    private string $dependencyPath;

    private ?array $composerJsonContents;

    public function __construct(string $rootPath, string $dependencyPath)
    {
        $this->rootPath = $rootPath;
        $this->dependencyPath = $dependencyPath;
        $this->composerJsonContents = $this->readComposerJsonContents();
    }

    public function hasComposerJson(): bool
    {
        return isset($this->composerJsonContents);
    }

    public function getDependencyPath(): string
    {
        return $this->rootPath . '/vendor/' . $this->dependencyPath;
    }

    private function readComposerJsonContents(): ?array
    {
        $dependencyComposerJson = $this->getComposerJsonPath();
        if (empty($dependencyComposerJson)
            || !is_file($dependencyComposerJson)
        ) {
            return null;
        }

        $dependencyComposerJson = json_decode(file_get_contents($dependencyComposerJson), true);
        return $dependencyComposerJson;
    }

    public function writeComposerJsonContents(?array $composerJsonContents): void
    {
        $composerJsonPath = $this->rootPath . '/vendor/' . $this->dependencyPath . '/composer.json';

        $composerJsonContents = json_encode($composerJsonContents, JSON_PRETTY_PRINT);

        if (!is_dir(dirname($composerJsonPath))) {
            mkdir(dirname($composerJsonPath), 0777, true);
        }
        file_put_contents($composerJsonPath, $composerJsonContents);
    }

    public function getComposerJsonPath(): ?string
    {
        // some composer dependencies *cough*Symfony*cough* do not have a root composer.json file. instead it's nested
        // in some folders. we try to detect those here.
        // TODO: maybe we should recurse through every folder and use the first one instead of just using the first?
        $path = $this->getDependencyPath();
        if (!is_dir($path)) {
            return null;
        }

        while (!is_file($path . '/composer.json')) {
            $contents = scandir($path);
            $contents = array_filter($contents, function ($p) use ($path) { return $p != '.' && $p != '..' && is_dir($path . '/' . $p); });
            if (count($contents) !== 1) {
                return null;
            }

            $path = $path . '/' . reset($contents);
        }
        return $path . '/composer.json';
    }

    public function getNamespaces(): array
    {
        $dependencyComposerJson = $this->composerJsonContents;

        $namespaces = [];
        if (!empty($dependencyComposerJson['autoload']['psr-4'])) { // only handling psr-4 and psr-0 for now
            $namespaces = array_merge(
                $namespaces,
                array_keys($dependencyComposerJson['autoload']['psr-4'])
            );
        }
        if (!empty($dependencyComposerJson['autoload']['psr-0'])) {
            $namespaces = array_merge(
                $namespaces,
                array_keys($dependencyComposerJson['autoload']['psr-0'])
            );
        }
        return $namespaces;
    }

    /**
     * @return ComposerDependency[]
     */
    public function getRequires(): array
    {
        $dependencies = array_keys($this->composerJsonContents['require'] ?? []);
        $dependencies = array_filter($dependencies, function ($name) {
            return $name !== 'php';
        });
        $dependencies = array_map(function ($dependencySlug) {
            return new ComposerDependency($this->rootPath, $dependencySlug);
        }, $dependencies);
        return $dependencies;
    }

    public function getRelativeDependencyPath(): string
    {
        return $this->dependencyPath;
    }

    public function getAutoloadFiles(): array
    {
        return $this->composerJsonContents['autoload']['files'] ?? [];
    }

    public function getComposerJsonContents(): ?array
    {
        return $this->composerJsonContents;
    }
}
