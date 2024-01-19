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
    const VERSION = '0.1.1';

    public function __construct()
    {
        parent::__construct(self::APP_NAME, self::VERSION);
        $this->add(new ScopeCommand());
        $this->setDefaultCommand(ScopeCommand::NAME);
    }
}
