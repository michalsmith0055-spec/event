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
        $line = sprintf("[%s] %s: %s %s\n", $date, $level, $message, $this->encodeContext($context));

        $directory = dirname($this->logFile);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            error_log(sprintf('Logger could not create log directory "%s". %s', $directory, $line));
            return;
        }

        if (@file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log(sprintf('Logger could not write to "%s". %s', $this->logFile, $line));
        }
    }

    private function encodeContext(array $context): string
    {
        try {
            return json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            return json_encode(
                ['context_encoding_error' => $e->getMessage()],
                JSON_UNESCAPED_SLASHES
            ) ?: '{}';
        }
    }
}
