<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

/**
 * Moves PHP 8 attributes onto a line of their own so scoped dependencies still parse on older PHP versions.
 *
 * PHP 7 reads `#[` as a comment running to the end of the line. An attribute sitting alone on a line is therefore
 * inert there while still applying on PHP 8, which is the style Matomo core uses. Written inline, as vendors commonly
 * do for parameters, it instead comments out the rest of the signature and causes a parse error:
 *
 *     public function decode(string $jwt, #[\SensitiveParameter] $key) {}
 *
 * Attributes are deliberately kept rather than downgraded away, since they still do useful work on PHP 8, so they are
 * reformatted here instead.
 */
class AttributeFormatter
{
    /**
     * Reformat every PHP file below the given directory.
     *
     * @return string[] The paths that changed.
     */
    public function formatDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $changed = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $original = file_get_contents($path);
            if ($original === false) {
                continue;
            }

            $formatted = $this->format($original);
            if ($formatted !== $original) {
                file_put_contents($path, $formatted);
                $changed[] = $path;
            }
        }

        return $changed;
    }

    /**
     * Put every attribute in the given source on a line of its own.
     */
    public function format(string $code): string
    {
        if (!defined('T_ATTRIBUTE')) {
            // Running on a PHP where attributes are already only comments, so there is nothing to move
            return $code;
        }

        // Tokenising rather than matching text keeps strings such as preg_match('#[abc]#', ...), which look like an
        // attribute, from being rewritten
        $tokens = token_get_all($code);
        $count = count($tokens);
        $result = '';

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token) || $token[0] !== T_ATTRIBUTE) {
                $result .= is_array($token) ? $token[1] : $token;
                continue;
            }

            $indent = $this->indentOfLineBeingWritten($result);

            // Copy the attribute across, counting brackets so that arrays in its arguments do not end it early
            $depth = 0;
            for (; $i < $count; $i++) {
                $inner = $tokens[$i];
                $text = is_array($inner) ? $inner[1] : $inner;
                $result .= $text;

                if ((is_array($inner) && $inner[0] === T_ATTRIBUTE) || $text === '[') {
                    $depth++;
                } elseif ($text === ']') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }

            if (!$this->hasCodeBeforeNextNewline($tokens, $i + 1)) {
                continue;
            }

            // The extra indent marks what follows as a continuation of the line the attribute was written on
            $result .= "\n" . $indent . '    ';
            $this->dropSeparatingSpace($tokens, $i);
        }

        return $result;
    }

    /**
     * Leading whitespace of the line currently being written, so what follows the attribute lines up with it.
     */
    private function indentOfLineBeingWritten(string $emitted): string
    {
        $lineStart = strrpos($emitted, "\n");
        $line = $lineStart === false ? $emitted : substr($emitted, $lineStart + 1);

        preg_match('/^[ \t]*/', $line, $matches);

        return $matches[0];
    }

    /**
     * Whether anything other than horizontal whitespace follows before the next newline.
     *
     * @param array<int, array{0: int, 1: string}|string> $tokens
     */
    private function hasCodeBeforeNextNewline(array $tokens, int $index): bool
    {
        $count = count($tokens);

        for ($i = $index; $i < $count; $i++) {
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            $newline = strpos($text, "\n");

            if ($newline !== false) {
                return trim(substr($text, 0, $newline)) !== '';
            }

            if (trim($text) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Drop the space that separated an inline attribute from whatever followed it.
     *
     * @param array<int, array{0: int, 1: string}|string> $tokens
     */
    private function dropSeparatingSpace(array &$tokens, int $index): void
    {
        $next = $index + 1;

        if (!isset($tokens[$next]) || !is_array($tokens[$next]) || $tokens[$next][0] !== T_WHITESPACE) {
            return;
        }

        if (strpos($tokens[$next][1], "\n") !== false) {
            return;
        }

        $tokens[$next][1] = '';
    }
}
