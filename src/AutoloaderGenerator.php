<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

use Matomo\Scoper\Composer\ComposerProject;
use Matomo\Scoper\GeneratedFiles\ProxyAutoloader;
use Matomo\Scoper\GeneratedFiles\TemporaryComposerJson;
use Matomo\Scoper\ShellCommands\DumpAutoload;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class AutoloaderGenerator
{
    private Paths $paths;

    private Filesystem $filesystem;

    private OutputInterface $output;

    private array $prefixedDependencies;

    public function __construct(Paths $paths, Filesystem $filesystem, OutputInterface $output, array $prefixedDependencies)
    {
        $this->paths = $paths;
        $this->filesystem = $filesystem;
        $this->output = $output;
        $this->prefixedDependencies = $prefixedDependencies;
    }

    public function generate(): void
    {
        $this->generateAutoloaderForPrefixedDeps();
        $this->regenerateAutoloaderForUnprefixedDeps();

        $this->output->writeln("Generated proxy autoload.php.");
    }

    private function generateAutoloaderForPrefixedDeps(): void
    {
        $repoPath = $this->paths->getRepoPath();

        $this->output->writeln("Generating prefixed autoloader...");
        $tempComposerJson = new TemporaryComposerJson($repoPath, $this->prefixedDependencies);
        $tempComposerJson->write();

        $dumpAutoload = new DumpAutoload($this->paths, $this->output, $repoPath . '/vendor/prefixed');
        $dumpAutoload->passthru();;

        // TODO: why do we do this again?
        $this->filesystem->remove([$repoPath . '/vendor/prefixed/autoload.php', $repoPath . '/vendor/prefixed/composer']);
        $tempComposerJson->remove();

        // remove original folders for prefixed dependencies
        foreach ($this->prefixedDependencies as $dependency) {
            $vendorPath = $repoPath . '/vendor/' . $dependency;
            $this->filesystem->remove($vendorPath);
        }
    }

    private function regenerateAutoloaderForUnprefixedDeps()
    {
        $repoPath = $this->paths->getRepoPath();

        $this->output->writeln("Regenerating unprefixed autoloader...");

        $composerProject = new ComposerProject($repoPath, $this->filesystem);
        $unprefixedAutoloadFiles = $composerProject->getUnprefixedAutoloadFiles();
        $composerProject->createDummyComposerJsonFilesForPrefixedDeps();

        try {
            $regenerateUnprefixedAutload = new DumpAutoload($this->paths, $this->output, $repoPath);
            $regenerateUnprefixedAutload->passthru();;
        } finally {
            $composerProject->removeDummyComposerJsonFilesForPrefixedDeps();
        }

        $composerProject->replaceStaticAutoloadFiles($unprefixedAutoloadFiles);

        $proxyAutoloader = new ProxyAutoloader($repoPath . '/vendor', $this->output);
        $proxyAutoloader->write();
    }
}
