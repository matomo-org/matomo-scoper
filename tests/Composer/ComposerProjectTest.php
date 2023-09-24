<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\Composer;

use Matomo\Scoper\Composer\ComposerProject;
use Matomo\Scoper\Tests\Framework\ComposerTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ComposerProjectTest extends ComposerTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeTestProject();
    }

    public function test_getUnprefixedAutoloadFiles_removesPrefixedFiles_whenAutoloadStaticPutsFilesOnOneLine()
    {
        $rootPath = $this->setUpTestProject([], ['mustangostang/spyc', 'lox/xhprof', 'szymach/c-pchart'], []);

        $this->putTestProjectFile('vendor/mustangostang/spyc/Spyc.php', 'TEST');
        $this->putTestProjectFile('vendor/szymach/c-pchart/constants.php', 'TEST');
        $this->putTestProjectFile('vendor/lox/xhprof/xhprof_lib/utils/xhprof_runs.php', 'TEST');

        $this->putTestProjectFile(
            'vendor/composer/autoload_static.php',
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$files = array('123' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php', '456' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/deep_copy.php','789' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_lib.php',   '101123' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php','7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php',);
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF
        );

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $unprefixedAutoloadFiles = $composerProject->getUnprefixedAutoloadFiles();

        $expected = <<<EOF
        public static \$files = array(
        '123' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php',
        '101123' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php',
        '7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php');
        EOF;

        $this->assertEquals(
            trim($expected),
            trim($unprefixedAutoloadFiles)
        );
    }

    public function test_getUnprefixedAutoloadFiles_removesPrefixedFiles_whenAutoloadStaticPutsFilesOnSeparateLines()
    {
        $rootPath = $this->setUpTestProject([], ['mustangostang/spyc', 'lox/xhprof', 'szymach/somethingelse'], []);

        $this->putTestProjectFile('vendor/mustangostang/spyc/Spyc.php', 'TEST');
        $this->putTestProjectFile('vendor/szymach/c-pchart/constants.php', 'TEST');
        $this->putTestProjectFile('vendor/lox/xhprof/xhprof_lib/utils/xhprof_runs.php', 'TEST');

        $this->putTestProjectFile(
            'vendor/composer/autoload_static.php',
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$files = array(
                    '04c6c5c2f7095ccf6c481d3e53e1776f' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php',
                    '6124b4c8570aa390c21fafd04a26c69f' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/deep_copy.php',
                    '8ac259e46781d60665439a97846a4a66' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_lib.php',
                    'e30869f87cf76d235b75bb956c7ba9ed' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php',
                    '7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php',
                );
            
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF
        );

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $unprefixedAutoloadFiles = $composerProject->getUnprefixedAutoloadFiles();

        $expected = <<<EOF
        public static \$files = array(
        '04c6c5c2f7095ccf6c481d3e53e1776f' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php',
        'e30869f87cf76d235b75bb956c7ba9ed' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php',
        '7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php');
        EOF;

        $this->assertEquals(
            trim($expected),
            trim($unprefixedAutoloadFiles)
        );
    }

    public function test_getUnprefixedAutoloadFiles_removesPrefixedFiles_whenArrayHasBrackets()
    {
        $rootPath = $this->setUpTestProject([], ['mustangostang/spyc', 'lox/asdkjlf', 'szymach/c-pchart'], []);

        $this->putTestProjectFile('vendor/mustangostang/spyc/Spyc.php', 'TEST');
        $this->putTestProjectFile('vendor/szymach/c-pchart/constants.php', 'TEST');
        $this->putTestProjectFile('vendor/lox/xhprof/xhprof_lib/utils/xhprof_runs.php', 'TEST');

        $this->putTestProjectFile(
            'vendor/composer/autoload_static.php',
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$files = [
                    '04c6c5c2f7095ccf6c481d3e53e1776f' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php',
                    '6124b4c8570aa390c21fafd04a26c69f' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/deep_copy.php',
                    '8ac259e46781d60665439a97846a4a66' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_lib.php',
                    'e30869f87cf76d235b75bb956c7ba9ed' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php',
                    '7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php',
                ];
            
            
                public static \$prefixLengthsPsr4 = [
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => [],
                ];
            }
            EOF
        );

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $unprefixedAutoloadFiles = $composerProject->getUnprefixedAutoloadFiles();

        $expected = <<<EOF
        public static \$files = array(
        '04c6c5c2f7095ccf6c481d3e53e1776f' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php',
        'e30869f87cf76d235b75bb956c7ba9ed' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php',
        '7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php');
        EOF;

        $this->assertEquals(
            trim($expected),
            trim($unprefixedAutoloadFiles)
        );
    }

    public function test_createDummyComposerJsonFilesForPrefixedDeps_createsDummyComposerJsonFiles_forDependenciesInThePrefixedFolder()
    {
        $rootPath = $this->setUpTestProject(['name' => 'root'], ['mustangostang/spyc', 'prefixed/lox/xhprof', 'prefixed/szymach/c-pchart'], [
            'name' => 'dependency',
            'autoload' => 'junk',
        ]);

        file_put_contents($rootPath . '/vendor/prefixed/szymach/c-pchart/composer.json', json_encode([
            'name' => 'dependency',
            'autoload' => [
                'classmap' => [
                    "src/",
                    "lib/",
                    "Something.php",
                ],
            ],
        ]));

        mkdir($rootPath . '/vendor/prefixed/another/nocomposerjson', 0777, true);

        $allFiles = $this->getTestProjectFiles();
        $this->assertEquals([
            '/composer.json',
            '/vendor',
            '/vendor/mustangostang',
            '/vendor/mustangostang/spyc',
            '/vendor/mustangostang/spyc/composer.json',
            '/vendor/prefixed',
            '/vendor/prefixed/another',
            '/vendor/prefixed/another/nocomposerjson',
            '/vendor/prefixed/lox',
            '/vendor/prefixed/lox/xhprof',
            '/vendor/prefixed/lox/xhprof/composer.json',
            '/vendor/prefixed/szymach',
            '/vendor/prefixed/szymach/c-pchart',
            '/vendor/prefixed/szymach/c-pchart/composer.json',
        ], $allFiles);

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $composerProject->createDummyComposerJsonFilesForPrefixedDeps();

        $allFiles = $this->getTestProjectFiles();
        $this->assertEquals([
            '/composer.json',
            '/vendor',
            '/vendor/lox',
            '/vendor/lox/xhprof',
            '/vendor/lox/xhprof/composer.json',
            '/vendor/mustangostang',
            '/vendor/mustangostang/spyc',
            '/vendor/mustangostang/spyc/composer.json',
            '/vendor/prefixed',
            '/vendor/prefixed/another',
            '/vendor/prefixed/another/nocomposerjson',
            '/vendor/prefixed/lox',
            '/vendor/prefixed/lox/xhprof',
            '/vendor/prefixed/lox/xhprof/composer.json',
            '/vendor/prefixed/szymach',
            '/vendor/prefixed/szymach/c-pchart',
            '/vendor/prefixed/szymach/c-pchart/composer.json',
            '/vendor/szymach',
            '/vendor/szymach/c-pchart',
            '/vendor/szymach/c-pchart/Something.php',
            '/vendor/szymach/c-pchart/composer.json',
            '/vendor/szymach/c-pchart/lib',
            '/vendor/szymach/c-pchart/src',
        ], $allFiles);

        $this->assertEquals(
            ['name' => 'dependency', 'autoload' => 'junk'],
            $this->getTestProjectDependencyComposerJson('mustangostang/spyc')
        );

        $this->assertEquals(
            ['name' => 'dependency'],
            $this->getTestProjectDependencyComposerJson('lox/xhprof')
        );

        $this->assertEquals(
            ['name' => 'dependency'],
            $this->getTestProjectDependencyComposerJson('szymach/c-pchart')
        );

        $this->assertEquals(
            ['name' => 'dependency', 'autoload' => 'junk'],
            $this->getTestProjectDependencyComposerJson('prefixed/lox/xhprof')
        );

        $this->assertEquals(
            [
                'name' => 'dependency',
                'autoload' => [
                    'classmap' => [
                        "src/",
                        "lib/",
                        "Something.php",
                    ],
                ],
            ],
            $this->getTestProjectDependencyComposerJson('prefixed/szymach/c-pchart')
        );
    }

    public function test_removeDummyComposerJsonFilesForPrefixedDeps_removesDependenciesInVendor_thatHavePrefixedVersions()
    {
        $dependencyFolders = [
            'mustangostang/spyc',
            'prefixed/lox/xhprof',
            'prefixed/szymach/c-pchart',
            'lox/xhprof',
            'szymach/c-pchart',
        ];

        $rootPath = $this->setUpTestProject(['name' => 'root'], $dependencyFolders, ['name' => 'dependency']);

        $allFiles = $this->getTestProjectFiles();
        $this->assertEquals([
            '/composer.json',
            '/vendor',
            '/vendor/lox',
            '/vendor/lox/xhprof',
            '/vendor/lox/xhprof/composer.json',
            '/vendor/mustangostang',
            '/vendor/mustangostang/spyc',
            '/vendor/mustangostang/spyc/composer.json',
            '/vendor/prefixed',
            '/vendor/prefixed/lox',
            '/vendor/prefixed/lox/xhprof',
            '/vendor/prefixed/lox/xhprof/composer.json',
            '/vendor/prefixed/szymach',
            '/vendor/prefixed/szymach/c-pchart',
            '/vendor/prefixed/szymach/c-pchart/composer.json',
            '/vendor/szymach',
            '/vendor/szymach/c-pchart',
            '/vendor/szymach/c-pchart/composer.json',
        ], $allFiles);

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $composerProject->removeDummyComposerJsonFilesForPrefixedDeps();

        $allFiles = $this->getTestProjectFiles();
        $this->assertEquals([
            '/composer.json',
            '/vendor',
            '/vendor/mustangostang',
            '/vendor/mustangostang/spyc',
            '/vendor/mustangostang/spyc/composer.json',
            '/vendor/prefixed',
            '/vendor/prefixed/lox',
            '/vendor/prefixed/lox/xhprof',
            '/vendor/prefixed/lox/xhprof/composer.json',
            '/vendor/prefixed/szymach',
            '/vendor/prefixed/szymach/c-pchart',
            '/vendor/prefixed/szymach/c-pchart/composer.json',
        ], $allFiles);
    }

    public function test_replaceStaticAutoloadFiles_replacesFilesInStaticAutoloader()
    {
        $rootPath = $this->setUpTestProject([], [], []);
        $autoloadStaticPath = $rootPath . '/vendor/composer/autoload_static.php';

        mkdir(dirname($autoloadStaticPath), 0777, true);
        file_put_contents(
            $autoloadStaticPath,
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$files = array('123' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php', '456' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/deep_copy.php','789' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_lib.php',   '101123' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php','7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php',);
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF
        );

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $composerProject->replaceStaticAutoloadFiles('REPLACED');

        $actualContents = file_get_contents($autoloadStaticPath);
        $this->assertEquals(
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                REPLACED
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF,
            $actualContents
        );
    }

    public function test_replaceStaticAutoloadFiles_replacesFilesInStaticAutoloader_ifEntriesAreOnSeparateLines()
    {
        $rootPath = $this->setUpTestProject([], [], []);
        $autoloadStaticPath = $rootPath . '/vendor/composer/autoload_static.php';

        mkdir(dirname($autoloadStaticPath), 0777, true);
        file_put_contents(
            $autoloadStaticPath,
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$files = array(
                    '04c6c5c2f7095ccf6c481d3e53e1776f' => __DIR__ . '/..' . '/mustangostang/spyc/Spyc.php',
                    '6124b4c8570aa390c21fafd04a26c69f' => __DIR__ . '/..' . '/myclabs/deep-copy/src/DeepCopy/deep_copy.php',
                    '8ac259e46781d60665439a97846a4a66' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_lib.php',
                    'e30869f87cf76d235b75bb956c7ba9ed' => __DIR__ . '/..' . '/lox/xhprof/xhprof_lib/utils/xhprof_runs.php',
                    '7bb4f001eb5212bde073bf47a4bbedad' => __DIR__ . '/..' . '/szymach/c-pchart/constants.php',
                );
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF
        );

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $composerProject->replaceStaticAutoloadFiles('REPLACED');

        $actualContents = file_get_contents($autoloadStaticPath);
        $this->assertEquals(
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                REPLACED
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF,
            $actualContents
        );
    }

    public function test_replaceStaticAutoloadFiles_replacesFilesInStaticAutoloader_ifArrayIsEmpty()
    {
        $rootPath = $this->setUpTestProject([], [], []);
        $autoloadStaticPath = $rootPath . '/vendor/composer/autoload_static.php';

        mkdir(dirname($autoloadStaticPath), 0777, true);
        file_put_contents(
            $autoloadStaticPath,
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$files = array();
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF
        );

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $composerProject->replaceStaticAutoloadFiles('REPLACED');

        $actualContents = file_get_contents($autoloadStaticPath);
        $this->assertEquals(
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                REPLACED
            
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF,
            $actualContents
        );
    }

    public function test_replaceStaticAutoloadFiles_doesNothing_ifFilesArrayIsNotPresent()
    {
        $rootPath = $this->setUpTestProject([], [], []);
        $autoloadStaticPath = $rootPath . '/vendor/composer/autoload_static.php';

        mkdir(dirname($autoloadStaticPath), 0777, true);
        file_put_contents(
            $autoloadStaticPath,
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF
        );

        $composerProject = new ComposerProject($rootPath, new Filesystem());
        $composerProject->replaceStaticAutoloadFiles('REPLACED');

        $actualContents = file_get_contents($autoloadStaticPath);
        $this->assertEquals(
            <<<EOF
            <?php
            
            // autoload_static.php @generated by Composer
            
            namespace Composer\Autoload;
            
            class ComposerStaticInit2147197d52b13ec963dca390fc07f201
            {
                public static \$prefixLengthsPsr4 = array (
                    'T' => 
                    array (
                        'Twig\\' => 5,
                    ),
                    'S' => array (),
                );
            }
            EOF,
            $actualContents
        );
    }

    private function getTestProjectFiles()
    {
        $rootPath = $this->getTestProjectRootPath();

        $allFiles = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getTestProjectRootPath()));

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getFilename() === '..') {
                continue;
            }

            $pathName = $file->getPathName();
            if ($file->getFilename() === '.') {
                $pathName = dirname($file->getPathName());
            }

            if ($pathName == $rootPath) {
                continue;
            }

            $allFiles[] = str_replace($rootPath, '', $pathName);
        }

        sort($allFiles);

        return $allFiles;
    }

    private function getTestProjectDependencyComposerJson(string $dependencyPath): array
    {
        $contents = file_get_contents($this->getTestProjectRootPath() . '/vendor/' . $dependencyPath . '/composer.json');
        return json_decode($contents, true);
    }

    private function putTestProjectFile(string $relativePath, string $contents)
    {
        $path = $this->getTestProjectRootPath() . '/' . $relativePath;
        @mkdir(dirname($path), 0777, true);
        $this->assertNotFalse(file_put_contents($path, $contents));
    }
}
