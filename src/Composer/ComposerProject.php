<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Composer;

use Symfony\Component\Filesystem\Filesystem;

class ComposerProject
{
    private string $path;

    private Filesystem $filesystem;

    public function __construct(string $path, Filesystem $filesystem)
    {
        $this->path = $path;
        $this->filesystem = $filesystem;
    }

    /**
     * TODO: document
     *
     * TODO: this could be moved to a new AutoloadStatic class
     *
     * @return string
     */
    public function getUnprefixedAutoloadFiles(): ?string
    {
        $autoloadStatic = $this->getPathToAutoloadStaticFile();
        $autoloadStaticContents = file_get_contents($autoloadStatic);

        if (empty($autoloadStaticContents)) {
            return null;
        }

        preg_match('/public static \$files.*?;/s', $autoloadStaticContents, $matches);
        $autoloadFiles = $matches[0];

        $autoloadFiles = preg_split('/[,()\[\]]+\s*/', $autoloadFiles);
        foreach ($autoloadFiles as $key => $line) {
            if (!preg_match("/'\/..'\s+\.\s+'(.*?)'/", $line, $matches)) {
                unset($autoloadFiles[$key]);
                continue;
            }

            $relativePath = $matches[1];
            if (!$this->filesystem->exists($this->path . '/vendor' . $relativePath)) { // dependency was prefixed
                unset($autoloadFiles[$key]);
            }
        }

        $autoloadFiles = implode(",\n", $autoloadFiles);
        $autoloadFiles = "public static \$files = array(\n" . $autoloadFiles . ");\n";
        return $autoloadFiles;
    }

    /**
     * TODO: document
     *
     * adds dummy composer.json file for prefixed dependencies, required since dumping the autoloader w/ those files
     * missing, while their associated dependency is missing, causes composer to fail
     *
     *
     */
    public function createDummyComposerJsonFilesForPrefixedDeps(): void
    {
        $prefixedPath = $this->path . '/vendor/prefixed';

        foreach (scandir($prefixedPath) as $folder) {
            if ($folder == '.' || $folder == '..') {
                continue;
            }

            foreach (scandir($prefixedPath . '/' . $folder) as $subfolder) {
                if ($subfolder == '.' || $subfolder == '..') {
                    continue;
                }

                $dependency = new ComposerDependency($this->path, 'prefixed/' . $folder . '/' . $subfolder);
                if (!$dependency->hasComposerJson()) {
                    continue;
                }

                $composerJsonContents = $dependency->getComposerJsonContents();

                $autoload = $composerJsonContents['autoload'];
                unset($composerJsonContents['autoload']);

                $unprefixedDependency = new ComposerDependency($this->path, $folder . '/' . $subfolder);
                $unprefixedDependency->writeComposerJsonContents($composerJsonContents);

                foreach ($autoload['classmap'] ?? [] as $classmapFolder) {
                    mkdir($unprefixedDependency->getDependencyPath() . '/' . $classmapFolder, 0777, true);
                }
            }
        }
    }

    /**
     * TODO
     * @return void
     */
    public function removeDummyComposerJsonFilesForPrefixedDeps(): void
    {
        $vendorPath = $this->path . '/vendor/';
        $prefixedPath = $vendorPath . 'prefixed';

        foreach (scandir($prefixedPath) as $folder) { // TODO: refactor for loops
            if ($folder == '.' || $folder == '..') {
                continue;
            }

            foreach (scandir($prefixedPath . '/' . $folder) as $subfolder) {
                if ($subfolder == '.' || $subfolder == '..') {
                    continue;
                }

                $tempUnprefixedPath = $vendorPath . $folder . '/' . $subfolder;
                $this->filesystem->remove($tempUnprefixedPath);
            }
        }
    }

    /**
     * TODO document
     * @return void
     */
    public function replaceStaticAutoloadFiles(string $unprefixedAutoloadFiles): void
    {
        $autoloadStatic = $this->path . '/vendor/composer/autoload_static.php';
        $autoloadStaticContents = file_get_contents($autoloadStatic);
        $autoloadStaticContents = preg_replace('/public static \$files.*?;/s', $unprefixedAutoloadFiles, $autoloadStaticContents);
        file_put_contents($autoloadStatic, $autoloadStaticContents);
    }

    private function getPathToAutoloadStaticFile(): string
    {
        return $this->path . '/vendor/composer/autoload_static.php';
    }

    /**
     * @param string[] $dependenciesToPrefix
     * @param string[] $dependenciesToIgnore
     * @return ComposerDependency[]
     */
    public function getFlatDependencyTreeFor(array $dependenciesToPrefix, array $dependenciesToIgnore = []): array
    {
        $flatDependencyTree = [];

        $dependenciesToProcess = array_map(function ($relativePath) {
            return new ComposerDependency($this->path, $relativePath);
        }, $dependenciesToPrefix);

        while (!empty($dependenciesToProcess)) {
            $dependency = array_shift($dependenciesToProcess);
            $flatDependencyTree[$dependency->getRelativeDependencyPath()] = $dependency;

            if (!$dependency->hasComposerJson()) {
                continue;
            }

            $childDependencies = $dependency->getRequires();
            foreach ($childDependencies as $childDep) {
                $id = $childDep->getRelativeDependencyPath();

                $alreadyProcessed = !empty($flatDependencyTree[$id]);
                if ($alreadyProcessed
                    || in_array($id, $dependenciesToIgnore)
                ) {
                    continue;
                }

                $dependenciesToProcess[] = $childDep;
            }
        }

        return array_values($flatDependencyTree);
    }
}
