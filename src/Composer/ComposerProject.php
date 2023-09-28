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
    private ComposerLock $composerLock;

    private ComposerJson $composerJson;

    public function __construct(private readonly string $path, private readonly Filesystem $filesystem)
    {
        $this->composerLock = new ComposerLock($this->readFile($path . '/composer.lock'));
        $this->composerJson = new ComposerJson($this->readFile($path . '/composer.json'));
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getComposerJson(): ComposerJson
    {
        return $this->composerJson;
    }

    public function getComposerLock(): ComposerLock
    {
        return $this->composerLock;
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
            if (!preg_match("/'(?:\/..)+'\s+\.\s+'(.*?)'/", $line, $matches)) {
                unset($autoloadFiles[$key]);
                continue;
            }

            $relativePath = $matches[1];
            if (!$this->filesystem->exists($this->path . '/vendor' . $relativePath)
                && !$this->filesystem->exists($this->path . $relativePath)
            ) { // dependency was prefixed
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

                $dependency = $this->getDependency($folder . '/' . $subfolder);
                if (!$dependency) {
                    continue;
                }

                $composerJsonContents = $dependency->getComposerJsonContents();

                $autoload = $composerJsonContents['autoload'] ?? [];
                unset($composerJsonContents['autoload']);

                $dependency->setComposerJsonContents($composerJsonContents);
                $dependency->writeTo(sprintf('%s/%s/%s/composer.json', $this->path, $folder, $subfolder, 'composer.json'));

                foreach ($autoload['classmap'] ?? [] as $classmapFolder) {
                    mkdir($this->path . '/vendor/' . $dependency->getName() . '/' . $classmapFolder, 0777, true);
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

    public function getDependency(string $dependencyName): ?ComposerJson
    {
        return $this->composerLock->getDependency($dependencyName);
    }

    private function readFile(string $path): array
    {
        if (!is_file($path)) {
            throw new \Exception('Cannot open composer file at ' . $path);
        }

        return json_decode(file_get_contents($path), true);
    }
}
