<?php

declare(strict_types=1);

namespace JobVisa\App\Database\Seeders\Support;

use JobVisa\App\Database\Seeders\Contracts\SeederInterface;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Executes configured seeders in dependency-safe order.
 */
final class SeederRunner
{
    /**
     * @param  list<class-string<SeederInterface>>  $seederClasses
     * @param  callable(string): void|null  $logger
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $seederClasses,
        private $logger = null
    ) {
    }

    /**
     * @return array{ran: list<string>, skipped: list<string>, failed: list<string>}
     */
    public function run(?array $only = null): array
    {
        $ran = [];
        $skipped = [];
        $failed = [];

        foreach ($this->seederClasses as $class) {
            if (!is_string($class) || !class_exists($class)) {
                $failed[] = (string) $class;
                $this->log("FAIL  Missing seeder class: {$class}");
                continue;
            }

            /** @var SeederInterface $seeder */
            $seeder = new $class($this->pdo);
            $name = $seeder->name();

            if ($only !== null && $only !== [] && !in_array($class, $only, true) && !in_array($name, $only, true)) {
                $skipped[] = $name;
                continue;
            }

            $this->log("RUN   {$name} ({$class})");

            try {
                $this->pdo->beginTransaction();
                $seeder->run();
                $this->pdo->commit();
                $ran[] = $name;
                $this->log("OK    {$name}");
            } catch (Throwable $exception) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $failed[] = $name;
                $this->log('FAIL  ' . $name . ' — ' . $exception->getMessage());

                throw new RuntimeException(
                    "Seeder [{$name}] failed: " . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return compact('ran', 'skipped', 'failed');
    }

    private function log(string $message): void
    {
        if ($this->logger !== null) {
            ($this->logger)($message);
        }
    }
}
