<?php

/**
 * Migration command
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Database\Commands;

use Framework\Cli\CommandInterface;
use Framework\Database\Migrations;
use Framework\Cli\Cli;

class Migrate implements CommandInterface {
    public function __construct(private Migrations $migrations, private Cli $cli) {
    }

    public function run(array $commandArgs): null|string {
        switch (strtolower($commandArgs[1] ?? '')) {
            case 'run':
                return $this->runMigration($commandArgs);
            case 'list':
                return 'Migrations: ' . implode(', ', array_keys($this->migrations->getMigrations()));
            case 'info':
                $commandArgs[2] = strtolower($commandArgs[2]);
                if (!isset($commandArgs[2]) || $commandArgs[2] == '') {
                    return "\033[31mMissing argument: migration name\033[0m";
                }

                $migrations = $this->migrations->getMigrations($commandArgs[2])[$commandArgs[2]] ?? [];
                if (!$migrations) {
                    return "\033[31mMigration '" . $commandArgs[2] . "' does not exist!\033[0m";
                }

                $string = PHP_EOL . "\033[32mGreen\033[0m" . ' = Installed.' . PHP_EOL;
                $string .= "\033[31mRed\033[0m" . ' = Not installed.' . PHP_EOL;
                $string .= "\033[33mYellow\033[0m" . ' = Migration may not be fully installed. Rerun may be required.' . PHP_EOL;
                $string .= 'Migration \'' . $commandArgs[2] . '\' versions: ' . PHP_EOL;
                $versions = [];
                $versionFail = [];
                $versionSuccess = [];
                $versionDatabases = [];
                foreach ($migrations as $version => $migrationBatches) {
                    $versions[] = $version;
                    foreach ($migrationBatches as $migration) {
                        foreach ($migration->getDatabases() as $database) {
                            $versionDatabases[$version][] = $database->getName();
                            if ($this->migrations->migrationPackageHasBeenRun($migration, $database)) {
                                $versionSuccess[$version][] = $database->getName();
                            } else {
                                $versionFail[$version][] = $database->getName();
                            }
                        }
                    }

                    $versionDatabases[$version] = array_unique($versionDatabases[$version]);
                }

                foreach ($versions as $version) {
                    $versionColor = '32';
                    if (!isset($versionSuccess[$version])) {
                        $versionColor = '31';
                    } else if (isset($versionFail[$version])) {
                        $versionColor = '33';
                    }

                    $databaseStrings = [];
                    foreach ($versionDatabases[$version] as $database) {
                        $databaseColor = 32;
                        if (in_array($database, $versionFail[$version] ?? []) && in_array($database, $versionSuccess[$version] ?? [])) {
                            $databaseColor = 33;
                        } else if (in_array($database, $versionFail[$version] ?? [])) {
                            $databaseColor = 31;
                        }

                        $databaseStrings[] = "\033[" . $databaseColor . 'm' . $database . "\033[0m";
                    }

                    $string .= "\033[" . $versionColor . 'm' . $version . "\033[0m (" . implode(', ', $databaseStrings) . ')' . PHP_EOL;
                }

                return rtrim($string, PHP_EOL);
        }

        $string = 'Possible arguments:' . PHP_EOL;
        $string .= '    [run] [up/down] [migration name / all] <version> - Run up or down migrations.' . PHP_EOL;
        $string .= '    [info] [migration name] <version> - Show migration information.' . PHP_EOL;
        $string .= '    [list] - List all migrations.';
        return $string;
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'run data\database migrations';
    }

    public function runMigration(array $commandArgs) {
        if (count($commandArgs) < 3) {
            return "\033[31mUsage: [up/down] [migration name / all] <version>\033[0m";
        }

        $up = true;
        if (strtolower($commandArgs[2] ?? '') == 'down') {
            $up = false;
        }

        if (!isset($commandArgs[3])) {
            return "\033[31mMissing argument: migration name / all\033[0m";
        }

        $version = strtolower($commandArgs[4] ?? '') ?: null;
        $migrationNameArg = strtolower($commandArgs[3] ?? '') ?: null;
        if ($version && !preg_match('/^(\d+\.)?(\d+\.)?(\*|\d+)$/', $version)) {
            return "\033[31mInvalid version!\033[0m";
        }

        if ($migrationNameArg == 'all') {
            $migrationNameArg = null;
            $version = null;
        }

        $migrations = $this->migrations->getMigrations($migrationNameArg);
        if (!$migrations) {
            return "\033[31mMigration '" . $migrationNameArg . "' does not exit!\033[0m";
        }

        $internalMigrations = $migrations['framework'] ?? [];
        unset($migrations['framework']);
        if ($internalMigrations) {
            if ($up) {
                $migrations = ['framework' => $internalMigrations] + $migrations;
            } else {
                $migrations['framework'] = $internalMigrations;
            }
        }

        $result = false;
        $migrationsRun = 0;
        foreach ($migrations as $migrationName => $migrations) {
            $versionList = array_keys($migrations);
            uasort($versionList, 'version_compare');
            $versionCompare = '>';
            if (!$up) {
                $versionList = array_reverse($versionList);
                $versionCompare = '<';
            }

            foreach ($versionList as $migrationVersion) {
                $migrationBatches = $migrations[$migrationVersion];
                if ($version && version_compare($migrationVersion, $version, $versionCompare)) {
                    break;
                }

                $result = false;
                // Migration batches need to be run in reverse order during a down migration.
                if (!$up) {
                    $migrationBatches = array_reverse($migrationBatches, true);
                }

                foreach ($migrationBatches as $migrationBatch) {
                    foreach ($migrationBatch->getDatabases() as $database) {
                        $resultTemp = $this->migrations->runMigration($migrationBatch, $database, $up);
                        if ($resultTemp) {
                            $result = $resultTemp;
                        }
                    }
                }

                if ($up) {
                    if ($result) {
                        $this->cli->sendToOutput("\033[32mMigration '" . $migrationName . ' ' . $migrationVersion . "' has been installed!\033[0m");
                        $migrationsRun++;
                    } else {
                        if ($migrationNameArg) {
                            $this->cli->sendToOutput("\033[31mMigration '" . $migrationName . ' ' . $migrationVersion . "' has already been installed!\033[0m");
                        }
                    }
                } else {
                    if ($result) {
                        $this->cli->sendToOutput("\033[32mMigration '" . $migrationName . ' ' . $migrationVersion . "' has been uninstalled!\033[0m");
                        $migrationsRun++;
                    } else {
                        if ($migrationNameArg) {
                            $this->cli->sendToOutput("\033[31mMigration '" . $migrationName . ' ' . $migrationVersion . "' has already been uninstalled!\033[0m");
                        }
                    }
                }
            }
        }

        if (!$migrationNameArg) {
            if (!$migrationsRun) {
                if ($up) {
                    $this->cli->sendToOutput("\033[33mThere are no new migrations to run!\033[0m");
                } else {
                    $this->cli->sendToOutput("\033[31mThere are no migrations to downgrade!\033[0m");
                }
            }
        }
    }
}
