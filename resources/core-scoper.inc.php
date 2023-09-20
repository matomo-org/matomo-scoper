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

return [
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'force-no-global-alias' => $forceNoGlobalAlias,
    'prefix' => 'Matomo\\Dependencies',
    'finders' => $finders,
    'patchers' => [
        // patchers for twig
        static function (string $filePath, string $prefix, string $content): string {
            // correct use statements in generated templates
            if (preg_match('%twig/src/Node/ModuleNode\\.php$%', $filePath)) {
                return str_replace('"use Twig\\', '"use ' . str_replace('\\', '\\\\', $prefix) . '\\\\Twig\\', $content);
            }

            // correctly scoped function calls to twig_... globals (which will not be globals anymore) in strings
            if (strpos($filePath, 'twig/twig') !== false) {
                $content = preg_replace("/'(_?twig_[a-z_0-9]+)([('])/", '\'\\Matomo\\Dependencies\\\${1}${2}', $content);
                $content = preg_replace("/\"(_?twig_[a-z_0-9]+)([(\"])/", '"\\\\\\Matomo\\\\\\Dependencies\\\\\\\${1}${2}', $content);

                $content = preg_replace("/([^\\\\])(_?twig_[a-z_0-9]+)\(\"/", '${1}\\\\\\Matomo\\\\\\Dependencies\\\\\\\${2}("', $content);
                $content = preg_replace("/([^\\\\])(_?twig_[a-z_0-9]+)\('/", '${1}\\Matomo\\Dependencies\\\${2}(\'', $content);
            }

            return $content;
        },
    ],
    'include-namespaces' => array_map(function ($n) {
        $n = rtrim($n, '\\');
        return '/^' . preg_quote($n) . '(?:\\|$)/';
    }, $namespacesToPrefix),
    'exclude-namespaces' => $namespacesToExclude,
];
