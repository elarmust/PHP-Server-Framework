<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Tests\Commands;

use Psr\Log\LogLevel;
use Framework\Logger\Logger;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use Framework\Cli\CommandInterface;
use Framework\Framework;
use Framework\Module\ModuleRegistry;

class Test implements CommandInterface {
    public function __construct(
        private ModuleRegistry $moduleRegistry,
        private Logger $logger,
        private Framework $framework) {
    }

    public function run(array $commandArgs): null|string {
        if (!$this->framework->isTestingEnvironment()) {
            $this->logger->log(LogLevel::WARNING, 'Tests can only be run in test invironment! You can enable it in config.json.', identifier: 'framework');
            return null;
        }

        foreach ($this->getTests() as $module) {
            $testRunner = new TestRunner();
            $suite = new TestSuite();
            foreach ($module as $test) {
                $this->logger->log(LogLevel::INFO, 'Running test: ' . $test);
                $suite->addTestSuite($test);
            }

            $testRunner->run($suite, exit: false);
        }

        return null;
    }

    private function getTests(): array {
        $tests = [];

        // Framework internal tests.
        $intermalTests = array_diff(scandir(BASE_PATH . '/src/Framework/Tests/Tests'), ['..', '.']);
        foreach ($intermalTests as $internalTest) {
            if (!is_file(BASE_PATH . '/src/Framework/Tests/Tests/' . $internalTest)) {
                continue;
            }

            $internalTest = 'Framework\\Tests\\Tests\\' . str_replace('.php', '', $internalTest);
            if (!class_exists($internalTest) || !is_subclass_of($internalTest, TestCase::class)) {
                continue;
            }

            $tests['framework'][] = $internalTest;
        }

        // Module tests.
        foreach ($this->moduleRegistry->getAllModules() as $module) {
            $modulePath = $module->getPath();

            if (!file_exists($modulePath . '/Tests')) {
                continue;
            }

            $moduleTests = array_diff(scandir($modulePath . '/Tests'), ['..', '.']);
            foreach ($moduleTests as $moduleTest) {
                // If it is not a file, then continue
                if (!is_file($modulePath . '/Tests/' . $moduleTest)) {
                    continue;
                }

                $testClassPath = $module->getName() . '\\Tests\\' . str_replace('.php', '', $moduleTest);
                if (!class_exists($testClassPath) || !is_subclass_of($testClassPath, TestCase::class)) {
                    continue;
                }

                $tests[$module->getName()][] = $testClassPath;
            }
        }

        return $tests;
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'Run unit tests.';
    }
}
