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

    public function __construct(Paths $paths, OutputInterface $output, string $workingDirectory)
    {
        parent::__construct($output);
        $this->paths = $paths;
        $this->workingDirectory = $workingDirectory;
    }

    protected function getCommand(): string
    {
        $composerPath = $this->paths->getComposerPath();
        $composerCommand = escapeshellarg($composerPath) . " --working-dir=" . escapeshellarg($this->workingDirectory)
            . " dump-autoload -o --no-interaction";
        return $composerCommand;
    }
}
