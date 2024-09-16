<?php

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
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
return new class implements DiagnosticsPluginInterface {
    public function getName(): string
    {
        return 'composer-require-checker';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder
            ->describeStringOption('config_file', 'Path to configuration file');
        $configOptionsBuilder
            ->describeStringOption('composer_file', 'Path to the composer.json (relative to project root)')
            ->isRequired()
            ->withDefaultValue('composer.json');
        $configOptionsBuilder
            ->describeStringListOption(
                'custom_flags',
                'Any custom flags to pass to composer-require-checker.' .
                'For valid flags refer to the composer-require-checker documentation.',
            )
        ;
    }

    public function createDiagnosticTasks(
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment
    ): iterable {
        $composerJson = $config->getString('composer_file');

        yield $environment
            ->getTaskFactory()
            ->buildRunPhar('composer-require-checker', $this->buildArguments($config, $environment))
            ->withoutXDebug()
            ->withWorkingDirectory($environment->getProjectConfiguration()->getProjectRootPath())
            ->withOutputTransformer($this->createOutputTransformerFactory($composerJson))
            ->build();
    }

    /** @psalm-return array<int, string> */
    private function buildArguments(PluginConfigurationInterface $config, EnvironmentInterface $environment): array
    {
        $arguments   = ['check', '--output=json'];
        $projectRoot = $environment->getProjectConfiguration()->getProjectRootPath() . '/';

        if ($config->has('config_file')) {
            $arguments[] = '--config-file=' . $projectRoot . $config->getString('config_file');
        }
        $arguments[] = $projectRoot . $config->getString('composer_file');

        if ($config->has('custom_flags')) {
            foreach ($config->getStringList('custom_flags') as $value) {
                $arguments[] = (string) $value;
            }
        }

        return $arguments;
    }

    private function createOutputTransformerFactory(string $composerFile): OutputTransformerFactoryInterface
    {
        return new class ($composerFile) implements OutputTransformerFactoryInterface {
            /** @var string */
            private $composerFile;

            public function __construct(string $composerFile)
            {
                $this->composerFile = $composerFile;
            }

            public function createFor(TaskReportInterface $report): OutputTransformerInterface
            {
                return new class ($this->composerFile, $report) implements OutputTransformerInterface {
                    /** @var string */
                    private $composerFile;
                    /** @var BufferedLineReader */
                    private $data;
                    /** @var TaskReportInterface */
                    private $report;

                    public function __construct(string $composerFile, TaskReportInterface $report)
                    {
                        $this->composerFile = $composerFile;
                        $this->report       = $report;
                        $this->data         = BufferedLineReader::create();
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

                    /** @psalm-param TSeverity $severity */
                    private function logDiagnostic(string $message, string $severity): void
                    {
                        $this->report->addDiagnostic($severity, $message)->forFile($this->composerFile)->end()->end();
                    }

                    private function process(): void
                    {
                        try {
                            $data = json_decode($this->data->getData(), true, 512, JSON_THROW_ON_ERROR);
                        } catch (JsonException $exception) {
                            $this->logDiagnostic(
                                'Unable to parse output: ' . $exception->getMessage(),
                                TaskReportInterface::SEVERITY_FATAL
                            );
                            $this->report->addAttachment('error.log')->fromString($this->data->getData());

                            return;
                        }

                        if (count($data['unknown-symbols']) === 0) {
                            $this->logDiagnostic(
                                'There were no unknown symbols found.',
                                TaskReportInterface::SEVERITY_INFO
                            );

                            return;
                        }

                        $dependencies = [];
                        $unknown = [];

                        foreach ($data['unknown-symbols'] as $symbol => $guessedDependencies) {
                            if ([] === $guessedDependencies) {
                                $unknown[] = $symbol;

                                continue;
                            }

                            foreach ($guessedDependencies as $dependency) {
                                $dependencies[$dependency][] = $symbol;
                            }
                        }

                        foreach ($dependencies as $dependency => $symbols) {
                            $this->logDiagnostic(
                                sprintf(
                                    'Missing dependency "%1$s" (used symbols: "%2$s")',
                                    $dependency,
                                    implode('", "', $symbols)
                                ),
                                TaskReportInterface::SEVERITY_MAJOR
                            );
                        }

                        if ([] !== $unknown) {
                            $this->logDiagnostic(
                                sprintf(
                                    'Unknown symbols found: "%1$s" - is there a dependency missing?',
                                    implode('", "', $unknown)
                                ),
                                TaskReportInterface::SEVERITY_FATAL
                            );
                        }
                    }
                };
            }
        };
    }
};
