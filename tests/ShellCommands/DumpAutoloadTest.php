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
    /**
     * @dataProvider get_getCommand_testValues
     *
     * @param bool $ignorePlatformCheck
     * @param string $expectedCommand
     * @return void
     */
    public function test_getCommand_constructsCorrectCommand(bool $ignorePlatformCheck, string $expectedCommand)
    {
        $paths = new Paths('/path/to/matomo', '/path/to/composer');
        $command = new DumpAutoload($paths, new NullOutput(), '/path/to/matomo/plugins/plugin', $ignorePlatformCheck);

        $actualCommand = $command->getCommand();

        $this->assertEquals($expectedCommand, $actualCommand, "When ignorePlatform is {$ignorePlatformCheck}, the expected command is: {$expectedCommand}");
    }

    public static function get_getCommand_testValues()
    {
        return [
            [
                false,
                "'/path/to/composer' --working-dir='/path/to/matomo/plugins/plugin' dump-autoload -o --no-interaction",
            ],
            [
                true,
                "'/path/to/composer' --working-dir='/path/to/matomo/plugins/plugin' dump-autoload -o --no-interaction --ignore-platform-reqs",
            ],
        ];
    }
}
