<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\GeneratedFiles;

use Matomo\Scoper\GeneratedFile;

class CoreScoperInc extends GeneratedFile
{
    public function __construct(string $matomoPath)
    {
        parent::__construct($matomoPath . '/scoper.inc.php');
    }

    public function getContent(): ?string
    {
        return file_get_contents(MATOMO_SCOPER_ROOT_PATH . '/resources/core-scoper.inc.php');
    }
}
