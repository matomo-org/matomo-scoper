<?php

namespace Matomo\Scoper\Tests\Composer;

use Matomo\Scoper\Composer\ComposerJson;
use Matomo\Scoper\Composer\ComposerLock;
use Matomo\Scoper\Composer\ComposerProject;
use Matomo\Scoper\Tests\Framework\ComposerTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ComposerLockTest extends ComposerTestCase
{
    public function test_getFlatDependencyTreeFor_ignoresDependencies_ifDependenciesToIgnoreRequested()
    {
        $rootPath = $this->setUpTestProject([], [
            'org/dep1', // depends on org1/dep2
            'org1/dep2', // depends on org1/dep3, org2/dep4
            'org1/dep3', // depends on org/dep1 (cycle), org2/dep5
            'org2/dep4',
            'org2/dep5',
            'org3/dep6', // depends on org4/dep7
            'org4/dep7',
        ], null);

        $composerLock = new ComposerLock([
            'packages' => [
                [
                    'name' => 'org/dep1',
                    'require' => [
                        'org1/dep2' => '*',
                    ],
                ],
                [
                    'name' => 'org1/dep2',
                    'require' => [
                        'org1/dep3' => '*',
                        'org2/dep4' => '*',
                    ],
                ],
                [
                    'name' => 'org1/dep3',
                    'require' => [
                        'org/dep1' => '*',
                        'org2/dep5' => '*',
                    ],
                ],
                [
                    'name' => 'org2/dep4',
                    'require' => [
                        // empty
                    ],
                ],
                [
                    'name' => 'org3/dep6',
                    'require' => [
                        'org4/dep7' => '*',
                    ],
                ],
                [
                    'name' => 'org2/dep5',
                ],
                [
                    'name' => 'org4/dep7',
                ],
            ],
        ]);

        $actual = $composerLock->getFlatDependencyTreeFor(['org/dep1', 'org4/dep7'], ['org1/dep3']);
        $expected = [
            $composerLock->getDependency('org/dep1'),
            $composerLock->getDependency('org4/dep7'),
            $composerLock->getDependency('org1/dep2'),
            $composerLock->getDependency('org2/dep4'),
        ];

        $this->assertEquals($expected, $actual);
    }


    public function test_getFlatDependencyTreeFor_returnsFlatDependencyTree_forGivenDependencies()
    {
        $rootPath = $this->setUpTestProject([], [
            'org/dep1', // depends on org1/dep2
            'org1/dep2', // depends on org1/dep3, org2/dep4
            'org1/dep3', // depends on org/dep1 (cycle), org2/dep5
            'org2/dep4',
            'org2/dep5',
            'org3/dep6', // depends on org4/dep7
            'org4/dep7',
        ], null);

        $composerLock = new ComposerLock([
            'packages' => [
                [
                    'name' => 'org/dep1',
                    'require' => [
                        'org1/dep2' => '*',
                        'php' => '*',
                    ],
                ],
                [
                    'name' => 'org1/dep2',
                    'require' => [
                        'org1/dep3' => '*',
                        'org2/dep4' => '*',
                    ],
                ],
                [
                    'name' => 'org1/dep3',
                    'require' => [
                        'org/dep1' => '*',
                        'org2/dep5' => '*',
                        'ext-mbstring' => '*',
                    ],
                ],
                [
                    'name' => 'org2/dep4',
                    'require' => [
                        // empty
                    ],
                ],
                [
                    'name' => 'org3/dep6',
                    'require' => [
                        'org4/dep7',
                    ],
                ],
                [
                    'name' => 'org2/dep5',
                ],
                [
                    'name' => 'org4/dep7',
                ],
            ],
        ]);

        $actual = $composerLock->getFlatDependencyTreeFor(['org/dep1', 'org4/dep7']);
        $expected = [
            $composerLock->getDependency( 'org/dep1'),
            $composerLock->getDependency( 'org4/dep7'),
            $composerLock->getDependency( 'org1/dep2'),
            $composerLock->getDependency( 'org1/dep3'),
            $composerLock->getDependency( 'org2/dep4'),
            $composerLock->getDependency( 'org2/dep5'),
        ];

        $this->assertEquals($expected, $actual);
    }
}
