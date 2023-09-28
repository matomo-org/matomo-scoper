<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Composer;

class ComposerJson
{
    private array $composerJsonContents;

    public function __construct(array $composerJsonContents)
    {
        $this->composerJsonContents = $composerJsonContents;
    }

    public function setComposerJsonContents(array $composerJsonContents): void
    {
        $this->composerJsonContents = $composerJsonContents;
    }

    public function writeTo(string $composerJsonPath): void
    {
        $composerJsonContents = json_encode($this->composerJsonContents, JSON_PRETTY_PRINT);
        if (!is_dir(dirname($composerJsonPath))) {
            mkdir(dirname($composerJsonPath), 0777, true);
        }
        file_put_contents($composerJsonPath, $composerJsonContents);
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
     * @return ComposerJson[]
     */
    public function getRequires(ComposerLock $lockFile): array // TODO: swap method name w/ getAllTopLevelDependencies()
    {
        $dependencies = $this->getAllTopLevelDependencies();
        $dependencies = array_map(function ($dependencyName) use ($lockFile) {
            return $lockFile->getDependency($dependencyName);
        }, $dependencies);
        $dependencies = array_filter($dependencies);
        $dependencies = array_values($dependencies);
        return $dependencies;
    }

    public function getName(): string
    {
        return $this->composerJsonContents['name'];
    }

    public function getAutoloadFiles(): array
    {
        return $this->composerJsonContents['autoload']['files'] ?? [];
    }

    public function getComposerJsonContents(): ?array
    {
        return $this->composerJsonContents;
    }

    public function getAllTopLevelDependencies(): array
    {
        $dependencies = array_keys($this->composerJsonContents['require'] ?? []);
        $dependencies = array_filter($dependencies, function ($name) {
            return $name !== 'php' && strpos($name, 'ext-') !== 0;
        });
        return $dependencies;
    }

    public function getAllReplacedDependencies(): array
    {
        return array_keys($this->composerJsonContents['replace'] ?? []);
    }
}
