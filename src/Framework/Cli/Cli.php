<?php

/**
 * Cli application for Framework
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cli;

use Psr\Log\LogLevel;
use Framework\Logger\Logger;
use Framework\CLI\CommandInterface;
use Framework\Core\ApplicationInterface;

class Cli implements ApplicationInterface {
    private Logger $logger;
    private array $commands = [];
    public $stdin;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function run() {
    }

    public function runCommand(array $commandArgs) {
        $commandRoutesMatches = [];
        foreach ($this->getCommands() as $command) {
            $routeParts = explode('/', $command);
            $argsToSkip = [];

            $commandRoutesMatches[$command] = 0;
            foreach ($routeParts as $index => $routePart) {
                foreach ($commandArgs as $index2 => $cmdParam) {
                    if (in_array($index2, $argsToSkip)) {
                        continue;
                    }

                    if ($routePart == $cmdParam) {
                        $commandRoutesMatches[$command]++;
                        $argsToSkip[] = $index2;
                    } else {
                        if ($index2 < $index) {
                            $commandRoutesMatches[$command]--;
                        }
                    }
                }
            }

            if ($commandRoutesMatches[$command] < 1) {
                unset($commandRoutesMatches[$command]);
            }
        }

        if (!$commandRoutesMatches) {
            $this->sendToOutput($this->getCommandDescriptions());
            return;
        }

        $highestMatch = array_keys($commandRoutesMatches, max($commandRoutesMatches))[0];

        if (count(array_unique($commandRoutesMatches, SORT_REGULAR)) === 1) {
            $cmdParams = [];
            foreach ($commandRoutesMatches as $command => $matches) {
                $cmdParams[$command] = explode('/', $command);
            }

            $highestMatch = array_keys($cmdParams, min($cmdParams))[0];
        }

        foreach ($this->getCommandHandlers($highestMatch) as $handlerClass) {
            $result = $handlerClass->run($commandArgs);
            if (is_string($result)) {
                $this->sendToOutput($result);
            } else if (!$result) {
                return false;
            }

            usleep(50000);
        }
    }

    public function getCommandDescriptions(string $commandRouteFilter = ''): string {
        $string = 'Possible arguments: ' . PHP_EOL;
        $commandsDisplayed = [];
        foreach ($this->getCommands() as $command) {
            if (str_starts_with($command, $commandRouteFilter)) {
                foreach ($this->getCommandHandlers($command) as $commandHandler) {
                    if (in_array($command, $commandsDisplayed)) {
                        continue;
                    }

                    $commandArgs = str_replace($commandRouteFilter, '', $command);
                    $description = $commandHandler->getDescription();
                    if (is_string($description)) {
                        $string .= '    ' . str_replace('/', ' ', $commandArgs) . ' - ' . $commandHandler->getDescription() . PHP_EOL;
                        $commandsDisplayed[] = $command;
                    }
                }
            }
        }

        return $string;
    }

    public function getCommands(): array {
        return array_keys($this->commands);
    }

    public function getCommandHandlers(string $command): array {
        return $this->commands[$command] ?? [];
    }

    public function registerCommandHandler(string $command, CommandInterface $commandClass): void {
        $this->commands[$command][] = $commandClass;
    }

    public function unregisterCommandHandler(string $command, CommandInterface $commandClass): void {
        $key = array_search($commandClass, $this->commands[$command]);
        if ($key === false) {
            $this->logger->log(LogLevel::NOTICE, 'Unregistering nonexistent command handler: \'' .  $commandClass::class . '\' from command \'' . $command . '\'', identifier: 'framework');
            return;
        }

        unset($this->commands[$command][$key]);

        if (count($this->commands[$command]) == 0) {
            unset($this->commands[$command]);
        }
    }

    public function unregisterCommand(string $command): void {
        if (!isset($this->commands[$command])) {
            $this->logger->log(LogLevel::NOTICE, 'Unregistering nonexistent command: \'' . $command . '\'', identifier: 'framework');
            return;
        }

        unset($this->commands[$command]);
    }

    public function sendToOutput(string $text) {
        $this->logger->log(LogLevel::INFO, $text, identifier: 'framework');
    }
}
