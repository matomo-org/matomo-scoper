<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests\GeneratedFiles;

use Matomo\Scoper\GeneratedFiles\ProxyAutoloader;
use Matomo\Scoper\Tests\Framework\ComposerTestCase;
use Symfony\Component\Console\Output\NullOutput;

class ProxyAutoloaderTest extends ComposerTestCase
{
    public function test_write_overwritesTheExistingComposerAutoloader()
    {
        $rootPath = $this->setUpTestProject([], [], []);
        $this->putTestProjectFile(
            'vendor/autoload.php',
            <<<EOF
            <?php
            
            // ... autoloader contents ...
            EOF
        );

        $file = new ProxyAutoloader($rootPath . '/vendor', new NullOutput());
        $file->write();

        $this->assertFileExists($rootPath . '/vendor/autoload_original.php');
        $this->assertStringContainsString('... autoloader contents ...', file_get_contents($rootPath . '/vendor/autoload_original.php'));

        $this->assertFileExists($rootPath . '/vendor/autoload.php');
        $this->assertStringContainsString(ProxyAutoloader::PROXY_FILE_MARKER, file_get_contents($rootPath . '/vendor/autoload.php'));
    }

    public function test_write_doesNotWrite_ifAnOriginalComposerAutoloader_isNotFound()
    {
        $rootPath = $this->setUpTestProject([], [], []);
        $this->assertFileDoesNotExist($rootPath . '/vendor/autoload.php');

        $file = new ProxyAutoloader($rootPath . '/vendor', new NullOutput());
        $file->write();

        $this->assertFileDoesNotExist($rootPath . '/vendor/autoload.php');
        $this->assertFileDoesNotExist($rootPath . '/vendor/autoload_original.php');
    }

    public function test_write_doesNotWrite_ifExistingAutoloaderFile_isTheProxyAutoloader()
    {
        $proxyFileMarker = ProxyAutoloader::PROXY_FILE_MARKER;
        $existingAutoloadContents = <<<EOF
        <?php
        
        $proxyFileMarker
        
        // ...
        EOF;

        $rootPath = $this->setUpTestProject([], [], []);
        $this->putTestProjectFile('vendor/autoload.php', $existingAutoloadContents);

        $file = new ProxyAutoloader($rootPath . '/vendor', new NullOutput());
        $file->write();

        $this->assertFileDoesNotExist($rootPath . '/vendor/autoload_original.php');
        $this->assertEquals($existingAutoloadContents, file_get_contents($rootPath . '/vendor/autoload.php'));
    }
}
