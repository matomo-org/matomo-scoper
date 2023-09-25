<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\GeneratedFiles;

use Matomo\Scoper\Composer\ComposerDependency;
use Matomo\Scoper\GeneratedFile;

/**
 * TODO: document
 */
class TemporaryComposerJson extends GeneratedFile
{
    /**
     * @var string
     */
    private string $repoPath;

    /**
     * @var string[]
     */
    private array $prefixedDependencies;

    public function __construct(string $repoPath, array $prefixedDependencies)
    {
        parent::__construct($repoPath . '/vendor/prefixed/composer.json');
        $this->repoPath = $repoPath;
        $this->prefixedDependencies = $prefixedDependencies;
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
            $dependency = new ComposerDependency($this->repoPath, 'prefixed/' . $dependencyPath);
            if (!$dependency->hasComposerJson()) {
                continue;
            }

            $dependencyFiles = $dependency->getAutoloadFiles();
            $dependencyFiles = array_map(function ($p) use ($dependencyPath) { return $dependencyPath . '/' . $p; }, $dependencyFiles);

            $files = array_merge($files, $dependencyFiles);
        }
        return $files;
    }
}
