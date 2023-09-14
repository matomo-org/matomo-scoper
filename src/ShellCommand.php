<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

use Symfony\Component\Console\Output\OutputInterface;

abstract class ShellCommand
{
    protected OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    protected abstract function getCommand(): string;

    public function passthru(): void
    {
        $classNameParts = explode('\\', get_class($this));
        $simpleClassName = array_pop($classNameParts);

        $command = $this->getCommand();

        if ($this->output->isVerbose()) {
            $this->output->writeln($simpleClassName . ' command: ' . $command);
        }

        passthru($command, $returnCode);

        if ($returnCode) {
            throw new \Exception("Failed to run $simpleClassName! Command was: $command");
        }
    }
}
