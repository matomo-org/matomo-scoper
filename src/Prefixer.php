<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

use Matomo\Scoper\Composer\ComposerProject;
use Matomo\Scoper\ShellCommands\PhpScoper;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class Prefixer
{
    protected readonly ComposerProject $composerProject;

    /**
     * @var ?string[]
     */
    protected ?array $dependenciesToPrefix = null;

    /**
     * @var string[]
     */
    protected array $dependenciesToIgnore = [];

    public function __construct(protected readonly Paths $paths, protected readonly Filesystem $filesystem, protected readonly OutputInterface $output)
    {
        $this->composerProject = new ComposerProject($this->paths->getRepoPath(), $this->filesystem);
    }

    public function run(): array
    {
        if (empty($this->dependenciesToPrefix)) {
            return [];
        }

        list($dependenciesToPrefix, $namespacesToInclude) = $this->collectChildDependencies();

        $this->scopeDependencies($dependenciesToPrefix, $namespacesToInclude);
        $this->renameReferencesToScopedDependencies($dependenciesToPrefix, $namespacesToInclude);

        return $dependenciesToPrefix;
    }

    private function removePrefixedDependencies(array $dependenciesToPrefix): void
    {
        $vendorPath = $this->paths->getRepoPath() . '/vendor/';
        foreach ($dependenciesToPrefix as $dependencyPath) {
            $this->filesystem->remove([$vendorPath . $dependencyPath]);

            $orgPath = dirname($vendorPath . $dependencyPath);

            $orgPathContents = scandir($orgPath);
            $orgPathContents = array_filter($orgPathContents, function ($c) { return $c !== '.' && $c !== '..'; });
            if (empty($orgPathContents)) {
                $this->filesystem->remove([$orgPath]);
            }
        }
    }

    private function collectChildDependencies(): array
    {
        $allDependencies = $this->composerProject->getComposerLock()->getFlatDependencyTreeFor($this->dependenciesToPrefix, $this->dependenciesToIgnore);

        $allDependenciesToPrefix = [];
        $allNamespacesToInclude = [];

        foreach ($allDependencies as $dependency) {
            $allDependenciesToPrefix[] = $dependency->getName();
            $allNamespacesToInclude = array_merge($allNamespacesToInclude, $dependency->getNamespaces());
        }

        return [$allDependenciesToPrefix, $allNamespacesToInclude];
    }

    private function scopeDependencies(array $dependenciesToPrefix, array $namespacesToInclude): void
    {
        $this->output->writeln("<info>  Scoping vendor...</info>");

        $command = new PhpScoper($this->paths, $this->output, $dependenciesToPrefix, $namespacesToInclude);
        $command->setPlugin($this->getPluginNameIfAny());
        $command->passthru();

        $this->removePrefixedDependencies($dependenciesToPrefix);
    }

    private function renameReferencesToScopedDependencies(array $dependenciesToPrefix, array $namespacesToInclude): void
    {
        // rename dependencies in rest of project
        $this->output->writeln("<info>  Scoping references in rest of project...</info>");
        $command = new PhpScoper($this->paths, $this->output, $dependenciesToPrefix, $namespacesToInclude);
        $command->setPlugin($this->getPluginNameIfAny());
        $command->renameReferences(true);
        $command->passthru();
    }

    private function getPluginNameIfAny(): ?string
    {
        try {
            return (new PluginDetails($this->paths->getRepoPath()))->getPluginName();
        } catch (\Exception $ex) {
            return null;
        }
    }
}
