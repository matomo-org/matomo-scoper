<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Utilities;

class Paths
{
    private string $repoPath;

    private string $composerPath;

    public function __construct(string $repoPath, string $composerPath)
    {
        $this->repoPath = $repoPath;
        $this->composerPath = $composerPath;
    }

    public function getRepoPath(): string
    {
        return $this->repoPath;
    }

    public function getPhpBinaryPath(): string
    {
        $phpBinaryInEnv = getenv('MATOMO_PREFIX_PHP');
        if ($phpBinaryInEnv) {
            return $phpBinaryInEnv;
        }

        return PHP_BINARY;
    }

    public function getPhpScoperPath(): string
    {
        return MATOMO_SCOPER_ROOT_PATH . '/php-scoper.phar';
    }

    public function getComposerPath(): string
    {
        return $this->composerPath;
    }
}
