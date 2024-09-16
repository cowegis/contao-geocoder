<?php

declare(strict_types=1);

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleApplicationBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\ExecTaskDefinitionBuilderInterface;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\PluginApi\Version10\Exception\RuntimeException;
use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\ExecPluginInterface;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Task\OutputWritingTaskInterface;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Phpcq\PluginApi\Version10\Util\CheckstyleReportAppender;

return new class implements DiagnosticsPluginInterface, ExecPluginInterface {
    public function getName(): string
    {
        return 'phpcs';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder->supportDirectories();
        $configOptionsBuilder
            ->describeStringOption('standard', 'The default code style')
            ->withDefaultValue('PSR12');
        $configOptionsBuilder
            ->describeStringListOption('excluded', 'The excluded files and folders.')
            ->isRequired()
            ->withDefaultValue([]);
        $configOptionsBuilder
            ->describeStringListOption(
                'custom_flags',
                'Any custom flags to pass. For valid flags refer to the phpcs documentation.',
            )
            ->isRequired()
            ->withDefaultValue([]);
        $configOptionsBuilder
            ->describeStringListOption(
                'standard_paths',
                'Setting the installed standard paths as relative path to the project root dir.',
            )
            ->isRequired()
            ->withDefaultValue([]);
        $configOptionsBuilder
            ->describeBoolOption(
                'fix',
                'If given, the source will be fixed automatically with phpcbf',
            )
            ->isRequired()
            ->withDefaultValue(false);
        $configOptionsBuilder
            ->describeStringListOption(
                'excluded',
                'List of excluded paths.',
            );
        $configOptionsBuilder
            ->describeStringListOption(
                'excluded_sniffs',
                'List of excluded sniffs.',
            );

        $configOptionsBuilder
            ->describeStringListOption(
                'autoload_paths',
                'List of files to autoload relative to project directory',
            );
    }

    public function createDiagnosticTasks(
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment
    ): iterable {
        $projectRoot = $environment->getProjectConfiguration()->getProjectRootPath();
        $tmpfile     = $environment->getUniqueTempFile($this, 'checkstyle.xml');

        if ($config->getBool('fix')) {
            yield $environment
                ->getTaskFactory()
                ->buildRunPhar('phpcbf', $this->buildArguments($config, $environment, null))
                ->withCosts($environment->getAvailableThreads())
                ->withWorkingDirectory($projectRoot)
                ->build();
        }

        yield $environment
            ->getTaskFactory()
            ->buildRunPhar('phpcs', $this->buildArguments($config, $environment, $tmpfile))
            ->withCosts($environment->getAvailableThreads())
            ->withWorkingDirectory($projectRoot)
            ->withOutputTransformer(CheckstyleReportAppender::transformFile($tmpfile, $projectRoot))
            ->build();
    }

    /** @return string[] */
    private function buildArguments(
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment,
        ?string $tempFile
    ): array {
        $arguments = [];

        if ($config->has('standard')) {
            $arguments[] = '--standard=' . $config->getString('standard');
        }

        if ($config->has('excluded')) {
            if ([] !== ($excluded = $config->getStringList('excluded'))) {
                $arguments[] = '--ignore=' . implode(',', $excluded);
            }
        }

        if ($config->has('excluded_sniffs')) {
            if ([] !== ($excluded = $config->getStringList('excluded_sniffs'))) {
                $arguments[] = '--exclude=' . implode(',', $excluded);
            }
        }

        if ($config->has('custom_flags')) {
            foreach ($config->getStringList('custom_flags') as $value) {
                $arguments[] = $value;
            }
        }

        if ([] !== ($standardPaths = $config->getStringList('standard_paths'))) {
            $projectPath = $environment->getProjectConfiguration()->getProjectRootPath();
            $arguments[] = '--runtime-set';
            $arguments[] = 'installed_paths';
            $arguments[] = implode(',', array_map(
                function ($path) use ($projectPath): string {
                    return realpath($projectPath . '/' . $path);
                },
                $standardPaths,
            ));
        }

        $arguments[] = '--parallel=' . $environment->getAvailableThreads();
        if (null !== $tempFile) {
            $arguments[] = '--report=checkstyle';
            $arguments[] = '--report-file=' . $tempFile;
        }

        if ($config->has('autoload_paths')) {
            $template = <<<'PHP'
<?php 
declare(strict_types=1);

foreach (%s as $path) {
    require_once $path;
}
PHP;
            $tmpFile = $environment->getUniqueTempFile($this, 'phpcs.bootstrap.php');
            $paths   = array_unique(
                array_map(
                    function (string $path) use ($environment): string {
                        $file = $environment->getProjectConfiguration()->getProjectRootPath() . '/' . $path;
                        if (! file_exists($file)) {
                            throw new RuntimeException('Autoload file does not exist: ' . $file);
                        }

                        return $environment->getProjectConfiguration()->getProjectRootPath() . '/' . $path;
                    },
                    $config->getStringList('autoload_paths')
                ),
            );
            file_put_contents($tmpFile, sprintf($template, var_export($paths, true)));
            $arguments[] = '--bootstrap=' . $tmpFile;
        }

        return array_merge($arguments, $config->getStringList('directories'));
    }

    public function describeExecTask(
        ExecTaskDefinitionBuilderInterface $definitionBuilder,
        EnvironmentInterface $environment
    ): void {

        $this->describeApplication(
            'phpcs',
            'PHP CodeSniffer by Squiz (http://www.squiz.net)',
            $definitionBuilder,
            $environment,
        );
        $this->describeApplication(
            'phpcbf',
            'PHP Code Beautifier and Fixer',
            $definitionBuilder,
            $environment,
            'fix',
        );
    }

    public function createExecTask(
        ?string $application,
        array $arguments,
        EnvironmentInterface $environment
    ): TaskInterface {
        switch ($application) {
            case null:
                return $environment->getTaskFactory()->buildRunPhar('phpcs', $arguments)->build();

            case 'fix':
                return $environment->getTaskFactory()->buildRunPhar('phpcbf', $arguments)->build();

            default:
                throw new RuntimeException('Unknown application "' . $application . '"');
        }
    }

    private function describeApplication(
        string $tool,
        string $description,
        ExecTaskDefinitionBuilderInterface $definitionBuilder,
        EnvironmentInterface $environment,
        ?string $applicationName = null
    ): void {
        $application = $definitionBuilder->describeApplication($description, $applicationName);

        $task = $environment->getTaskFactory()->buildRunPhar($tool, ['--help'])->build();
        if ($task instanceof OutputWritingTaskInterface) {
            $parser = $this->createHelpParser();
            $task->runForOutput($parser);
            /** @psalm-suppress UndefinedInterfaceMethod */
            $parser->parse($application);
        }
    }

    private function createHelpParser(): OutputInterface
    {
        return new class implements OutputInterface
        {
            /** @var string */
            private $help = '';

            /** @var array<string,string> */
            private $descriptions = [];

            /** @var array<string,array{description:string, short?: bool, paramName?: string|null, keyValue?: bool}> */
            private $options = [];

            /** @var array<string,string> */
            private $arguments = [];

            public function write(
                string $message,
                int $verbosity = self::VERBOSITY_NORMAL,
                int $channel = self::CHANNEL_STDOUT
            ): void {
                if ($channel === self::CHANNEL_STDOUT) {
                    $this->help .= $message;
                }
            }

            public function writeln(
                string $message,
                int $verbosity = self::VERBOSITY_NORMAL,
                int $channel = self::CHANNEL_STDOUT
            ): void {
                if ($channel === self::CHANNEL_STDOUT) {
                    $this->help .= $message . "\n";
                }
            }

            public function parse(ConsoleApplicationBuilderInterface $application): void
            {
                $this->doParse();
                $this->describe($application);
            }

            private function doParse(): void
            {
                preg_match('#Usage: [a-z]+ (.+)\n\n(.+)\n\n(.+)\n\n(.+)#s', $this->help, $blocks);

                // Parse descriptions first so other block can use them
                if (isset($blocks[4])) {
                    $this->parseDescriptions($blocks[4]);
                }

                // Parse usage
                if (isset($blocks[1])) {
                    $this->parseUsage($blocks[1]);
                }

                // Parse short option descriptions
                if (isset($blocks[2])) {
                    $this->parseShortOptionDescriptions($blocks[2]);
                }

                // Parse option descriptions
                if (isset($blocks[3])) {
                    $this->parseOptionDescriptions($blocks[3]);
                }
            }

            private function describe(ConsoleApplicationBuilderInterface $application): void
            {
                foreach ($this->arguments as $argument => $description) {
                    $argument = $application->describeArgument($argument, $description);

                    if (stripos($description, 'one or more') !== false) {
                        $argument->isArray();
                    }
                }

                foreach ($this->options as $option => $config) {
                    $definition = $application->describeOption($option, $config['description']);

                    if ($config['short'] ?? false) {
                        $definition->withShortcutOnly();
                    }

                    if (($config['paramName'] ?? null) !== null) {
                        /** @psalm-suppress PossiblyUndefinedArrayOffset */
                        $definition->withRequiredValue($config['paramName']);
                    }

                    // Fixme: Is there a way to detect it properly?
                    if ($config['keyValue'] ?? false) {
                        $definition->withOptionValueSeparator(' ');
                        $definition->withKeyValueMap(true);
                    }
                }
            }

            private function parseDescriptions(string $descriptions): void
            {
                $lines = explode("\n", $descriptions);
                foreach ($lines as $line) {
                    preg_match('#^\s+<([^>]+)>\s+(.*)$#', $line, $matches);
                    if (isset($matches[1])) {
                        $this->descriptions[$matches[1]] = $matches[2];
                    }
                }
            }

            private function parseUsage(string $help): void
            {
                preg_match_all('#\[-(-?)([a-z-]+)(=<([^\]]+)>)?\]#', $help, $matches);
                foreach ($matches[2] as $index => $option) {
                    if ($matches[1][$index] === '-') {
                        $this->options[$option] = [
                            'description' => $this->descriptions[$matches[4][$index] ?? $option] ?? '',
                            'paramName'   => $matches[4][$index] ?: null,
                            'short'       => false,
                        ];
                    } else {
                        foreach (str_split($option, 1) as $shortOption) {
                            $this->options[$shortOption] = [
                                'description' => $this->descriptions[$matches[4][$index] ?? $option] ?? '',
                                'short'       => true,
                            ];
                        }
                    }
                }

                preg_match_all('#\[-(-?)([a-z-]+)\s([^=]+)\[=(.*)\]\]#', $help, $matches);
                foreach ($matches[2] as $index => $option) {
                    $this->options[$option] = [
                        'description' => $this->descriptions[$matches[4][$index]] ?? '',
                        'keyValue'    => true,
                        'short'       => $matches[1][$index] !== '-',
                    ];
                }

                preg_match_all('#\s<([^>]+)>#', $help, $matches);
                foreach ($matches[1] as $match) {
                    $this->arguments[$match] = $this->descriptions[$match] ?? '';
                }
            }

            private function parseShortOptionDescriptions(string $help): void
            {
                $lines = explode("\n", $help);
                $currentName = null;
                $currentDescription = '';

                foreach ($lines as $line) {
                    preg_match('#^\s+(-[a-z-]*)?\s+(.*)$#', $line, $matches);

                    if ($matches[1] === '') {
                        $currentDescription .= ' ' . trim($matches[2]);
                        continue;
                    }

                    if (null !== $currentName) {
                        $this->options[$currentName]['short'] = true;
                        $this->options[$currentName]['description'] = $currentDescription;
                    }

                    $currentName = substr($matches[1], 1);
                    $currentDescription = $matches[2];
                }

                if ($currentName !== null) {
                    $this->options[$currentName]['short'] = true;
                    $this->options[$currentName]['description'] = $currentDescription;
                }
            }

            private function parseOptionDescriptions(string $help): void
            {
                $lines = explode("\n", $help);
                $currentName = null;
                $currentDescription = '';

                foreach ($lines as $line) {
                    preg_match('#^\s+(--[a-z-]*)?\s+(.*)$#', $line, $matches);
                    if ($matches === []) {
                        continue;
                    }

                    if ($matches[1] === '') {
                        $currentDescription .= ' ' . trim($matches[2]);
                        continue;
                    }

                    if (null !== $currentName) {
                        $this->options[$currentName]['description'] = $currentDescription;
                    }

                    $currentName = substr($matches[1], 2);
                    $currentDescription = $matches[2];
                }

                if (null !== $currentName) {
                    $this->options[$currentName]['description'] = $currentDescription;
                }
            }
        };
    }
};
