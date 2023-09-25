<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\GeneratedFiles;

use Matomo\Scoper\GeneratedFiles\TemporaryComposerJson;
use Matomo\Scoper\Tests\Framework\ComposerTestCase;

class TemporaryComposerJsonTest extends ComposerTestCase
{
    public function test_getContent_correctlyCollectsStaticFilesToAutoload_ofEveryPrefixedDependencyInAProject()
    {
        $rootPath = $this->setUpTestProject(
            ['name' => 'project'],
            [
                'org1/dep1',
                'org1/dep2',
                'prefixed/org1/dep3',
                'prefixed/org2/dep4', // no composer.json
                'prefixed/org2/dep5',
                'prefixed/org2/dep6', // no autoload files
                'org2/anotherdep',
            ],
            ['name' => 'dependency'],
        );

        $this->putTestProjectFile('vendor/prefixed/org1/dep3/composer.json', json_encode([
            'autoload' => [
                'psr-0' => ['SomeNamespace\\' => 'src/'],
                'files' => ['dep3.php'],
            ],
        ]));
        unlink($rootPath . '/vendor/prefixed/org2/dep4/composer.json');
        $this->putTestProjectFile('vendor/prefixed/org2/dep5/composer.json', json_encode([
            'autoload' => [
                'files' => ['dep5.php', 'src/depanother5.php'],
            ],
        ]));
        $this->putTestProjectFile('vendor/prefixed/org2/dep6/composer.json', json_encode([]));

        $file = new TemporaryComposerJson($rootPath, ['org1/dep3', 'org2/dep4', 'org2/dep5', 'org2/dep6']);
        $content = $file->getContent();
        $content = json_decode($content, true);

        $this->assertEquals([
            'autoload' => [
                'classmap' => [''],
                'files' => [
                    'org1/dep3/dep3.php',
                    'org2/dep5/dep5.php',
                    'org2/dep5/src/depanother5.php',
                ],
            ],
        ], $content);
    }
}
