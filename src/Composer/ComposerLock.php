<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper\Composer;

class ComposerLock
{
    public function __construct(private readonly array $contents) {}

    /**
     * @param string[] $dependenciesToPrefix
     * @param string[] $dependenciesToIgnore
     * @return ComposerJson[]
     */
    public function getFlatDependencyTreeFor(array $dependenciesToPrefix, array $dependenciesToIgnore = []): array
    {
        $flatDependencyTree = [];

        $dependenciesToProcess = array_map(function ($relativePath) {
            return $this->getDependency($relativePath);
        }, $dependenciesToPrefix);
        $dependenciesToProcess = array_filter($dependenciesToProcess);

        while (!empty($dependenciesToProcess)) {
            $dependency = array_shift($dependenciesToProcess);
            $flatDependencyTree[$dependency->getName()] = $dependency;

            $childDependencies = $dependency->getRequires($this);
            foreach ($childDependencies as $childDep) {
                $id = $childDep->getName();

                $alreadyProcessed = !empty($flatDependencyTree[$id]);
                if ($alreadyProcessed
                    || in_array($id, $dependenciesToIgnore)
                ) {
                    continue;
                }

                $dependenciesToProcess[] = $childDep;
            }
        }

        return array_values($flatDependencyTree);
    }

    public function getDependency(string $dependencyName): ?ComposerJson
    {
        $packages = $this->contents['packages'] ?? [];
        foreach ($packages as $package) {
            if ($package['name'] === $dependencyName) {
                return new ComposerJson($package);
            }
        }

        return null;
    }
}
