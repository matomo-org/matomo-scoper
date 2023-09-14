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

    public function __construct(Paths $paths, OutputInterface $output, array $dependenciesToPrefix, array $namespacesToInclude)
    {
        parent::__construct($output);

        $this->paths = $paths;
        $this->dependenciesToPrefix = $dependenciesToPrefix;
        $this->namespacesToInclude = $namespacesToInclude;

        if (empty($namespacesToInclude)) {
            throw new \Exception("Couldn't find any namespaces to prefix, dependencies may not be supported, or something might be wrong with the prefixing process.");
        }
    }

    protected function getCommand(): string
    {
        $vendorPath = $this->paths->getRepoPath() . '/vendor';

        $phpBinary = $this->paths->getPhpBinaryPath();
        $phpScoper = $this->paths->getPhpScoperPath();

        $env = 'MATOMO_DEPENDENCIES_TO_PREFIX="' . addslashes(json_encode($this->dependenciesToPrefix)) . '" '
            . 'MATOMO_NAMESPACES_TO_PREFIX="' . addslashes(json_encode($this->namespacesToInclude)) . '"';
        $command = 'cd ' . $vendorPath . ' && ' . $env . ' ' . $phpBinary . ' ' . $phpScoper
            . ' add --force --output-dir=./prefixed/ --config=../scoper.inc.php';

        return $command;
    }
}
