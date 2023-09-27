<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Scoper;

use GuzzleHttp\Client;
use Matomo\Scoper\Prefixers\CorePrefixer;
use Matomo\Scoper\Prefixers\PluginPrefixer;
use Matomo\Scoper\Utilities\Paths;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

// TODO: use constructor promotion everywhere
class ScopeCommand extends Command
{
    const NAME = 'scope';

    // using custom build for included-namespaces feature and some other additions
    const PHP_SCOPER_URL = 'https://github.com/matomo-org/php-scoper/releases/download/custom-build-1/php-scoper.phar';

    protected function configure(): void
    {
        parent::configure();

        $this->setName(self::NAME);
        $this->setDescription('Prefix namespaces for core Matomo source code or a Matomo plugin.');
        $this->addArgument('repo_path', InputArgument::OPTIONAL, 'Path to the Matomo source code or the Matomo plugin to prefix.', getcwd());
        $this->addOption('composer-path', null, InputOption::VALUE_REQUIRED,
            'Path to composer. Required to generate a new autoloader.', getenv('COMPOSER_BINARY') ?: 'composer');
        $this->addOption('yes', 'y', InputOption::VALUE_NONE, 'Bypass confirmation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repoPath = $input->getArgument('repo_path');
        $composerPath = $this->getComposerPath($input);
        $bypassConfirmation = $input->getOption('yes');

        $paths = new Paths($repoPath, $composerPath);
        $filesystem = new Filesystem();

        $this->downloadPhpScoperIfNeeded($paths, $output);

        $isCore = !is_file($repoPath . '/plugin.json');
        if ($isCore) {
            $output->writeln("<info>Will scope core matomo at $repoPath</info>");
        } else {
            $output->writeln("<info>Will scope matomo plugin at $repoPath</info>");
        }

        if (!$bypassConfirmation) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you wish to continue? ', false);
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln("Aborting.");
                return Command::SUCCESS;
            }
        }

        if ($isCore) {
            $prefixer = new CorePrefixer($paths, $filesystem, $output);
        } else {
            $prefixer = new PluginPrefixer($paths, $filesystem, $output);
        }

        $output->writeln("<info>Prefixing dependencies...</info>");
        $prefixedDependencies = $prefixer->run();
        if (empty($prefixedDependencies)) {
            $output->writeln("<info>Nothing prefixed.</info>");
            return Command::SUCCESS;
        }

        $output->writeln("");
        $output->writeln("<info>Regenerating autoloader...</info>");

        $autoloaderGenerator = new AutoloaderGenerator($paths, $filesystem, $output, $prefixedDependencies);
        $autoloaderGenerator->generate();

        $output->writeln("<info>Done.</info>");

        return Command::SUCCESS;
    }

    private function getComposerPath(InputInterface $input)
    {
        $composerPath = $input->getOption('composer-path');
        if (empty($composerPath)) {
            throw new \InvalidArgumentException('The --composer-path option is required.');
        }

        if (!is_file($composerPath) && $composerPath !== 'composer' && $composerPath !== 'composer.phar') {
            throw new \InvalidArgumentException('--composer-path value "' . $composerPath . '" is not a file.');
        }

        return $composerPath;
    }

    private function downloadPhpScoperIfNeeded(Paths $paths, OutputInterface $output)
    {
        $outputPath = $paths->getPhpScoperPath();
        if (is_file($outputPath)) {
            $output->writeln("Found existing php-scoper phar.");
            return $outputPath;
        }

        $output->writeln("Downloading php-scoper from github...");

        $client = new Client();
        $client->get(self::PHP_SCOPER_URL, ['sink' => $outputPath]);

        $output->writeln("...Finished.");

        return $outputPath;
    }
}
