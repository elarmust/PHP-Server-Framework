<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Tests\Commands;

use Framework\Module\ModuleRegistry;
use Framework\Cli\CommandInterface;
use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\TestRunner;

class Test implements CommandInterface {
    public function __construct(private ModuleRegistry $moduleRegistry) {
    }

    public function run(array $commandArgs): null|string {
        foreach ($this->getTests() as $module) {
            $testRunner = new TestRunner();
            $suite = new TestSuite();
            foreach ($module as $test) {
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
            $internalTest = 'Framework\\Tests\\Tests\\' . $internalTest;
            $tests['framework'][] = str_replace('.php', '', $internalTest);
        }

        // Module tests.
        foreach ($this->moduleRegistry->getAllModules() as $module) {
            $modulePath = $module->getPath();

            if (!file_exists($modulePath . '/Tests')) {
                continue;
            }

            $moduleTests = array_diff(scandir($modulePath . '/Tests'), ['..', '.']);
            foreach ($moduleTests as $moduleTest) {
                $testClassPath = $module->getName() . '\\Tests\\' . $moduleTest;
                $tests[$module->getName()][] = str_replace('.php', '', $testClassPath);
            }
        }

        return $tests;
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'Run unit tests.';
    }
}
