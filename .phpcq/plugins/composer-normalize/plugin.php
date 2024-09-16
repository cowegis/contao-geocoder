<?php

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\Definition\ExecTaskDefinitionBuilderInterface;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\ExecPluginInterface;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Task\OutputWritingTaskInterface;
use Phpcq\PluginApi\Version10\Task\TaskInterface;
use Phpcq\PluginApi\Version10\Util\BufferedLineReader;

// phpcs:disable PSR12.Files.FileHeader.IncorrectOrder - This is not the file header but psalm annotations
/**
 * @psalm-type TSeverity = TaskReportInterface::SEVERITY_FATAL
 *  |TaskReportInterface::SEVERITY_MAJOR
 *  |TaskReportInterface::SEVERITY_MINOR
 *  |TaskReportInterface::SEVERITY_MARGINAL
 *  |TaskReportInterface::SEVERITY_INFO
 *  |TaskReportInterface::SEVERITY_NONE
 */
return new class implements DiagnosticsPluginInterface, ExecPluginInterface {
    public function getName(): string
    {
        return 'composer-normalize';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder
            ->describeBoolOption('dry_run', 'Show the results of normalizing, but do not modify any files')
            ->isRequired()
            ->withDefaultValue(true);
        $configOptionsBuilder->describeStringOption('file', 'Path to composer.json file relative to project root');
        $configOptionsBuilder
            ->describeIntOption(
                'indent_size',
                'Indent size (an integer greater than 0); should be used with the indent_style option'
            )
            ->isRequired()
            ->withDefaultValue(2);
        $configOptionsBuilder
            ->describeStringOption(
                'indent_style',
                'Indent style (one of "space", "tab"); should be used with the indent_size option'
            )
            ->isRequired()
            ->withDefaultValue('space');
        $configOptionsBuilder
            ->describeBoolOption('no_update_lock', 'Do not update lock file if it exists');
        $configOptionsBuilder
            ->describeStringListOption('ignore_output', 'Regular expressions for output lines to ignore')
            ->withDefaultValue([]);
    }

    public function createDiagnosticTasks(
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment
    ): iterable {
        $composerJson = $config->has('file') ? $config->getString('file') : 'composer.json';

        yield $environment
            ->getTaskFactory()
            ->buildRunPhar('composer-normalize', $this->buildArguments($config))
            ->withWorkingDirectory($environment->getProjectConfiguration()->getProjectRootPath())
            ->withOutputTransformer(
                $this->createOutputTransformerFactory($composerJson, $config->getStringList('ignore_output'))
            )
            ->build();
    }

    public function describeExecTask(
        ExecTaskDefinitionBuilderInterface $definitionBuilder,
        EnvironmentInterface $environment
    ): void {
        $task = $environment->getTaskFactory()
            ->buildRunPhar('composer-normalize', ['--help', '--format=json'])->build();
        if (!$task instanceof OutputWritingTaskInterface) {
            return;
        }

        $parser = $this->createHelpParser();
        $task->runForOutput($parser);
        $parser->describe($definitionBuilder);
    }

    public function createExecTask(
        ?string $application,
        array $arguments,
        EnvironmentInterface $environment
    ): TaskInterface {
        return $environment->getTaskFactory()->buildRunPhar('composer-normalize', $arguments)->build();
    }

    /** @return string[] */
    private function buildArguments(PluginConfigurationInterface $config): array
    {
        $arguments = [];
        if ($config->has('file')) {
            $arguments[] = $config->getString('file');
        }
        if ($config->getBool('dry_run')) {
            $arguments[] = '--dry-run';
        }
        $arguments[] = '--indent-size';
        $arguments[] = (string) $config->getInt('indent_size');
        $arguments[] = '--indent-style';
        $arguments[] = (string) $config->getString('indent_style');

        if ($config->has('no_update_lock')) {
            $arguments[] = '--no-update-lock';
        }

        return $arguments;
    }

    /** @param list<string> $ignore */
    private function createOutputTransformerFactory(
        string $composerFile,
        array $ignore
    ): OutputTransformerFactoryInterface {
        return new class ($composerFile, $ignore) implements OutputTransformerFactoryInterface {
            /** @var string */
            private $composerFile;
            /** @var list<string> */
            private $ignore;

            /** @param list<string> $ignore */
            public function __construct(string $composerFile, array $ignore)
            {
                $this->composerFile = $composerFile;
                $this->ignore = $ignore;
            }

            public function createFor(TaskReportInterface $report): OutputTransformerInterface
            {
                return new class ($this->composerFile, $report, $this->ignore) implements OutputTransformerInterface {
                    private const REGEX_IN_APPLICATION = '#^In Application\.php line [0-9]*:$#';
                    private const REGEX_NOT_WRITABLE = '#^.* is not writable\.$#';
                    private const REGEX_NOT_NORMALIZED = '#^.* is not normalized\.$#';
                    private const REGEX_IS_NORMALIZED = '#^.* is already normalized\.$#';
                    private const REGEX_XDEBUG_ENABLED = '#^(?<message>Composer is operating slower than normal' .
                    ' because you have Xdebug enabled\. See https://getcomposer\.org/xdebug)$#';
                    private const REGEX_CURL_DISABLED = '#^(?<message>Composer is operating significantly slower' .
                    ' than normal because you do not have the PHP curl extension enabled\.)$#';
                    private const REGEX_LOCK_OUTDATED = '#^(?<message>The lock file is not up to date with the latest' .
                    ' changes in composer\.json, it is recommended that you run `composer update --lock`\.)$#';
                    private const REGEX_SCHEMA_VIOLATION = '#^.* does not match the expected JSON schema:$#';
                    private const REGEX_SKIPPED_COMMAND = '#^(?<message>Plugin command normalize \(.*\) would' .
                    ' override a Composer command and has been skipped)#';
                    private const READING_FILE = '#^Reading.*#';
                    private const LOADING_FILE = '#^Loading.*#';
                    private const CHECKED_CA_OR_DIRECTORY = '#^Checked (?:CA|directory).*#';
                    private const UNCONFIGURED_DOMAIN = '#^(?<message>.* is not in the configured .*, adding it ' .
                    'implicitly as authentication is configured for this domain)#';
                    private const EXECUTING_COMMAND = '#^(?<message>Executing (?:async )?command.*)#';
                    private const RUNNING_VERSION_INFORMATION = '#^(?<message>Running .* with PHP .* on .*)#';

                    /** @var string */
                    private $composerFile;
                    /** @var BufferedLineReader */
                    private $data;
                    /** @var string */
                    private $diff = '';
                    /** @var TaskReportInterface */
                    private $report;
                    /** @var list<string> */
                    private $ignore;
                    private $inDiff = false;

                    /** @param list<string> $ignore */
                    public function __construct(string $composerFile, TaskReportInterface $report, array $ignore)
                    {
                        $this->composerFile = $composerFile;
                        $this->report       = $report;
                        $this->data         = BufferedLineReader::create();
                        $this->ignore       = $ignore;
                    }

                    public function write(string $data, int $channel): void
                    {
                        // strip ansi codes.
                        $ascii = preg_replace('#\[[0-9;]+m#', '', $data);
                        if ('' === $ascii) {
                            return;
                        }
                        if (OutputInterface::CHANNEL_STDOUT === $channel) {
                            // This is the ONLY line that is on output channel instead of error.
                            if (1 === preg_match(self::REGEX_IS_NORMALIZED, $dummy = trim($ascii))) {
                                $this->logDiagnostic(
                                    $this->composerFile . ' is normalized.',
                                    TaskReportInterface::SEVERITY_INFO
                                );
                                return;
                            }
                            // Chop off beginning.
                            if (false !== ($start = \strpos($ascii, "---------- begin diff ----------\n"))) {
                                $ascii = substr($ascii, $start + 33);
                                $this->inDiff = true;
                            }
                            if (!$this->inDiff) {
                                return;
                            }
                            // Translate file name in diff.
                            $ascii = str_replace('--- original', '--- ' . $this->composerFile, $ascii);
                            $ascii = str_replace('+++ normalized', '+++ ' . $this->composerFile, $ascii);

                            // Chop off trailing.
                            if (false !== ($end = \strpos($ascii, "----------- end diff -----------\n"))) {
                                $this->diff .= substr($ascii, 0, $end - 1);
                                $this->inDiff = false;
                            }
                            // Add content.
                            if ($this->inDiff) {
                                $this->diff = $ascii;
                                return;
                            }

                            return;
                        }

                        $this->data->push($ascii);
                    }

                    public function finish(int $exitCode): void
                    {
                        $this->process();
                        $this->report->close(0 === $exitCode
                            ? ReportInterface::STATUS_PASSED
                            : ReportInterface::STATUS_FAILED);
                    }

                    /** @psalm-param TSeverity $severity */
                    private function logDiagnostic(string $message, string $severity): void
                    {
                        /** @psalm-trace $severity */
                        $this->report->addDiagnostic($severity, $message)->forFile($this->composerFile)->end()->end();
                    }

                    private function process(): void
                    {
                        $unknown = [];
                        while (null !== $line = $this->data->fetch()) {
                            if (!$this->processLine($line)) {
                                $unknown[] = $line;
                            }
                        }

                        if ([] !== $unknown) {
                            $this->logDiagnostic(
                                'Did not understand the following tool output: ' . "\n" .
                                implode("\n", $unknown),
                                TaskReportInterface::SEVERITY_MINOR
                            );
                            $this->report
                                ->addAttachment('composer-normalize-raw.txt')
                                ->fromString($this->data->getData())
                                ->end();
                        }

                        if ('' !== $this->diff) {
                            $this->report
                                ->addDiff('composer.json-normalized.diff')
                                ->fromString($this->diff)
                                ->end();
                        }
                    }

                    private function processLine(string $line): bool
                    {
                        // Never process empty lines.
                        if (empty($line)) {
                            return true;
                        }

                        foreach (
                            // Regex => callback (...<named match>): void
                            [
                                self::REGEX_IN_APPLICATION => static function (): void {
                                    // Ignore header.
                                },
                                self::REGEX_NOT_WRITABLE => function (): void {
                                    $this->logDiagnostic(
                                        $this->composerFile . ' is not writable.',
                                        TaskReportInterface::SEVERITY_FATAL
                                    );
                                },
                                self::REGEX_NOT_NORMALIZED => function (): void {
                                    $this->logDiagnostic(
                                        $this->composerFile . ' is not normalized.',
                                        TaskReportInterface::SEVERITY_MAJOR
                                    );
                                },
                                self::REGEX_XDEBUG_ENABLED => function (string $message): void {
                                    $this->logDiagnostic($message, TaskReportInterface::SEVERITY_INFO);
                                },
                                self::REGEX_CURL_DISABLED => function (string $message): void {
                                    $this->logDiagnostic($message, TaskReportInterface::SEVERITY_INFO);
                                },
                                self::REGEX_LOCK_OUTDATED => function (string $message): void {
                                    $this->logDiagnostic($message, TaskReportInterface::SEVERITY_MAJOR);
                                },
                                self::REGEX_SCHEMA_VIOLATION => function (): void {
                                    while (null !== $line = $this->data->peek()) {
                                        if (empty($line)) {
                                            $this->data->fetch();
                                            continue;
                                        }
                                        if ('-' === $line[0]) {
                                            $error = substr($line, 2);
                                            $this->data->fetch();
                                            // Collect wrapped lines.
                                            while (null !== $line = $this->data->peek()) {
                                                if (empty($line)) {
                                                    break;
                                                }
                                                if ('-' !== $line[0]) {
                                                    $error .= ' ' . $line;
                                                    $this->data->fetch();
                                                    continue;
                                                }
                                                break;
                                            }
                                            $this->logDiagnostic($error, TaskReportInterface::SEVERITY_FATAL);
                                        }
                                        if (
                                            'See https://getcomposer.org/doc/04-schema.md for details on the schema'
                                            === $line
                                        ) {
                                            $this->data->fetch();
                                            break;
                                        }
                                    }
                                },
                                self::REGEX_SKIPPED_COMMAND => function (string $message): void {
                                    $this->logDiagnostic($message, TaskReportInterface::SEVERITY_INFO);
                                },
                                self::READING_FILE => static function (): void {
                                    // Ignore.
                                },
                                self::LOADING_FILE => static function (): void {
                                    // Ignore.
                                },
                                self::CHECKED_CA_OR_DIRECTORY => static function (): void {
                                    // Ignore.
                                },
                                self::UNCONFIGURED_DOMAIN => function (string $message): void {
                                    $this->logDiagnostic($message, TaskReportInterface::SEVERITY_INFO);
                                },
                                self::EXECUTING_COMMAND => function (string $message): void {
                                    $this->logDiagnostic($message, TaskReportInterface::SEVERITY_INFO);
                                },
                                self::RUNNING_VERSION_INFORMATION => function (string $message): void {
                                    $this->logDiagnostic($message, TaskReportInterface::SEVERITY_INFO);
                                },
                            ] as $pattern => $handler
                        ) {
                            if (1 === preg_match($pattern, $line, $matches)) {
                                $variables = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                                call_user_func_array($handler, $variables);
                                return true;
                            }
                        }
                        foreach ($this->ignore as $ignore) {
                            if (preg_match($ignore, $line)) {
                                return true;
                            }
                        }
                        return false;
                    }
                };
            }
        };
    }

    private function createHelpParser(): OutputInterface
    {
        return new class implements OutputInterface {
            private $output = '';

            public function write(
                string $message,
                int $verbosity = self::VERBOSITY_NORMAL,
                int $channel = self::CHANNEL_STDOUT
            ): void {
                if ($channel !== OutputInterface::CHANNEL_STDOUT) {
                    return;
                }
                $this->output .= $message;
            }

            public function writeln(
                string $message,
                int $verbosity = self::VERBOSITY_NORMAL,
                int $channel = self::CHANNEL_STDOUT
            ): void {
                if ($channel !== OutputInterface::CHANNEL_STDOUT) {
                    return;
                }
                $this->output .= $message . "\n";
            }

            public function describe(ExecTaskDefinitionBuilderInterface $definitionBuilder): void
            {
                $help = json_decode($this->output, true, JSON_THROW_ON_ERROR);
                $application = $definitionBuilder->describeApplication($help['description']);

                foreach ($help['definition']['arguments'] as $name => $config) {
                    $argument = $application->describeArgument($name, $config['description'] ?? '');
                    if ($config['is_required']) {
                        $argument->isRequired();
                    }
                    if ($config['is_array']) {
                        $argument->isArray();
                    }
                    if ($config['default'] !== null) {
                        $argument->withDefaultValue($config['default']);
                    }
                }

                foreach ($help['definition']['options'] as $name => $config) {
                    $option = $application->describeOption($name, $config['description'] ?? '');
                    if ($config['shortcut']) {
                        $option->withShortcut($config['shortcut']);
                    }
                    if ($config['accept_value']) {
                        if ($config['is_value_required']) {
                            $option->withRequiredValue();
                        } else {
                            $option->withOptionalValue(null, $config['default']);
                        }
                        if ($config['is_multiple']) {
                            $option->isArray();
                        }
                    }
                }
            }
        };
    }
};
