<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\ShellCommands;

use Matomo\Scoper\ShellCommand;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Output\OutputInterface;

class PhpScoper extends ShellCommand
{
    private Paths $paths;

    /**
     * @var string[]
     */
    private array $dependenciesToPrefix;

    /**
     * @var string[]
     */
    private array $namespacesToInclude;

    private bool $renameReferences = false;

    public function __construct(Paths $paths, OutputInterface $output, array $dependenciesToPrefix, array $namespacesToInclude)
    {
        parent::__construct($output);

        $this->paths = $paths;
        $this->dependenciesToPrefix = $dependenciesToPrefix;
        $this->namespacesToInclude = $namespacesToInclude;

        if (empty($namespacesToInclude)) {
            throw new \Exception("Couldn't find any namespaces to prefix, did you forget to run 'composer install'?");
        }
    }

    public function renameReferences(bool $value)
    {
        $this->renameReferences = $value;
    }

    public function getCommand(): string
    {
        $vendorPath = $this->paths->getRepoPath() . '/vendor';
        $configPath = '../scoper.inc.php';
        $outputDir = './prefixed/';

        $phpBinary = $this->paths->getPhpBinaryPath();
        $phpScoper = $this->paths->getPhpScoperPath();

        $env = 'MATOMO_DEPENDENCIES_TO_PREFIX="' . addslashes(json_encode($this->dependenciesToPrefix)) . '" '
            . 'MATOMO_NAMESPACES_TO_PREFIX="' . addslashes(json_encode($this->namespacesToInclude)) . '"';

        $extraOptions = '';
        if ($this->renameReferences) {
            $env .= " MATOMO_RENAME_REFERENCES=1";
            $vendorPath = $this->paths->getRepoPath();
            $outputDir = '.';
            $configPath = './scoper.inc.php';
            $extraOptions = ' --in-place';
        }

        $command = 'cd ' . $vendorPath . ' && ' . $env . ' ' . $phpBinary . ' ' . $phpScoper
            . ' add --force --output-dir=' . $outputDir . ' --config=' . $configPath . $extraOptions;

        return $command;
    }
}
