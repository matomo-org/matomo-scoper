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
    public function __construct(string $repoPath, private readonly string $pluginName)
    {
        parent::__construct($repoPath . '/scoper.inc.php');
    }

    // TODO: move this to a file and use a new environment variable for the plugin name
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
\$isRenamingReferences = getenv('MATOMO_RENAME_REFERENCES') == 1;

\$namespacesToExclude = [];
\$forceNoGlobalAlias = false;

if (\$isRenamingReferences) {
    \$finders = [
        Finder::create()
            ->files()
            ->in(__DIR__)
            ->exclude('vendor')
            ->exclude('node_modules')
            ->exclude('lang')
            ->exclude('javascripts')
            ->exclude('vue')
            ->notName('scoper.inc.php')
            ->filter(function (\SplFileInfo \$file) {
                return !(\$file->isLink() && \$file->isDir());
            })
            ->filter(function (\SplFileInfo \$file) {
                return !(\$file->isLink() && !\$file->getRealPath());
            }),
    ];
} else {
    \$finders = array_map(function (\$dependency) {
        return Finder::create()
            ->files()
            ->in(\$dependency);
    }, \$dependenciesToPrefix);
}

\$namespacesToIncludeRegexes = array_map(function (\$n) {
    \$n = rtrim(\$n, '\\\\');
    return '/^' . preg_quote(\$n) . '(?:\\\\\\\\|\$)/';
}, \$namespacesToPrefix);

return [
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'force-no-global-alias' => \$forceNoGlobalAlias,
    'prefix' => 'Matomo\\Dependencies\\{$this->pluginName}',
    'finders' => \$finders,
    'patchers' => [
        // define custom patchers here
    ],
    'include-namespaces' => \$namespacesToIncludeRegexes,
    'exclude-namespaces' => \$namespacesToExclude,
    'exclude-constants' => [
        '/^self::/', // work around php-scoper bug
    ],
];
EOF;

        return $scoperIncFileContents;
    }
}
