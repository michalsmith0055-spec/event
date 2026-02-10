<?php

declare(strict_types=1);

namespace App\Controllers;

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
        $pages = $_SESSION['pages'] ?? [];
        $selectedPage = $_SESSION['selected_page'] ?? '';

        unset($_SESSION['errors']);

        require dirname(__DIR__, 2) . '/templates/dashboard.php';
    }

    public function upload(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $_SESSION['errors'] = ['Invalid CSRF token.'];
            $this->redirect('/');
        }

        if (!isset($_FILES['events_file']) || $_FILES['events_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errors'] = ['Please upload a valid .xlsx file.'];
            $this->redirect('/');
        }

        $tmp = $_FILES['events_file']['tmp_name'];
        $name = strtolower((string) $_FILES['events_file']['name']);
        if (!str_ends_with($name, '.xlsx')) {
            $_SESSION['errors'] = ['Only .xlsx files are supported.'];
            $this->redirect('/');
        }

        $events = $this->excelParser->parse($tmp);

        $errors = [];
        foreach ($events as $index => $event) {
            $errors = [...$errors, ...$this->validator->validate($event, $index + 2)];
        }

        $_SESSION['events'] = $events;
        $_SESSION['errors'] = $errors;

        $this->logger->info('Excel uploaded and parsed.', ['rows' => count($events)]);
        $this->sheets->appendLog('INFO', 'Excel uploaded and parsed. Rows: ' . count($events));

        $this->redirect('/');
    }

    public function facebookLogin(): void
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['fb_oauth_state'] = $state;
        $this->redirect($this->facebook->getLoginUrl($state));
    }

    public function facebookCallback(): void
    {
        if (($_GET['state'] ?? '') !== ($_SESSION['fb_oauth_state'] ?? '')) {
            $_SESSION['errors'] = ['Facebook OAuth state mismatch.'];
            $this->redirect('/');
        }

        $code = (string)($_GET['code'] ?? '');
        if ($code === '') {
            $_SESSION['errors'] = ['Missing OAuth code from Facebook.'];
            $this->redirect('/');
        }

        $tokenData = $this->facebook->exchangeCodeForToken($code);
        $pages = $this->facebook->getPages($tokenData['access_token']);

        $_SESSION['user_access_token'] = $tokenData['access_token'];
        $_SESSION['pages'] = $pages;

        $this->redirect('/');
    }

    public function selectPage(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $_SESSION['errors'] = ['Invalid CSRF token for page selection.'];
            $this->redirect('/');
        }

        $pageId = (string)($_POST['page_id'] ?? '');
        foreach (($_SESSION['pages'] ?? []) as $page) {
            if (($page['id'] ?? '') === $pageId) {
                $_SESSION['selected_page'] = $page;
                break;
            }
        }

        $this->redirect('/');
    }

    public function submitEvents(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $_SESSION['errors'] = ['Invalid CSRF token for submission.'];
            $this->redirect('/');
        }

        $events = $_SESSION['events'] ?? [];
        $selectedPage = $_SESSION['selected_page'] ?? null;

        if ($events === [] || !$selectedPage) {
            $_SESSION['errors'] = ['Upload events and select a Facebook Page first.'];
            $this->redirect('/');
        }

        $batchSize = (int)env('BATCH_SIZE', '10');
        $delayMs = (int)env('API_DELAY_MS', '500');
        $maxRetries = (int)env('MAX_RETRIES', '2');

        $this->sheets->ensureTabsExist();

        foreach (array_chunk($events, max(1, $batchSize), true) as $chunk) {
            foreach ($chunk as $index => $event) {
                $attempt = 0;
                $posted = false;

                while ($attempt <= $maxRetries && !$posted) {
                    try {
                        $attempt++;
                        $response = $this->facebook->createPageEvent(
                            $selectedPage['id'],
                            $selectedPage['access_token'],
                            $event
                        );
                        $events[$index]['status'] = 'Posted';
                        $events[$index]['facebook_event_id'] = $response['id'] ?? '';
                        $events[$index]['error_message'] = '';
                        $posted = true;
                    } catch (\Throwable $e) {
                        $events[$index]['status'] = 'Failed';
                        $events[$index]['error_message'] = $e->getMessage();
                        $this->logger->error('Facebook event posting failed.', [
                            'row' => $index,
                            'attempt' => $attempt,
                            'error' => $e->getMessage(),
                        ]);
                        usleep(300_000);
                    }
                }

                usleep(max(0, $delayMs) * 1000);
            }

            $this->sheets->appendEvents(array_values($events));
        }

        $_SESSION['events'] = $events;
        $this->sheets->appendLog('INFO', 'Submission completed.');
        $this->redirect('/');
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
