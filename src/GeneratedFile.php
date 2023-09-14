<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

abstract class GeneratedFile
{
    /**
     * @var string
     */
    protected $outputPath;

    public function __construct(string $outputPath)
    {
        $this->outputPath = $outputPath;
    }

    public abstract function getContent(): ?string;

    public function exists(): bool
    {
        return is_file($this->outputPath);
    }

    public function write(): void
    {
        $content = $this->getContent();
        if ($content === null) {
            return;
        }

        file_put_contents($this->outputPath, $content);
    }

    public function writeIfNotExists(): bool
    {
        if ($this->exists()) {
            return false;
        }

        $this->write();
        return true;
    }

    public function remove(): void
    {
        if (is_file($this->outputPath)) {
            unlink($this->outputPath);
        }
    }
}
