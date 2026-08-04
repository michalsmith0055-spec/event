<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConfigurationException;
use App\Exceptions\SheetsException;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\Request;
use Google\Service\Sheets\ValueRange;

final class GoogleSheetsRepository
{
    private ?Sheets $service = null;
    private string $eventsTab;
    private string $logsTab;

    public function __construct()
    {
        $this->eventsTab = env('GOOGLE_SHEET_EVENTS_TAB', 'events');
        $this->logsTab = env('GOOGLE_SHEET_LOGS_TAB', 'logs');
    }

    public function ensureTabsExist(): void
    {
        $spreadsheet = $this->call(
            'read spreadsheet metadata',
            fn(): mixed => $this->service()->spreadsheets->get($this->spreadsheetId())
        );

        $existingTabs = array_map(
            static fn($sheet) => $sheet->getProperties()->getTitle(),
            $spreadsheet->getSheets()
        );

        $requests = [];
        foreach ([$this->eventsTab, $this->logsTab] as $tabName) {
            if (!in_array($tabName, $existingTabs, true)) {
                $requests[] = new Request([
                    'addSheet' => [
                        'properties' => ['title' => $tabName],
                    ],
                ]);
            }
        }

        if ($requests !== []) {
            $this->call(
                'create missing spreadsheet tabs',
                fn(): mixed => $this->service()->spreadsheets->batchUpdate(
                    $this->spreadsheetId(),
                    new BatchUpdateSpreadsheetRequest(['requests' => $requests])
                )
            );
        }
    }

    /**
     * @param array<int, array<string,mixed>> $events
     */
    public function appendEvents(array $events): void
    {
        if ($events === []) {
            return;
        }

        $rows = [];
        foreach ($events as $event) {
            $rows[] = [
                $event['event_name'],
                $event['event_description'],
                $event['start_date'],
                $event['start_time'],
                $event['end_date'],
                $event['end_time'],
                $event['event_type'],
                $event['venue_name'],
                $event['city'],
                $event['state'],
                $event['country'],
                $event['ticket_url'],
                $event['category'],
                $event['event_image_url'],
                $event['status'],
                $event['facebook_event_id'],
                $event['error_message'],
                (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];
        }

        $body = new ValueRange(['values' => $rows]);
        $this->call(
            'append event rows',
            fn(): mixed => $this->service()->spreadsheets_values->append(
                $this->spreadsheetId(),
                $this->eventsTab . '!A:R',
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    public function appendLog(string $level, string $message): void
    {
        $body = new ValueRange([
            'values' => [[
                (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                $level,
                $message,
            ]],
        ]);

        $this->call(
            'append log row',
            fn(): mixed => $this->service()->spreadsheets_values->append(
                $this->spreadsheetId(),
                $this->logsTab . '!A:C',
                $body,
                ['valueInputOption' => 'RAW']
            )
        );
    }

    private function call(string $action, callable $operation): mixed
    {
        try {
            return $operation();
        } catch (SheetsException | ConfigurationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new SheetsException("Google Sheets failed to {$action}: " . $e->getMessage(), 0, $e);
        }
    }

    private function service(): Sheets
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $credentialsPath = (string) env('GOOGLE_SERVICE_ACCOUNT_JSON', '');
        if ($credentialsPath === '') {
            throw new ConfigurationException('Missing required configuration value: GOOGLE_SERVICE_ACCOUNT_JSON.');
        }

        if (!is_readable($credentialsPath)) {
            throw new ConfigurationException(
                "Google service account key is not readable at \"{$credentialsPath}\"."
            );
        }

        try {
            $client = new Client();
            $client->setApplicationName('Facebook Event Automation');
            $client->setScopes([Sheets::SPREADSHEETS]);
            $client->setAuthConfig($credentialsPath);
        } catch (\Throwable $e) {
            throw new ConfigurationException(
                'Google service account credentials could not be loaded: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return $this->service = new Sheets($client);
    }

    private function spreadsheetId(): string
    {
        $id = (string) env('GOOGLE_SHEETS_ID', '');
        if ($id === '') {
            throw new ConfigurationException('Missing required configuration value: GOOGLE_SHEETS_ID.');
        }

        return $id;
    }
}
