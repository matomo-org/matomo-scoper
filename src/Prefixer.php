<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

use Matomo\Scoper\Composer\ComposerDependency;
use Matomo\Scoper\ShellCommands\PhpScoper;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class Prefixer
{
    // TODO: document variables

    protected Paths $paths;

    protected Filesystem $filesystem;

    protected OutputInterface $output;

    /**
     * @var ?string[]
     */
    protected ?array $dependenciesToPrefix = null;

    /**
     * @var string[]
     */
    protected array $dependenciesToIgnore = [];

    public function __construct(Paths $paths, Filesystem $filesystem, OutputInterface $output)
    {
        $this->paths = $paths;
        $this->filesystem = $filesystem;
        $this->output = $output;
    }

    public function run(): array
    {
        if (empty($this->dependenciesToPrefix)) {
            return [];
        }

        list($dependenciesToPrefix, $namespacesToInclude) = $this->collectChildDependencies();

        $command = new PhpScoper($this->paths, $this->output, $dependenciesToPrefix, $namespacesToInclude);
        $command->passthru();

        return $dependenciesToPrefix;
    }

    private function collectChildDependencies(): array
    {
        $allDependenciesToPrefix = $this->dependenciesToPrefix;
        $allNamespacesToInclude = [];

        $dependenciesToProcess = $this->dependenciesToPrefix;
        while (!empty($dependenciesToProcess)) {
            $dependencySlug = array_shift($dependenciesToProcess);

            $dependency = new ComposerDependency($this->paths->getRepoPath(), $dependencySlug);
            if (!$dependency->hasComposerJson()) {
                continue;
            }

            $allNamespacesToInclude = array_merge($allNamespacesToInclude, $dependency->getNamespaces());

            $childDependencies = $dependency->getRequires();
            foreach ($childDependencies as $childDep) {
                $id = $childDep->getRelativeDependencyPath();

                if (!$childDep->hasComposerJson()
                    || in_array($id, $allDependenciesToPrefix)
                    || in_array($id, $this->dependenciesToIgnore)
                ) {
                    continue;
                }

                $allDependenciesToPrefix[] = $id;
                $dependenciesToProcess[] = $id;
            }
        }

        return [$allDependenciesToPrefix, $allNamespacesToInclude];
    }
}
