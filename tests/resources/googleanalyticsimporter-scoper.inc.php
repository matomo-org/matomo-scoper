<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Isolated\Symfony\Component\Finder\Finder;

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
            ->exclude('vendor')
            ->exclude('node_modules')
            ->exclude('lang')
            ->exclude('javascripts')
            ->exclude('vue')
            ->notName('scoper.inc.php')
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && $file->isDir());
            })
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && !$file->getRealPath());
            }),
    ];
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
    'prefix' => 'Matomo\Dependencies\GoogleAnalyticsImporter',
    'finders' => $finders,
    'patchers' => [
        // patcher for google's protobuf related classes which need to prefix class names at runtime
        static function (string $filePath, string $prefix, string $content) use ($isRenamingReferences): string {
            if ($isRenamingReferences) {
                return $content;
            }

            $descriptorClasses = [
                __DIR__ . '/vendor/google/protobuf/src/Google/Protobuf/Internal/Descriptor.php',
                __DIR__ . '/vendor/google/protobuf/src/Google/Protobuf/Internal/EnumDescriptor.php',
            ];
            if (in_array($filePath, $descriptorClasses)) {
                $content = preg_replace_callback('/function (set[^(]+)\(\\$klass\\)\s*\\{/', function (array $matches): string {
                    return <<<EOF
function {$matches[1]}(\$klass)
{
    if (strpos(\$klass, 'Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\') !== 0) {
        \$klass = 'Matomo\\\\Dependencies\\\\GoogleAnalyticsImporter\\\\' . \$klass;
    }
EOF;
                }, $content);
            }

            return $content;
        },
    ],
    'include-namespaces' => $namespacesToIncludeRegexes,
    'exclude-namespaces' => $namespacesToExclude,
];
