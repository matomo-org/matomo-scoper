<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Prefixers;

use Matomo\Scoper\Prefixer;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class CorePrefixer extends Prefixer
{
    const SUPPORTED_CORE_DEPENDENCIES = [
        'twig/twig',
        'monolog/monolog',
        'symfony/monolog-bridge',
        'symfony/event-dispatcher',
        'symfony/console', // new version now depends on service-contracts which symfony/monolog-bridge also depends on
        'php-di/php-di',
    ];

    const DEPENDENCIES_TO_IGNORE = [
        'symfony/polyfill-php80',
        'symfony/polyfill-php73',
    ];

    public function __construct(Paths $paths, Filesystem $filesystem, OutputInterface $output)
    {
        parent::__construct($paths, $filesystem, $output);

        $this->dependenciesToPrefix = self::SUPPORTED_CORE_DEPENDENCIES;
        $this->dependenciesToIgnore = self::DEPENDENCIES_TO_IGNORE;
    }
}
