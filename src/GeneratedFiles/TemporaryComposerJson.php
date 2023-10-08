<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\GeneratedFiles;

use Matomo\Scoper\Composer\ComposerJson;
use Matomo\Scoper\Composer\ComposerProject;
use Matomo\Scoper\GeneratedFile;

/**
 * TODO: document
 */
class TemporaryComposerJson extends GeneratedFile
{

    public function __construct(private readonly array $prefixedDependencies, private readonly ComposerProject $composerProject)
    {
        parent::__construct($this->composerProject->getPath() . '/vendor/prefixed/composer.json');
    }

    public function getContent(): ?string
    {
        return json_encode([
            'autoload' => [
                'classmap' => array_merge($this->getMergedClassmapEntry(), ['']),
                'files' => $this->getFilesToAutoload(),
            ],
        ]);
    }

    private function getFilesToAutoload(): array
    {
        $files = [];
        foreach ($this->prefixedDependencies as $dependencyPath) {
            $dependency = $this->composerProject->getDependency($dependencyPath);
            if (!$dependency) {
                continue;
            }

            $dependencyFiles = $dependency->getAutoloadFiles();
            $dependencyFiles = array_map(function ($p) use ($dependencyPath) { return $dependencyPath . '/' . $p; }, $dependencyFiles);

            $files = array_merge($files, $dependencyFiles);
        }
        return $files;
    }

    private function getMergedClassmapEntry(): array
    {
        // TODO: code redundancy w/ above
        $classmap = [];
        foreach ($this->prefixedDependencies as $dependencyPath) {
            $dependency = $this->composerProject->getDependency($dependencyPath);
            if (!$dependency) {
                continue;
            }

            $dependencyFiles = $dependency->getAutoloadClassmap();
            $dependencyFiles = array_map(function ($p) use ($dependencyPath) { return $dependencyPath . '/' . $p; }, $dependencyFiles);

            $classmap = array_merge($classmap, $dependencyFiles);
        }
        return $classmap;
    }
}
