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

class DumpAutoload extends ShellCommand
{
    private Paths $paths;

    private string $workingDirectory;

    private bool $ignorePlatformCheck;

    public function __construct(Paths $paths, OutputInterface $output, string $workingDirectory, bool $ignorePlatformCheck)
    {
        parent::__construct($output);
        $this->paths = $paths;
        $this->workingDirectory = $workingDirectory;
        $this->ignorePlatformCheck = $ignorePlatformCheck;
    }

    public function getCommand(): string
    {
        $composerPath = $this->paths->getComposerPath();
        $composerCommand = escapeshellarg($composerPath) . " --working-dir=" . escapeshellarg($this->workingDirectory)
            . " dump-autoload -o --no-interaction";

        if ($this->ignorePlatformCheck) {
            $composerCommand .= ' --ignore-platform-reqs';
        }

        return $composerCommand;
    }
}
