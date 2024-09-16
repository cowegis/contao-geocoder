<?php

declare(strict_types=1);

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleApplicationBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\Builder\ConsoleCommandBuilderInterface;
use Phpcq\PluginApi\Version10\Definition\ExecTaskDefinitionBuilderInterface;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\ExecPluginInterface;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\OutputWritingTaskInterface;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Phpcq\PluginApi\Version10\Util\BufferedLineReader;

return new class implements DiagnosticsPluginInterface, ExecPluginInterface
{
    private const RECTOR_FILE_TEMPLATE = <<<'PHP'
<?php

declare(strict_types=1);

use Rector\Configuration\RectorConfigBuilder;

return (function(
    string $config,
    string $workingDirectory,
    array $directories,
    int $threads
) {
    $builder = require_once $config;
    assert($builder instanceof RectorConfigBuilder);

    // Configure parallel
    if ($threads > 1) {
        $builder->withParallel(maxNumberOfProcess: $threads);
    } else {
        $builder->withoutParallel();
    }

    // Make sure that project directories are configured
    $pathsProperty = new ReflectionProperty(RectorConfigBuilder::class, 'paths');
    $directories   = array_map(static fn (string $directory) => $workingDirectory . '/' . $directory, $directories);
    $directories   = array_unique(array_merge($directories, $pathsProperty->getValue($builder)));

    $builder->withPaths($directories);
    
    return $builder;
})(%s, %s, %s, %s);
PHP;

    public function getName(): string
    {
        return 'rector';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder
            ->describeStringOption('config', 'Path to config file')
            ->withDefaultValue('rector.php')
            ->isRequired();

        $configOptionsBuilder
            ->describeBoolOption(
                'dry-run',
                'Only see the diff of changes, do not save them to files.',
            )
            ->withDefaultValue(true)
            ->isRequired();
    }

    public function createDiagnosticTasks(
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment,
    ): iterable {
        $factory = $environment->getTaskFactory()
            ->buildRunProcess($this->getName(), $this->buildArguments($config, $environment))
            ->withWorkingDirectory($environment->getProjectConfiguration()->getProjectRootPath())
            ->withOutputTransformer($this->createOutputTransformerFactory($config->getBool('dry-run')));

        if (! $config->getBool('dry-run')) {
            $factory->forceSingleProcess();
        }

        yield $factory->build();
    }

    public function describeExecTask(
        ExecTaskDefinitionBuilderInterface $definitionBuilder,
        EnvironmentInterface $environment,
    ): void {
        $parser = new class {
            public function parse(
                string $output,
                ConsoleApplicationBuilderInterface|ConsoleCommandBuilderInterface $builder,
            ): void {
                $this->parseOutput(
                    $output,
                    function (string $currentSection, string $line) use ($builder): void {
                        match ($currentSection) {
                            'options' => $this->parseOption($line, $builder),
                            'arguments' => $this->parseArgument($line, $builder),
                            default => null,
                        };
                    }
                );
            }

            public function parseCommands(
                string $output,
                ConsoleApplicationBuilderInterface|ConsoleCommandBuilderInterface $builder,
            ): void {
                $this->parseOutput(
                    $output,
                    function (string $currentSection, string $line) use ($builder): void {
                        if ($currentSection !== 'available commands') {
                            return;
                        }

                        $this->parseCommand($line, $builder);
                    }
                );
            }

            private function parseOutput(string $output, callable $lineParser): void
            {
                $lines          = explode("\n", $output);
                $currentSection = '';

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }

                    // Check for section headers
                    if (str_ends_with($line, ':')) {
                        $currentSection = strtolower(trim(substr($line, 0, -1)));
                        continue;
                    }

                    $lineParser($currentSection, $line);
                }
            }

            private function parseOption(
                string $line,
                ConsoleApplicationBuilderInterface|ConsoleCommandBuilderInterface $builder,
            ): void {
                if (
                    preg_match(
                        '#(-([^,]+), )?--([\w-]+)(\|--([\w-]+))?(=([A-Z-]+))?\s*(.*)#',
                        $line,
                        $matches
                    ) === false
                ) {
                    return;
                }

                foreach (array_filter([$matches[3], $matches[5]]) as $name) {
                    $option = $builder->describeOption($name, $matches[8]);
                    if ($matches[7]) {
                        $option->withOptionalValue($matches[7]);
                    }

                    if ($matches[2]) {
                        $option->withShortcut($matches[2]);
                    }
                }
            }

            private function parseArgument(
                string $line,
                ConsoleApplicationBuilderInterface|ConsoleCommandBuilderInterface $builder,
            ): void {
                if (preg_match('/([\w-]+)\s*(.*)/', $line, $matches) === false) {
                    return;
                }

                $builder->describeArgument($matches[1], $matches[2]);
            }

            private function parseCommand(
                string $line,
                ConsoleApplicationBuilderInterface|ConsoleCommandBuilderInterface $builder,
            ): void {
                if (! $builder instanceof ConsoleApplicationBuilderInterface) {
                    return;
                }

                if (preg_match('/([\w-]+)\s*(.*)/', $line, $matches) === false) {
                    return;
                }

                $builder->describeCommand($matches[1], $matches[2]);
            }
        };

        $application = $definitionBuilder->describeApplication(
            'Upgrades or refactors source code with provided rectors',
        );
        $task        = $this->buildRunTask($environment, ['--help']);
        if ($task instanceof OutputWritingTaskInterface) {
            $output = $this->createOutputToStringHandler();
            $task->runForOutput($output);
            $parser->parse((string) $output, $application);
        }

        $task = $this->buildRunTask($environment, ['list']);
        if ($task instanceof OutputWritingTaskInterface) {
            $output = $this->createOutputToStringHandler();
            $task->runForOutput($output);
            $parser->parseCommands((string) $output, $application);
        }
    }

    /** {@inheritDoc} */
    public function createExecTask(
        string|null $application,
        array $arguments,
        EnvironmentInterface $environment,
    ): TaskInterface {
        return $this->buildRunTask($environment, $arguments);
    }

    /** @param list<string> $arguments */
    private function buildRunTask(EnvironmentInterface $environment, array $arguments): TaskInterface
    {
        array_unshift($arguments, $environment->getInstalledDir() . '/vendor/bin/rector');

        return $environment->getTaskFactory()
            ->buildRunProcess('rector', $arguments)
            ->forceSingleProcess()
            ->build();
    }

    /** @return list<string> */
    private function buildArguments(PluginConfigurationInterface $config, EnvironmentInterface $environment): array
    {
        $arguments = [
            $environment->getInstalledDir() . '/vendor/bin/rector',
            '--output-format=json',
            '--config=' . $this->createTemporaryRectorPhpFile($environment, $config),
        ];

        if ($config->getBool('dry-run')) {
            $arguments[] = '--dry-run';
        }

        return $arguments;
    }

    private function createTemporaryRectorPhpFile(
        EnvironmentInterface $environment,
        PluginConfigurationInterface $config,
    ): string {
        $projectRoot  = $environment->getProjectConfiguration()->getProjectRootPath();
        $rectorPhp    = $environment->getUniqueTempFile($this, 'rector.php');
        $customConfig = sprintf(
            self::RECTOR_FILE_TEMPLATE,
            var_export($projectRoot . '/' . $config->getString('config'), true),
            var_export($projectRoot, true),
            var_export($environment->getProjectConfiguration()->getDirectories(), true),
            var_export($environment->getProjectConfiguration()->getMaxCpuCores(), true),
        );
        file_put_contents($rectorPhp, $customConfig);

        return $rectorPhp;
    }

    private function createOutputTransformerFactory(bool $dryRun): OutputTransformerFactoryInterface
    {
        return new class ($dryRun) implements OutputTransformerFactoryInterface
        {
            public function __construct(private readonly bool $dryRun)
            {
            }

            public function createFor(TaskReportInterface $report): OutputTransformerInterface
            {
                /**
                 * @psalm-type TRectorError = array{
                 *     message: string,
                 *     file: non-empty-string,
                 *     line: int,
                 * }
                 * @psalm-type TRectorFileDiff = array{
                 *     file: non-empty-string,
                 *     diff: non-empty-string,
                 *     applied_rectors: list<non-empty-string>,
                 *     applied_rectors_with_changelog: array<non-empty-string,non-empty-string>
                 * }
                 * @psalm-type TRectorJson = array{
                 *     fatal_errors?: list<non-empty-string>,
                 *     totals?: array{
                 *         changed_files: int,
                 *         errors: int,
                 *     },
                 *     errors?: list<TRectorError>,
                 *     file_diffs: list<TRectorFileDiff>,
                 * }
                 */
                return new class ($report, $this->dryRun) implements OutputTransformerInterface
                {
                    private readonly BufferedLineReader $data;

                    public function __construct(
                        private readonly TaskReportInterface $report,
                        private readonly bool $dryRun,
                    ) {
                        $this->data = BufferedLineReader::create();
                    }

                    public function write(string $data, int $channel): void
                    {
                        $this->data->push($data);
                    }

                    public function finish(int $exitCode): void
                    {
                        $this->process();
                        $this->report->close(0 === $exitCode
                            ? TaskReportInterface::STATUS_PASSED
                            : TaskReportInterface::STATUS_FAILED);
                    }

                    private function process(): void
                    {
                        $this->report->addAttachment('output.log')->fromString($this->data->getData());

                        try {
                            /** @psalm-var TRectorJson $data */
                            $data = json_decode($this->data->getData(), true, flags: JSON_THROW_ON_ERROR);
                        } catch (JsonException $exception) {
                            $this->report->addDiagnostic(
                                TaskReportInterface::SEVERITY_FATAL,
                                'Unable to parse output: ' . $exception->getMessage(),
                            );

                            return;
                        }

                        if (isset($data['fatal_errors'])) {
                            $this->report->addDiagnostic(
                                TaskReportInterface::SEVERITY_FATAL,
                                'Fatal errors detected: ' . implode(', ', $data['fatal_errors']),
                            );

                            return;
                        }

                        // No error or changes detected
                        if (
                            !isset($data['totals'])
                            || $data['totals']['changed_files'] === 0 && $data['totals']['errors'] === 0
                        ) {
                            return;
                        }

                        foreach ($data['errors'] ?? [] as $error) {
                            $this->report
                                ->addDiagnostic(TaskReportInterface::SEVERITY_MAJOR, $error['message'])
                                ->forFile($error['file'])
                                ->forRange($error['line']);
                        }

                        $severity = $this->dryRun
                            ? TaskReportInterface::SEVERITY_MARGINAL
                            : TaskReportInterface::SEVERITY_INFO;

                        foreach ($data['file_diffs'] ?? [] as $diff) {
                            $this->report->addDiff($diff['file'])->fromString($diff['diff']);

                            foreach ($diff['applied_rectors'] ?? [] as $rector) {
                                $diagnostic = $this->report
                                    ->addDiagnostic($severity, 'Applied rector: ' . $rector)
                                    ->forFile($diff['file'])->end()
                                    ->fromSource($rector);

                                if (isset($diff['applied_rectors_with_changelog'][$rector])) {
                                    $diagnostic->withExternalInfoUrl($diff['applied_rectors_with_changelog'][$rector]);
                                }
                            }
                        }
                    }
                };
            }
        };
    }

    /** @return Stringable&OutputInterface */
    private function createOutputToStringHandler(): Stringable|OutputInterface
    {
        return new class implements OutputInterface {
            private string $output = '';

            public function write(
                string $message,
                int $verbosity = self::VERBOSITY_NORMAL,
                int $channel = self::CHANNEL_STDOUT,
            ): void {
                if ($channel === self::CHANNEL_STDOUT) {
                    $this->output .= $message;
                }
            }

            public function writeln(
                string $message,
                int $verbosity = self::VERBOSITY_NORMAL,
                int $channel = self::CHANNEL_STDOUT,
            ): void {
                if ($channel === self::CHANNEL_STDOUT) {
                    $this->output .= $message . "\n";
                }
            }

            public function __toString(): string
            {
                return $this->output;
            }
        };
    }
};
