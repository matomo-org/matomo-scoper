<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

class PluginDetails
{
    /**
     * @var string
     */
    private $repoPath;

    /**
     * @var array
     */
    private $pluginDetails;

    public function __construct(string $repoPath)
    {
        $this->repoPath = $repoPath;

        $pluginJsonPath = $repoPath . '/plugin.json';
        if (!is_file($pluginJsonPath)) {
            throw new \Exception("Could not find a plugin.json file at $pluginJsonPath.");
        }

        $pluginJsonContents = file_get_contents($pluginJsonPath);
        $pluginJsonContents = json_decode($pluginJsonContents, true);

        $this->pluginDetails = $pluginJsonContents;
    }

    public function getRepoPath(): string
    {
        return $this->repoPath;
    }

    public function getPluginDetails(): array
    {
        return $this->pluginDetails;
    }

    public function getPluginName(): ?string
    {
        return $this->pluginDetails['name'] ?? null;
    }

    public function getDependenciesToPrefix(): ?array
    {
        return $this->pluginDetails['prefixedDependencies'] ?? null;
    }
}
