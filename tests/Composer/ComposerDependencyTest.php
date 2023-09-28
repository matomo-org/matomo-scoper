<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\Composer;

use Matomo\Scoper\Composer\ComposerJson;
use Matomo\Scoper\Tests\Framework\ComposerTestCase;

class ComposerDependencyTest extends ComposerTestCase
{
    const TEST_DEPENDENCY = 'test/dependency';

    public function test_hasComposerJson_returnsTrueIfAComposerJsonCanBeFound()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], []);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertFalse($dependency->hasComposerJson());
    }

    public function test_hasComposerJson_returnsFalseIfAComposerJsonCannotBeFound()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertFalse($dependency->hasComposerJson());
    }

    public function test_hasComposerJson_returnsTrueIfComposerIsNested_andProjectHasSingleDirectoryTree()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $composerJsonPath = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/folder1/folder2/folder3/composer.json';
        mkdir(dirname($composerJsonPath), 0777, true);

        file_put_contents($composerJsonPath, '{}');

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertTrue($dependency->hasComposerJson());
    }

    public function test_getDependencyPath_returnsTheFullPathToTheDependency()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals($rootPath . '/vendor/' . self::TEST_DEPENDENCY, $dependency->getDependencyPath());
    }

    public function test_writeComposerJsonContents_overwriteTheComposerJsonFile_atTheRootOfTheDependency()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], ['test' => 'value']);

        $rootComposerJson = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/composer.json';

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(['test' => 'value'], $dependency->getComposerJsonContents());

        $dependency->writeComposerJsonContents(['this' => 'that']);

        $this->assertEquals(['this' => 'that'], json_decode(file_get_contents($rootComposerJson), true));
        $this->assertEquals(['this' => 'that'], $dependency->getComposerJsonContents());
    }

    public function test_writeComposerJsonContents_doesNotOverwriteNestedComposerJsonFiles()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $composerJsonPath = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/folder1/folder2/folder3/composer.json';
        mkdir(dirname($composerJsonPath), 0777, true);

        file_put_contents($composerJsonPath, '{}');

        $rootComposerJson = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/composer.json';

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);

        $this->assertFalse(is_file($rootComposerJson));

        $dependency->writeComposerJsonContents(['test' => 'value']);

        $this->assertEquals(['test' => 'value'], $dependency->getComposerJsonContents());
        $this->assertEquals(['test' => 'value'], json_decode(file_get_contents($rootComposerJson), true));
        $this->assertTrue(is_file($composerJsonPath));
    }

    public function test_getComposerJsonPath_returnsNull_ifNoComposerJsonPathExists()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);
        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertNull($dependency->getComposerJsonPath());
    }

    public function test_getComposerJsonPath_returnsNull_ifDependencyFolderDoesNotExist()
    {
        $this->assertFalse(is_dir($this->getTestProjectRootPath() . '/vendor/test/test'));
        $dependency = new ComposerJson($this->getTestProjectRootPath(), 'test/test');
        $this->assertNull($dependency->getComposerJsonPath());
    }

    public function test_getComposerJsonPath_returnsPath_ifRootComposerJsonExists()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], ['test' => 'value']);
        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals($rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/composer.json', $dependency->getComposerJsonPath());
    }

    public function test_getComposerJsonPath_returnsPath_ifComposerJsonIsNestedInSomeFolders()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $composerJsonPath = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/folder1/folder2/folder3/composer.json';
        mkdir(dirname($composerJsonPath), 0777, true);
        file_put_contents($composerJsonPath, '{}');

        $this->assertFalse(is_file($rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/composer.json'));

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals($composerJsonPath, $dependency->getComposerJsonPath());
    }

    public function test_getComposerJsonPath_returnsNull_ifComposerIsNested_butNotInTheTopmostDirectoryTree()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $otherDirectory = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/folder1/folder2/folder3';
        mkdir($otherDirectory, 0777, true);

        $composerJsonPath = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/zzz/folder2/folder3/composer.json';
        mkdir(dirname($composerJsonPath), 0777, true);
        file_put_contents($composerJsonPath, '{}');

        $this->assertFalse(is_file($rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/composer.json'));

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertNull($dependency->getComposerJsonPath());
    }

    public function test_getNamespaces_returnsEmptyArray_ifNoAutoloadingNamespacesDeclared()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], ['name' => 'project']);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals([], $dependency->getNamespaces());
    }

    public function test_getNamespaces_returnsPsr0Namespaces_ifPsr0NamespacesDeclared()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'name' => 'project',
            'autoload' => [
                'psr-0' => [
                    "Monolog\\" => "src/",
                    "Vendor\\Namespace\\" => "src/",
                ],
            ],
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(['Monolog\\', 'Vendor\\Namespace\\'], $dependency->getNamespaces());
    }

    public function test_getNamespaces_returnsPsr4Namespaces_ifPsr4NamespacesDeclared()
    {
        // TODO
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'name' => 'project',
            'autoload' => [
                'psr-4' => [
                    "Monolog2\\" => "src/",
                    "Vendor2\\Namespace\\" => "src/",
                ],
            ],
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(['Monolog2\\', 'Vendor2\\Namespace\\'], $dependency->getNamespaces());
    }

    public function test_getNamespaces_returnsPsr0And4Namespaces_ifBothAreDeclared()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
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

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(['Monolog2\\', 'Vendor2\\Namespace\\', 'Monolog\\', 'Vendor\\Namespace\\'], $dependency->getNamespaces());
    }

    public function test_getRequires_returnsComposerDependencies_forProject()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'name' => 'project',
            'require' => [
                'org/dep' => '*',
                'org2/dep2' => '*',
                'org2/dep3' => '*',
            ],
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals([
            new ComposerJson($rootPath, 'org/dep'),
            new ComposerJson($rootPath, 'org2/dep2'),
            new ComposerJson($rootPath, 'org2/dep3'),
        ], $dependency->getRequires());
    }

    public function test_getRequires_ignoresPhpVersionRequirement()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'name' => 'project',
            'require' => [
                'org/dep' => '*',
                'org2/dep2' => '*',
                'php' => '>= 1',
                'org2/dep3' => '*',
            ],
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals([
            new ComposerJson($rootPath, 'org/dep'),
            new ComposerJson($rootPath, 'org2/dep2'),
            new ComposerJson($rootPath, 'org2/dep3'),
        ], $dependency->getRequires());
    }

    public function test_getRequires_ignoresPhpExtensionRequirements()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'name' => 'project',
            'require' => [
                'ext-whatever' => '*',
                'org/dep' => '*',
                'org2/depext2' => '*',
                'orgext2/dep3' => '*',
                'ext-mbstring' => '*',
            ],
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $actual = [
            new ComposerJson($rootPath, 'org/dep'),
            new ComposerJson($rootPath, 'org2/depext2'),
            new ComposerJson($rootPath, 'orgext2/dep3'),
        ];
        $this->assertEquals($actual, $dependency->getRequires());
    }

    public function test_getAutoloadFiles_returnsFilesDefinedInComposerJson_ifComposerJsonPresent()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'name' => 'project',
            'autoload' => [
                'files' => ['somefile.php', 'someotherfile.php'],
            ],
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(['somefile.php', 'someotherfile.php'], $dependency->getAutoloadFiles());
    }

    public function test_getAutoloadFiles_returnsEmptyArray_ifComposerJsonDoesNotExist()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals([], $dependency->getAutoloadFiles());
    }

    public function test_getAutoloadFiles_returnsEmptyArray_ifComposerJsonHasNoAutoloadFilesEntry()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'name' => 'project',
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals([], $dependency->getAutoloadFiles());
    }

    public function test_getComposerJsonContents_returnsNull_ifComposerJsonDoesNotExist()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(null, $dependency->getComposerJsonContents());
    }

    public function test_getComposerJsonContents_returnsParsedContents_ifComposerJsonExists()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], [
            'test' => 'value',
        ]);

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(['test' => 'value'], $dependency->getComposerJsonContents());
    }

    public function test_getComposerJsonContents_returnsParsedContents_ifComposerJsonExists_andIsLocatedInNextedFolder()
    {
        $rootPath = $this->setUpTestProject(null, [self::TEST_DEPENDENCY], null);

        $composerJsonPath = $rootPath . '/vendor/' . self::TEST_DEPENDENCY . '/zzz/folder2/folder3/composer.json';
        mkdir(dirname($composerJsonPath), 0777, true);
        file_put_contents($composerJsonPath, '{"abc":"def"}');

        $dependency = new ComposerJson($rootPath, self::TEST_DEPENDENCY);
        $this->assertEquals(['abc' => 'def'], $dependency->getComposerJsonContents());
    }
}
