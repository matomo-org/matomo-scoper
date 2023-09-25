<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\ShellCommands;

use Matomo\Scoper\ShellCommands\DumpAutoload;
use Matomo\Scoper\Utilities\Paths;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

class DumpAutoloadTest extends TestCase
{
    public function test_getCommand_constructsCorrectCommand()
    {
        $paths = new Paths('/path/to/matomo', '/path/to/composer');
        $command = new DumpAutoload($paths, new NullOutput(), '/path/to/matomo/plugins/plugin');

        $actualCommand = $command->getCommand();
        $expectedCommand = "'/path/to/composer' --working-dir='/path/to/matomo/plugins/plugin' dump-autoload -o --no-interaction";

        $this->assertEquals($expectedCommand, $actualCommand);
    }
}
