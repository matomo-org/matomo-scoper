<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Isolated\Symfony\Component\Finder\Finder;

/**
 * This file is for php-scoper, a tool used when prefixing dependencies.
 * TODO: link to docs here
 */

$dependenciesToPrefix = json_decode(getenv('MATOMO_DEPENDENCIES_TO_PREFIX'), true);
$namespacesToPrefix = json_decode(getenv('MATOMO_NAMESPACES_TO_PREFIX'), true);
$isRenamingReferences = getenv('MATOMO_RENAME_REFERENCES') == 1;

$namespacesToExclude = [];
$forceNoGlobalAlias = false;

if ($isRenamingReferences) {
    $finders = [
        Finder::create()
            ->files()
            ->in(__DIR__)
            ->exclude('build')
            ->exclude('vendor')
            ->exclude('node_modules')
            ->exclude('tmp')
            ->exclude('@types')
            ->exclude('js')
            ->exclude('lang')
            ->notName('*.ini.php')
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && $file->isDir());
            })
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && !$file->getRealPath());
            }),
    ];

    $namespacesToExclude = ['/^$/'];
    $forceNoGlobalAlias = true;
} else {
    $finders = array_map(function ($dependency) {
        return Finder::create()
            ->files()
            ->in($dependency);
    }, $dependenciesToPrefix);
}

$namespacesToIncludeRegexes = array_map(function ($n) {
    $n = rtrim($n, '\\');
    return '/^' . preg_quote($n) . '(?:\\\\|$)/';
}, $namespacesToPrefix);

return [
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'force-no-global-alias' => $forceNoGlobalAlias,
    'prefix' => 'Matomo\\Dependencies',
    'finders' => $finders,
    'patchers' => [
        // patchers for twig
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            // correct use statements in generated templates
            if (preg_match('%twig/src/Node/ModuleNode\\.php$%', $filePath)) {
                return str_replace('"use Twig\\', '"use ' . str_replace('\\', '\\\\', $prefix) . '\\\\Twig\\', $content);
            }

            // correctly scope function calls to twig_... globals (which will not be globals anymore) in strings
            if (strpos($filePath, 'twig/twig') !== false
                || ($isRenamingReferences && preg_match('/\\.php$/', $filePath))
            ) {
                if ($isRenamingReferences) {
                    $content = preg_replace("/([^'\"])(_?twig_[a-z_0-9]+)\\(/", '${1}\\Matomo\\Dependencies\\\${2}(', $content);
                }

                $content = preg_replace("/'(_?twig_[a-z_0-9]+)([('])/", '\'\\Matomo\\Dependencies\\\${1}${2}', $content);
                $content = preg_replace("/\"(_?twig_[a-z_0-9]+)([(\"])/", '"\\\\\\Matomo\\\\\\Dependencies\\\\\\\${1}${2}', $content);

                $content = preg_replace("/([^\\\\])(_?twig_[a-z_0-9]+)\(\"/", '${1}\\\\\\Matomo\\\\\\Dependencies\\\\\\\${2}("', $content);
                $content = preg_replace("/([^\\\\])(_?twig_[a-z_0-9]+)\('/", '${1}\\Matomo\\Dependencies\\\${2}(\'', $content);
            }

            return $content;
        },

        // php-di has trouble w/ core\DI.php, since it has a class named DI and uses the DI namespace. replacing manually here.
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (!$isRenamingReferences || !preg_match('%core/DI\\.php%', $filePath)) {
                return $content;
            }

            $content = str_replace('use DI ', 'use Matomo\\Dependencies\\DI ', $content);
            $content = str_replace('\\DI\\', 'Matomo\\Dependencies\\DI\\', $content);

            return $content;
        },

        // the config/config.php file can sometimes be rendered empty (if, it just has return [], eg)
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if (!$isRenamingReferences || !preg_match('%config/config\\.php%', $filePath)) {
                return $content;
            }

            if (preg_match('/^<\\?php\s+$/', $content)) {
                $content = '<?php return [];';
            }

            return $content;
        },
    ],
    'include-namespaces' => $namespacesToIncludeRegexes,
    'exclude-namespaces' => $namespacesToExclude,
];
