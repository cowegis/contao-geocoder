<?php

/**
 * Tool home: https://github.com/phpmd/phpmd
 */

declare(strict_types=1);

use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationBuilderInterface;
use Phpcq\PluginApi\Version10\Configuration\PluginConfigurationInterface;
use Phpcq\PluginApi\Version10\DiagnosticsPluginInterface;
use Phpcq\PluginApi\Version10\EnvironmentInterface;
use Phpcq\PluginApi\Version10\Output\OutputInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerFactoryInterface;
use Phpcq\PluginApi\Version10\Output\OutputTransformerInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\PluginApi\Version10\Util\BufferedLineReader;

return new class implements DiagnosticsPluginInterface {
    public function getName(): string
    {
        return 'phpmd';
    }

    public function describeConfiguration(PluginConfigurationBuilderInterface $configOptionsBuilder): void
    {
        $configOptionsBuilder->supportDirectories();
        $configOptionsBuilder
            ->describeStringListOption(
                'ruleset',
                'List of rulesets (cleancode, codesize, controversial, design, naming, unusedcode).'
            )
            ->isRequired()
            ->withDefaultValue(['naming', 'unusedcode']);

        $configOptionsBuilder
            ->describeStringListOption(
                'custom_flags',
                'Any custom flags to pass to phpmd. For valid flags refer to the phpmd documentation.'
            )
            ->isRequired()
            ->withDefaultValue([]);

        $configOptionsBuilder
            ->describeStringListOption(
                'excluded',
                'List of excluded paths.'
            );
    }

    public function createDiagnosticTasks(
        PluginConfigurationInterface $config,
        EnvironmentInterface $environment
    ): iterable {
        $directories = $config->getStringList('directories');

        $args = [
            implode(',', $directories),
            'xml',
            implode(',', $config->getStringList('ruleset')),
        ];

        if ($config->has('excluded')) {
            $paths = [];
            foreach ($config->getStringList('excluded') as $path) {
                if ('' === ($path = trim($path))) {
                    continue;
                }
                $paths[] = $path;
            }
            if ($paths) {
                $args[] = '--exclude';
                $args[] = implode(',', $paths);
            }
        }

        if ($config->has('custom_flags')) {
            foreach ($config->getStringList('custom_flags') as $value) {
                $args[] = $value;
            }
        }

        $xmlfile = $environment->getUniqueTempFile($this, 'xml');
        $args[]  = '--report-file';
        $args[]  = $xmlfile;

        yield $environment
            ->getTaskFactory()
            ->buildRunPhar('phpmd', $args)
            ->withoutXDebug()
            ->withWorkingDirectory($environment->getProjectConfiguration()->getProjectRootPath())
            ->withOutputTransformer(
                $this->createOutputTransformer($xmlfile, $environment->getProjectConfiguration()->getProjectRootPath())
            )
            ->build();
    }

    private function createOutputTransformer(string $xmlFile, string $rootDir): OutputTransformerFactoryInterface
    {
        return new class ($xmlFile, $rootDir) implements OutputTransformerFactoryInterface {
            private $xmlFile;
            private $rootDir;

            public function __construct(string $xmlFile, string $rootDir)
            {
                $this->xmlFile = $xmlFile;
                $this->rootDir = $rootDir;
            }

            public function createFor(TaskReportInterface $report): OutputTransformerInterface
            {
                return new class ($this->xmlFile, $this->rootDir, $report) implements OutputTransformerInterface {
                    /** @var string */
                    private $xmlFile;
                    /** @var string */
                    private $rootDir;
                    /** @var TaskReportInterface */
                    private $report;
                    /** @var BufferedLineReader */
                    private $stdOut;
                    /** @var BufferedLineReader */
                    private $stdErr;

                    public function __construct(string $xmlFile, string $rootDir, TaskReportInterface $report)
                    {
                        $this->xmlFile = $xmlFile;
                        $this->rootDir = $rootDir;
                        $this->report  = $report;
                        $this->stdOut  = BufferedLineReader::create();
                        $this->stdErr  = BufferedLineReader::create();
                    }


                    public function write(string $data, int $channel): void
                    {
                        if (OutputInterface::CHANNEL_STDERR === $channel) {
                            $this->stdErr->push($data);
                            return;
                        }
                        $this->stdOut->push($data);
                    }

                    public function finish(int $exitCode): void
                    {
                        if (null === $xmlDocument = $this->openReportFile()) {
                            return;
                        }
                        $rootNode = $xmlDocument->firstChild;

                        if (!$rootNode instanceof DOMNode) {
                            $this->report->close(
                                $exitCode === 0
                                    ? TaskReportInterface::STATUS_PASSED
                                    : TaskReportInterface::STATUS_FAILED
                            );
                            return;
                        }

                        foreach ($rootNode->childNodes as $childNode) {
                            if (!$childNode instanceof DOMElement || $childNode->nodeName !== 'file') {
                                continue;
                            }

                            $fileName = $childNode->getAttribute('name');
                            if (strpos($fileName, $this->rootDir) === 0) {
                                $fileName = substr($fileName, strlen($this->rootDir) + 1);
                            }

                            foreach ($childNode->childNodes as $violationNode) {
                                if (!$violationNode instanceof DOMElement) {
                                    continue;
                                }

                                /*
                                 * <violation> may have:
                                 * beginline: starting line of the issue.
                                 * endline:   ending line of the issue.
                                 * rule:      name of the rule.
                                 * ruleset:   name of the ruleset the rule is defined within.
                                 * package:   namespace of the class where the issue is within.
                                 * class:     name of the class where the issue is within.
                                 * method:    name of the method where the issue is within.
                                 * externalInfoUrl: external URL describing the violation.
                                 * priority: The priority for the rule.
                                 *           This can be a value in the range 1-5, where 1 is the highest priority and
                                 *           5 the lowest priority.
                                 */

                                $message = sprintf(
                                    '%s%s(Ruleset: %s, %s)',
                                    trim($violationNode->textContent),
                                    "\n",
                                    (string) $this->getXmlAttribute($violationNode, 'ruleset', ''),
                                    (string) $this->getXmlAttribute($violationNode, 'externalInfoUrl', '')
                                );

                                $severity = TaskReportInterface::SEVERITY_FATAL;
                                if (null !== $prio = $this->getIntXmlAttribute($violationNode, 'priority')) {
                                    // FIXME: Is this mapping correct?
                                    switch ($prio) {
                                        case 1:
                                        case 2:
                                        case 3:
                                            $severity = TaskReportInterface::SEVERITY_MAJOR;
                                            break;
                                        case 4:
                                            $severity = TaskReportInterface::SEVERITY_MINOR;
                                            break;
                                        case 5:
                                        default:
                                            $severity = TaskReportInterface::SEVERITY_INFO;
                                    }
                                }

                                $beginLine = $this->getIntXmlAttribute($violationNode, 'beginline');
                                $endLine   = $this->getIntXmlAttribute($violationNode, 'endline');
                                $this->report->addDiagnostic($severity, $message)
                                    ->forFile($fileName)
                                        ->forRange(
                                            (int) $this->getIntXmlAttribute($violationNode, 'beginline'),
                                            null,
                                            $endLine !== $beginLine ? $endLine : null,
                                        )
                                        ->end()
                                    ->fromSource((string) $this->getXmlAttribute($violationNode, 'rule'))
                                    ->end();
                            }
                        }

                        $this->report->addAttachment('pmd.xml')
                                ->fromFile($this->xmlFile)
                                ->setMimeType('application/xml')
                            ->end();

                        $this->report->close(
                            $exitCode === 0
                                ? TaskReportInterface::STATUS_PASSED
                                : TaskReportInterface::STATUS_FAILED
                        );
                    }

                    private function openReportFile(): ?DOMDocument
                    {
                        if (is_readable($this->xmlFile) && filesize($this->xmlFile)) {
                            $xmlDocument = new DOMDocument('1.0');
                            $xmlDocument->load($this->xmlFile);
                            return $xmlDocument;
                        }
                        $this->report->addDiagnostic(
                            TaskReportInterface::SEVERITY_FATAL,
                            'Report file was not produced: ' . $this->xmlFile
                        );
                        $contents = [];
                        while (null !== $line = $this->stdOut->fetch()) {
                            $contents[] = $line;
                        }
                        if (!empty($contents)) {
                            $this->report
                                ->addAttachment('output.log')
                                ->fromString(implode("\n", $contents))
                                ->setMimeType('text/plain')
                                ->end();
                        }
                        $contents = [];
                        while (null !== $line = $this->stdErr->fetch()) {
                            $contents[] = $line;
                        }
                        if (!empty($contents)) {
                            $this->report
                                ->addAttachment('error.log')
                                ->fromString(implode("\n", $contents))
                                ->setMimeType('text/plain')
                                ->end();
                        }
                        $this->report->close(TaskReportInterface::STATUS_FAILED);

                        return null;
                    }

                    private function getXmlAttribute(
                        DOMElement $element,
                        string $attribute,
                        ?string $defaultValue = null
                    ): ?string {
                        if ($element->hasAttribute($attribute)) {
                            return $element->getAttribute($attribute);
                        }

                        return $defaultValue;
                    }

                    private function getIntXmlAttribute(DOMElement $element, string $attribute): ?int
                    {
                        $value = $this->getXmlAttribute($element, $attribute);
                        if ($value === null) {
                            return null;
                        }

                        return (int) $value;
                    }
                };
            }
        };
    }
};
