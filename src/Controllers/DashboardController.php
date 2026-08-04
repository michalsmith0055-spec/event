<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\AppException;
use App\Services\ExcelParser;
use App\Services\EventValidator;
use App\Services\FacebookService;
use App\Services\GoogleSheetsRepository;
use App\Utils\Csrf;
use App\Utils\Logger;

final class DashboardController
{
    public function __construct(
        private readonly ExcelParser $excelParser,
        private readonly EventValidator $validator,
        private readonly FacebookService $facebook,
        private readonly GoogleSheetsRepository $sheets,
        private readonly Logger $logger
    ) {
    }

    public function index(): void
    {
        $events = $_SESSION['events'] ?? [];
        $errors = $_SESSION['errors'] ?? [];
        $notices = $_SESSION['notices'] ?? [];
        $pages = $_SESSION['pages'] ?? [];
        $selectedPage = $_SESSION['selected_page'] ?? '';

        unset($_SESSION['errors'], $_SESSION['notices']);

        require dirname(__DIR__, 2) . '/templates/dashboard.php';
    }

    public function upload(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->failWith(['Invalid CSRF token.']);
        }

        if (!isset($_FILES['events_file']) || $_FILES['events_file']['error'] !== UPLOAD_ERR_OK) {
            $this->failWith([$this->describeUploadError($_FILES['events_file']['error'] ?? UPLOAD_ERR_NO_FILE)]);
        }

        $tmp = $_FILES['events_file']['tmp_name'];
        $name = strtolower((string) $_FILES['events_file']['name']);
        if (!str_ends_with($name, '.xlsx')) {
            $this->failWith(['Only .xlsx files are supported.']);
        }

        try {
            $events = $this->excelParser->parse($tmp);
        } catch (AppException $e) {
            $this->logger->error('Excel parsing failed.', ['file' => $name, 'error' => $e->getMessage()]);
            $this->failWith([$e->getMessage()]);
        }

        $errors = [];
        foreach ($events as $index => $event) {
            $errors = [...$errors, ...$this->validator->validate($event, $index + 2)];
        }

        $_SESSION['events'] = $events;
        $_SESSION['errors'] = $errors;

        $this->logger->info('Excel uploaded and parsed.', ['rows' => count($events)]);
        $this->auditLog('INFO', 'Excel uploaded and parsed. Rows: ' . count($events));

        $this->redirect('/');
    }

    public function facebookLogin(): void
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['fb_oauth_state'] = $state;

        try {
            $loginUrl = $this->facebook->getLoginUrl($state);
        } catch (AppException $e) {
            $this->logger->error('Facebook login URL could not be built.', ['error' => $e->getMessage()]);
            $this->failWith([$e->getMessage()]);
        }

        $this->redirect($loginUrl);
    }

    public function facebookCallback(): void
    {
        $expectedState = $_SESSION['fb_oauth_state'] ?? '';
        unset($_SESSION['fb_oauth_state']);

        if ($expectedState === '' || !hash_equals((string) $expectedState, (string) ($_GET['state'] ?? ''))) {
            $this->failWith(['Facebook OAuth state mismatch.']);
        }

        if (isset($_GET['error'])) {
            $description = (string) ($_GET['error_description'] ?? $_GET['error']);
            $this->logger->error('Facebook denied the OAuth request.', ['error' => $description]);
            $this->failWith(['Facebook login failed: ' . $description]);
        }

        $code = (string) ($_GET['code'] ?? '');
        if ($code === '') {
            $this->failWith(['Missing OAuth code from Facebook.']);
        }

        try {
            $tokenData = $this->facebook->exchangeCodeForToken($code);
            $pages = $this->facebook->getPages($tokenData['access_token']);
        } catch (AppException $e) {
            $this->logger->error('Facebook authentication failed.', ['error' => $e->getMessage()]);
            $this->auditLog('ERROR', 'Facebook authentication failed: ' . $e->getMessage());
            $this->failWith([$e->getMessage()]);
        }

        $_SESSION['user_access_token'] = $tokenData['access_token'];
        $_SESSION['pages'] = $pages;

        if ($pages === []) {
            $_SESSION['errors'] = ['Facebook returned no pages for this account.'];
        }

        $this->redirect('/');
    }

    public function selectPage(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->failWith(['Invalid CSRF token for page selection.']);
        }

        $pageId = (string) ($_POST['page_id'] ?? '');
        foreach (($_SESSION['pages'] ?? []) as $page) {
            if (($page['id'] ?? '') === $pageId) {
                if (empty($page['access_token'])) {
                    $this->failWith(['The selected page has no access token. Reconnect Facebook and try again.']);
                }

                $_SESSION['selected_page'] = $page;
                $this->redirect('/');
            }
        }

        $this->failWith(['Select a connected Facebook Page before continuing.']);
    }

    public function submitEvents(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $this->failWith(['Invalid CSRF token for submission.']);
        }

        $events = $_SESSION['events'] ?? [];
        $selectedPage = $_SESSION['selected_page'] ?? null;

        if ($events === [] || !$selectedPage) {
            $this->failWith(['Upload events and select a Facebook Page first.']);
        }

        $validationErrors = [];
        foreach ($events as $index => $event) {
            $validationErrors = [...$validationErrors, ...$this->validator->validate($event, $index + 2)];
        }

        if ($validationErrors !== []) {
            $this->failWith([...$validationErrors, 'Fix the rows above before submitting.']);
        }

        $batchSize = (int) env('BATCH_SIZE', '10');
        $delayMs = (int) env('API_DELAY_MS', '500');
        $maxRetries = (int) env('MAX_RETRIES', '2');

        $errors = [];

        try {
            $this->sheets->ensureTabsExist();
        } catch (AppException $e) {
            $this->logger->error('Could not prepare Google Sheets tabs.', ['error' => $e->getMessage()]);
            $this->failWith([$e->getMessage()]);
        }

        $posted = 0;
        $failed = 0;

        foreach (array_chunk($events, max(1, $batchSize), true) as $chunk) {
            foreach ($chunk as $index => $event) {
                $attempt = 0;
                $lastError = null;

                while ($attempt <= $maxRetries) {
                    $attempt++;
                    try {
                        $response = $this->facebook->createPageEvent(
                            $selectedPage['id'],
                            $selectedPage['access_token'],
                            $event
                        );
                        $events[$index]['status'] = 'Posted';
                        $events[$index]['facebook_event_id'] = (string) $response['id'];
                        $events[$index]['error_message'] = '';
                        $lastError = null;
                        $posted++;
                        break;
                    } catch (\Throwable $e) {
                        $lastError = $e;
                        $this->logger->error('Facebook event posting failed.', [
                            'row' => $index + 2,
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                        ]);

                        if ($attempt <= $maxRetries) {
                            usleep(300_000);
                        }
                    }
                }

                if ($lastError !== null) {
                    $failed++;
                    $message = $lastError->getMessage();
                    $events[$index]['status'] = 'Failed';
                    $events[$index]['error_message'] = $message;
                    $errors[] = sprintf('Row %d: %s', $index + 2, $message);
                    $this->auditLog('ERROR', sprintf('Row %d failed: %s', $index + 2, $message));
                }

                usleep(max(0, $delayMs) * 1000);
            }

            $chunkRows = array_values(array_intersect_key($events, $chunk));

            try {
                $this->sheets->appendEvents($chunkRows);
            } catch (AppException $e) {
                $this->logger->error('Could not append events to Google Sheets.', [
                    'rows' => count($chunkRows),
                    'error' => $e->getMessage(),
                ]);
                $errors[] = 'Results for ' . count($chunkRows) . ' row(s) could not be written to Google Sheets: '
                    . $e->getMessage();
            }
        }

        $_SESSION['events'] = $events;
        $_SESSION['errors'] = $errors;
        $_SESSION['notices'] = [sprintf('Submission finished: %d posted, %d failed.', $posted, $failed)];

        $this->logger->info('Submission completed.', ['posted' => $posted, 'failed' => $failed]);
        $this->auditLog('INFO', sprintf('Submission completed. Posted: %d, Failed: %d', $posted, $failed));

        $this->redirect('/');
    }

    /**
     * Writes to the Google Sheets log tab without letting a Sheets outage abort the request.
     */
    private function auditLog(string $level, string $message): void
    {
        try {
            $this->sheets->appendLog($level, $message);
        } catch (\Throwable $e) {
            $this->logger->error('Could not write to the Google Sheets log tab.', [
                'level' => $level,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array<int, string> $errors
     */
    private function failWith(array $errors): never
    {
        $_SESSION['errors'] = $errors;
        $this->redirect('/');
    }

    private function describeUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file is larger than the allowed size.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please choose an .xlsx file to upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server misconfiguration: no temporary upload directory.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the uploaded file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the upload.',
            default => 'Please upload a valid .xlsx file.',
        };
    }

    private function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
