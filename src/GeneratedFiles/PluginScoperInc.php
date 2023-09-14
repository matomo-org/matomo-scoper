<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\GeneratedFiles;

use Matomo\Scoper\GeneratedFile;

class PluginScoperInc extends GeneratedFile
{
    // TODO: different type declarations
    private string $pluginName;

    public function __construct(string $repoPath, string $pluginName)
    {
        parent::__construct($repoPath . '/scoper.inc.php');

        $this->pluginName = $pluginName;
    }

    public function getContent(): ?string
    {
        $scoperIncFileContents = <<<EOF
<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Isolated\Symfony\Component\Finder\Finder;

\$dependenciesToPrefix = json_decode(getenv('MATOMO_DEPENDENCIES_TO_PREFIX'), true);
\$namespacesToPrefix = json_decode(getenv('MATOMO_NAMESPACES_TO_PREFIX'), true);

return [
    'prefix' => 'Matomo\\Dependencies\\{$this->pluginName}',
    'finders' => array_map(function (\$dependency) {
        return Finder::create()
            ->files()
            ->in(\$dependency);
    }, \$dependenciesToPrefix),
    'patchers' => [
        // define custom patchers here
    ],
    'include-namespaces' => \$namespacesToPrefix,
];
EOF;

        return $scoperIncFileContents;
    }
}
