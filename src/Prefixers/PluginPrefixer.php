<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Prefixers;

use Matomo\Scoper\GeneratedFiles\PluginScoperInc;
use Matomo\Scoper\PluginDetails;
use Matomo\Scoper\Prefixer;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginPrefixer extends Prefixer
{
    private PluginDetails $pluginDetails;

    public function __construct(Paths $paths, Filesystem $filesystem, OutputInterface $output)
    {
        parent::__construct($paths, $filesystem, $output);

        $this->pluginDetails = new PluginDetails($this->paths->getRepoPath());
        $this->dependenciesToPrefix = $this->pluginDetails->getDependenciesToPrefix();
    }

    public function run(): void
    {
        $scoperIncFile = new PluginScoperInc($this->paths->getRepoPath(), $this->pluginDetails->getPluginName());
        $scoperIncFile->writeIfNotExists();

        parent::run();
    }
}
