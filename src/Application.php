<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

class Application extends \Symfony\Component\Console\Application
{
    const APP_NAME = 'matomo-scoper';
    const VERSION = '0.1.0';

    public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        parent::__construct(self::APP_NAME, self::VERSION);

        // TODO add commands
    }
}
