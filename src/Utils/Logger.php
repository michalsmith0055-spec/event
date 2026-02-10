<?php

declare(strict_types=1);

namespace App\Utils;

final class Logger
{
    private string $logFile;

    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile ?? dirname(__DIR__, 2) . '/storage/logs/app.log';
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $date = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $line = sprintf("[%s] %s: %s %s\n", $date, $level, $message, json_encode($context));
        file_put_contents($this->logFile, $line, FILE_APPEND);
    }
}
