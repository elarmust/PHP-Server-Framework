<?php

/**
 * Task Scheduler handles task scheduling and execution.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Task;

use Framework\Logger\Logger;
use OpenSwoole\Timer;
use InvalidArgumentException;

class TaskScheduler {
    private array $taskList = [];

    public function __construct(private Logger $logger) {
    }

    public function schedule(TaskInterface $task, int $delay): void {
        if ($delay < 1) {
            throw new InvalidArgumentException('Delay must be > 0!');
        }

        $taskId = Timer::after($delay, function () use (&$task) {
            $this->logger->debug('Running task: ' . $task->getName(), identifier: 'framework');
            $task->execute();
            unset($this->taskList[$task->getName()]);
        });

        if ($taskId !== false) {
            $this->taskList[$task->getName()] = $taskId;
        }
    }

    public function scheduleRecurring(TaskInterface $task, int $delay): void {
        if ($delay < 1) {
            throw new InvalidArgumentException('Delay must be > 0!');
        }

        $taskId = Timer::tick($delay, function () use (&$task, &$taskId) {
            $this->logger->debug('Running a recurring task: ' . $task->getName(), identifier: 'framework');

            // Cancel a recurring task if it returns false.
            if ($task->execute() === false) {
                unset($this->taskList[$task->getName()]);
                Timer::clear($taskId);
            }
        });

        if ($taskId !== false) {
            $this->taskList[$task->getName()] = $taskId;
        }
    }

    public function cancelTask(string $taskName): void {
        if (isset($this->taskList[$taskName])) {
            Timer::clear($this->taskList[$taskName]);
            unset($this->taskList[$taskName]);
        }
    }

    public function getTaskList(): array {
        return $this->taskList;
    }
}
