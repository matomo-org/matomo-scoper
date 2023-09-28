<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Composer;

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
        $this->composerJsonContents = $composerJsonContents;

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
        //
        // this only works where the package is just a couple nested folders and a composer.json file. in all other
        // cases, it is expected this won't work.
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
        $dependencies = self::getRequireEntriesFromComposerJson($this->composerJsonContents);
        $dependencies = array_map(function ($dependencySlug) {
            return new ComposerDependency($this->rootPath, $dependencySlug);
        }, $dependencies);
        $dependencies = array_values($dependencies);
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

    // TODO: unit test
    public static function getRequireEntriesFromComposerJson(array $contents): array
    {
        $dependencies = array_keys($contents['require'] ?? []);
        $dependencies = array_filter($dependencies, function ($name) {
            return $name !== 'php' && strpos($name, 'ext-') !== 0;
        });
        return $dependencies;
    }
}
