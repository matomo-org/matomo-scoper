<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Composer;

class ComposerJson
{
    private array $contents;

    public function __construct(private readonly string $path)
    {
        if (!is_file($this->path)) {
            throw new \Exception('Cannot open composer json file at ' . $this->path);
        }

        $this->contents = json_decode(file_get_contents($this->path), true);
    }

    public function getAllTopLevelDependencies(): array
    {
        $dependencies = array_keys($this->contents['require'] ?? []);
        $dependencies = array_filter($dependencies, function ($name) {
            return $name !== 'php' && strpos($name, 'ext-') !== 0;
        });
        return $dependencies;
    }


    public function getAllReplacedDependencies(): array
    {
        return array_keys($this->contents['replace'] ?? []);
    }
}
