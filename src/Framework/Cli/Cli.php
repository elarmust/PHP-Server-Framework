<?php

/**
 * Cli application for Framework
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Cli;

use Framework\Cli\CommandInterface;
use Framework\Utils\RouteUtils;
use Framework\Logger\Logger;
use Psr\Log\LogLevel;
use Throwable;

class Cli {
    private array $commands = [];
    public $stdin;

    public function __construct(private Logger $logger) {
    }

    public function runCommand(array $commandArgs) {
        $highestMatch = RouteUtils::findNearestMatch(implode(' ', $commandArgs), $this->getCommands(), ' ');

        foreach ($this->getCommandHandlers($highestMatch) as $handlerClass) {
            try {
                $result = $handlerClass->run($commandArgs);
            } catch (Throwable $e) {
                $this->logger->log(LogLevel::ERROR, $e->getMessage(), identifier: 'framework');
                $this->logger->log(LogLevel::ERROR, $e->getTraceAsString(), identifier: 'framework');
            }

            if (is_string($result)) {
                $this->sendToOutput($result);
            } else if (!$result) {
                return false;
            }
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
