<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Tests;

use Matomo\Scoper\AttributeFormatter;
use PHPUnit\Framework\TestCase;

class AttributeFormatterTest extends TestCase
{
    private AttributeFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new AttributeFormatter();
    }

    public function test_format_movesAnInlineParameterAttributeOntoItsOwnLine()
    {
        $code = "<?php\nfunction f(string \$a, #[\\SensitiveParameter] \$b) {}\n";

        $this->assertSame(
            "<?php\nfunction f(string \$a, #[\\SensitiveParameter]\n    \$b) {}\n",
            $this->formatter->format($code)
        );
    }

    public function test_format_leavesAnAttributeAlreadyOnItsOwnLineAlone()
    {
        $code = "<?php\nclass A {\n    #[Override]\n    public function f() {}\n}\n";

        $this->assertSame($code, $this->formatter->format($code));
    }

    public function test_format_ignoresStringsThatMerelyLookLikeAnAttribute()
    {
        // the regex delimiters here would be rewritten by a text based replacement
        $code = "<?php\n\$x = preg_replace('#[^\\d.]#', '', \$y);\n";

        $this->assertSame($code, $this->formatter->format($code));
    }

    public function test_format_handlesAnAttributeWhoseArgumentsContainAnArray()
    {
        $code = "<?php\nfunction f(#[Attr([1, 2])] string \$a) {}\n";

        $this->assertSame(
            "<?php\nfunction f(#[Attr([1, 2])]\n    string \$a) {}\n",
            $this->formatter->format($code)
        );
    }

    public function test_format_leavesNoAttributeWithCodeFollowingItOnTheSameLine()
    {
        $formatted = $this->formatter->format("<?php\nfunction f(#[A] \$a, #[B] \$b) {}\n");

        foreach (explode("\n", $formatted) as $line) {
            if (strpos($line, '#[') === false) {
                continue;
            }

            $this->assertSame('', trim(substr($line, strrpos($line, ']') + 1)), "code follows an attribute on: $line");
        }

        $this->assertStringContainsString('#[A]', $formatted);
        $this->assertStringContainsString('#[B]', $formatted);
    }

    public function test_format_outputParsesOnceAttributeLinesAreTreatedAsComments()
    {
        $formatted = $this->formatter->format(
            "<?php\nfunction f(string \$a, #[\\SensitiveParameter] \$b, ?int \$c = null) {}\n"
        );

        // PHP 7 reads `#[` as a comment to the end of the line, so drop those and check what remains is still valid
        $asSeenByPhp7 = preg_replace('/#\[[^\n]*/', '', $formatted);

        $this->assertNotFalse(@token_get_all($asSeenByPhp7, TOKEN_PARSE));
        $this->assertStringNotContainsString('SensitiveParameter', $asSeenByPhp7);
    }

    public function test_formatDirectory_rewritesOnlyTheFilesThatNeedIt()
    {
        $dir = sys_get_temp_dir() . '/matomo-scoper-attribute-test-' . uniqid();
        mkdir($dir . '/nested', 0777, true);

        file_put_contents($dir . '/inline.php', "<?php\nfunction f(#[A] \$a) {}\n");
        file_put_contents($dir . '/nested/ok.php', "<?php\nclass B {\n    #[A]\n    public \$x;\n}\n");
        file_put_contents($dir . '/notphp.txt', "function f(#[A] \$a) {}");

        try {
            $changed = $this->formatter->formatDirectory($dir);

            $this->assertCount(1, $changed);
            $this->assertStringEndsWith('inline.php', $changed[0]);
            $this->assertSame("function f(#[A] \$a) {}", file_get_contents($dir . '/notphp.txt'));
        } finally {
            array_map('unlink', glob($dir . '/nested/*') ?: []);
            array_map('unlink', glob($dir . '/*.*') ?: []);
            @rmdir($dir . '/nested');
            @rmdir($dir);
        }
    }

    public function test_formatDirectory_toleratesAMissingDirectory()
    {
        $this->assertSame([], $this->formatter->formatDirectory('/does/not/exist'));
    }
}
