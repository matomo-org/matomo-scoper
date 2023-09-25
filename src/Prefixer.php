<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

use Matomo\Scoper\Composer\ComposerDependency;
use Matomo\Scoper\Composer\ComposerProject;
use Matomo\Scoper\ShellCommands\PhpScoper;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class Prefixer
{
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

        // scope dependencies in vendor/
        $this->output->writeln("<info>  Scoping vendor...</info>");
        $command = new PhpScoper($this->paths, $this->output, $dependenciesToPrefix, $namespacesToInclude);
        $command->passthru();

        $this->removePrefixedDependencies($dependenciesToPrefix);

        // create recursive symlink to directory to trick php scoper into replacing files in-place
        // Note: we should be using a patched php-scoper that does not delete the output directory, but in
        // case we aren't, using this symlink approach means we won't accidentally delete the entire repo
        // we're trying to prefix.
        $buildPath = $this->paths->getRepoPath() . '/build';
        if (!is_dir($buildPath)) {
            symlink($this->paths->getRepoPath(), $buildPath);
        } else if (!is_link($buildPath)) {
            $this->output->writeln("<warning>  Warning: ./build dir exists and is not a symlink</warning>");
        }

        // rename dependencies in rest of project
        $this->output->writeln("<info>  Scoping references in rest of project...</info>");
        $command = new PhpScoper($this->paths, $this->output, $dependenciesToPrefix, $namespacesToInclude);
        $command->renameReferences(true);
        $command->passthru();

        return $dependenciesToPrefix;
    }

    private function removePrefixedDependencies(array $dependenciesToPrefix): void
    {
        $vendorPath = $this->paths->getRepoPath() . '/vendor/';
        foreach ($dependenciesToPrefix as $dependencyPath) {
            $this->filesystem->remove([$vendorPath . $dependencyPath, dirname($vendorPath . $dependencyPath)]);
        }
    }

    private function collectChildDependencies(): array
    {
        $composerProject = new ComposerProject($this->paths->getRepoPath(), $this->filesystem);
        $allDependencies = $composerProject->getFlatDependencyTreeFor($this->dependenciesToPrefix, $this->dependenciesToIgnore);

        $allDependenciesToPrefix = [];
        $allNamespacesToInclude = [];

        foreach ($allDependencies as $dependency) {
            $allDependenciesToPrefix[] = $dependency->getRelativeDependencyPath();
            $allNamespacesToInclude = array_merge($allNamespacesToInclude, $dependency->getNamespaces());
        }

        return [$allDependenciesToPrefix, $allNamespacesToInclude];
    }
}
