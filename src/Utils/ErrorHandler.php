<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Converts PHP warnings/notices into exceptions and turns otherwise fatal
 * failures into a logged, explicit HTTP 500 response.
 */
final class ErrorHandler
{
    private function __construct(private readonly Logger $logger, private readonly bool $debug)
    {
    }

    public static function register(Logger $logger, bool $debug): void
    {
        $handler = new self($logger, $debug);

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler([$handler, 'handleError']);
        set_exception_handler([$handler, 'handleException']);
        register_shutdown_function([$handler, 'handleShutdown']);
    }

    public function handleError(int $severity, string $message, string $file = '', int $line = 0): bool
    {
        if ((error_reporting() & $severity) === 0) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public function handleException(\Throwable $exception): void
    {
        $this->logger->error('Unhandled exception.', [
            'type' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        $this->renderFailure($exception->getMessage());
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $this->logger->error('Fatal error.', $error);
        $this->renderFailure($error['message']);
    }

    private function renderFailure(string $detail): void
    {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo '<h1>Something went wrong</h1>';
        echo '<p>The request could not be completed. Details were written to the application log.</p>';

        if ($this->debug) {
            echo '<pre>' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</pre>';
        }
    }
}
