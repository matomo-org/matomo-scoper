<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\ShellCommands;

use Matomo\Scoper\ShellCommands\PhpScoper;
use Matomo\Scoper\Utilities\Paths;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

class PhpScoperTest extends TestCase
{
    public function test_getCommand_generatesCorrectCommand_forMainPrefixCommand()
    {
        $dependenciesToPrefix = ['org1/dep1', 'org2/dep2'];
        $namespacesToPrefix = ['Twig\\', 'Monolog\\', 'SomeOther\\Ns\\'];

        $paths = new Paths('/path/to/matomo', '/path/to/composer');
        $command = new PhpScoper($paths, new NullOutput(), $dependenciesToPrefix, $namespacesToPrefix);

        $dependenciesEnvVar = addslashes(json_encode($dependenciesToPrefix));
        $namespacesEnvVar = addslashes(json_encode($namespacesToPrefix));
        $libDir = MATOMO_SCOPER_ROOT_PATH;

        $phpBinary = PHP_BINARY;

        $actual = $command->getCommand();
        $expected = <<<CMD
cd /path/to/matomo/vendor && MATOMO_DEPENDENCIES_TO_PREFIX="$dependenciesEnvVar" MATOMO_NAMESPACES_TO_PREFIX="$namespacesEnvVar" $phpBinary $libDir/php-scoper.phar add --force --output-dir=./prefixed/ --config=../scoper.inc.php
CMD;

        $this->assertEquals($expected, $actual);
    }

    public function test_getCommand_generatesCorrectCommand_forSecondaryPrefixReferencesCommand()
    {
        $dependenciesToPrefix = ['org1/dep1', 'org2/dep2'];
        $namespacesToPrefix = ['Twig\\', 'Monolog\\', 'SomeOther\\Ns\\'];

        $paths = new Paths('/path/to/matomo', '/path/to/composer');
        $command = new PhpScoper($paths, new NullOutput(), $dependenciesToPrefix, $namespacesToPrefix);
        $command->renameReferences(true);

        $dependenciesEnvVar = addslashes(json_encode($dependenciesToPrefix));
        $namespacesEnvVar = addslashes(json_encode($namespacesToPrefix));
        $libDir = MATOMO_SCOPER_ROOT_PATH;

        $phpBinary = PHP_BINARY;

        $actual = $command->getCommand();
        $expected = <<<CMD
cd /path/to/matomo && MATOMO_DEPENDENCIES_TO_PREFIX="$dependenciesEnvVar" MATOMO_NAMESPACES_TO_PREFIX="$namespacesEnvVar" MATOMO_RENAME_REFERENCES=1 $phpBinary $libDir/php-scoper.phar add --force --output-dir=. --config=./scoper.inc.php --in-place
CMD;

        $this->assertEquals($expected, $actual);
    }

    public function test_getCommand_generatesCorrectCommand_whenRepoIsForPlugin()
    {
        $dependenciesToPrefix = ['org1/dep1', 'org2/dep2'];
        $namespacesToPrefix = ['Twig\\', 'Monolog\\', 'SomeOther\\Ns\\'];

        $paths = new Paths('/path/to/matomo', '/path/to/composer');
        $command = new PhpScoper($paths, new NullOutput(), $dependenciesToPrefix, $namespacesToPrefix);
        $command->setPlugin('MyPlugin');

        $dependenciesEnvVar = addslashes(json_encode($dependenciesToPrefix));
        $namespacesEnvVar = addslashes(json_encode($namespacesToPrefix));
        $libDir = MATOMO_SCOPER_ROOT_PATH;

        $phpBinary = PHP_BINARY;

        $actual = $command->getCommand();
        $expected = <<<CMD
cd /path/to/matomo/vendor && MATOMO_DEPENDENCIES_TO_PREFIX="$dependenciesEnvVar" MATOMO_NAMESPACES_TO_PREFIX="$namespacesEnvVar" MATOMO_PLUGIN="MyPlugin" $phpBinary $libDir/php-scoper.phar add --force --output-dir=./prefixed/ --config=../scoper.inc.php
CMD;

        $this->assertEquals($expected, $actual);
    }
}
