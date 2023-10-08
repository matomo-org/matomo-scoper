<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\Composer;

use Matomo\Scoper\Composer\ComposerJson;
use Matomo\Scoper\Composer\ComposerLock;
use Matomo\Scoper\Tests\Framework\ComposerTestCase;

class ComposerJsonTest extends ComposerTestCase
{
    const TEST_DEPENDENCY = 'test/dependency';

    public function test_writeTo_overwritesTheTargetFile_ifItExists()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], ['name' => self::TEST_DEPENDENCY, 'test' => 'value']);

        $rootComposerJson = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/composer.json';

        $dependency = new ComposerJson(['name' => self::TEST_DEPENDENCY, 'test' => 'value']);
        $this->assertEquals(['name' => self::TEST_DEPENDENCY, 'test' => 'value'], $dependency->getComposerJsonContents());

        $dependency->setComposerJsonContents(['this' => 'that']);
        $dependency->writeTo($rootComposerJson);

        $this->assertEquals(['this' => 'that'], json_decode(file_get_contents($rootComposerJson), true));
        $this->assertEquals(['this' => 'that'], $dependency->getComposerJsonContents());
    }

    public function test_getNamespaces_returnsEmptyArray_ifNoAutoloadingNamespacesDeclared()
    {
        $dependency = new ComposerJson(['name' => self::TEST_DEPENDENCY]);
        $this->assertEquals([], $dependency->getNamespaces());
    }

    public function test_getNamespaces_returnsPsr0Namespaces_ifPsr0NamespacesDeclared()
    {
        $dependency = new ComposerJson([
            'name' => 'project',
            'autoload' => [
                'psr-0' => [
                    "Monolog\\" => "src/",
                    "Vendor\\Namespace\\" => "src/",
                ],
            ],
        ]);
        $this->assertEquals(['Monolog\\', 'Vendor\\Namespace\\'], $dependency->getNamespaces());
    }

    public function test_getNamespaces_returnsPsr4Namespaces_ifPsr4NamespacesDeclared()
    {
        $dependency = new ComposerJson([
            'name' => 'project',
            'autoload' => [
                'psr-4' => [
                    "Monolog2\\" => "src/",
                    "Vendor2\\Namespace\\" => "src/",
                ],
            ],
        ]);
        $this->assertEquals(['Monolog2\\', 'Vendor2\\Namespace\\'], $dependency->getNamespaces());
    }

    public function test_getNamespaces_returnsPsr0And4Namespaces_ifBothAreDeclared()
    {
        $dependency = new ComposerJson([
            'name' => 'project',
            'autoload' => [
                'psr-0' => [
                    "Monolog\\" => "src/",
                    "Vendor\\Namespace\\" => "src/",
                ],
                'psr-4' => [
                    "Monolog2\\" => "src/",
                    "Vendor2\\Namespace\\" => "src/",
                ],
            ],
        ]);
        $this->assertEquals(['Monolog2\\', 'Vendor2\\Namespace\\', 'Monolog\\', 'Vendor\\Namespace\\'], $dependency->getNamespaces());
    }

    public function test_getRequires_returnsComposerDependencies_forProject()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY, 'org/dep', 'org2/dep2', 'org2/dep3'], [
            'require' => [
                'org/dep' => '*',
                'org2/dep2' => '*',
                'org2/dep3' => '*',
            ],
        ]);

        $composerLock = new ComposerLock(json_decode(file_get_contents($rootPath . '/composer.lock'), true));
        $dependency = $composerLock->getDependency(self::TEST_DEPENDENCY);
        $this->assertEquals([
            $composerLock->getDependency('org/dep'),
            $composerLock->getDependency('org2/dep2'),
            $composerLock->getDependency('org2/dep3'),
        ], $dependency->getRequires($composerLock));
    }

    public function test_getRequires_ignoresPhpVersionRequirement()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY, 'org/dep', 'org2/dep2', 'org2/dep3'], [
            'name' => 'project',
            'require' => [
                'org/dep' => '*',
                'org2/dep2' => '*',
                'php' => '>= 1',
                'org2/dep3' => '*',
            ],
        ]);

        $composerLock = new ComposerLock(json_decode(file_get_contents($rootPath . '/composer.lock'), true));
        $dependency = $composerLock->getDependency(self::TEST_DEPENDENCY);
        $this->assertEquals([
            $composerLock->getDependency('org/dep'),
            $composerLock->getDependency('org2/dep2'),
            $composerLock->getDependency('org2/dep3'),
        ], $dependency->getRequires($composerLock));
    }

    public function test_getRequires_ignoresPhpExtensionRequirements()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY, 'org/dep', 'org2/depext2', 'orgext2/dep3'], [
            'name' => 'project',
            'require' => [
                'ext-whatever' => '*',
                'org/dep' => '*',
                'org2/depext2' => '*',
                'orgext2/dep3' => '*',
                'ext-mbstring' => '*',
            ],
        ]);

        $composerLock = new ComposerLock(json_decode(file_get_contents($rootPath . '/composer.lock'), true));
        $dependency = $composerLock->getDependency(self::TEST_DEPENDENCY);
        $actual = [
            $composerLock->getDependency('org/dep'),
            $composerLock->getDependency('org2/depext2'),
            $composerLock->getDependency('orgext2/dep3'),
        ];
        $this->assertEquals($actual, $dependency->getRequires($composerLock));
    }

    public function test_getAutoloadFiles_returnsFilesDefinedInComposerJson_ifComposerJsonPresent()
    {
        $dependency = new ComposerJson([
            'name' => 'project',
            'autoload' => [
                'files' => ['somefile.php', 'someotherfile.php'],
            ],
        ]);
        $this->assertEquals(['somefile.php', 'someotherfile.php'], $dependency->getAutoloadFiles());
    }

    public function test_getAutoloadFiles_returnsEmptyArray_ifComposerJsonHasNoAutoloadFilesEntry()
    {
        $dependency = new ComposerJson([
            'name' => 'project',
        ]);
        $this->assertEquals([], $dependency->getAutoloadFiles());
    }

    public function test_getComposerJsonContents_returnsContentsSetInConstructor()
    {
        $dependency = new ComposerJson([
            'test' => 'value',
        ]);
        $this->assertEquals(['test' => 'value'], $dependency->getComposerJsonContents());
    }
}
