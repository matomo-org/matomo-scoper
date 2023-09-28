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
                'classmap' => [''],
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
}
