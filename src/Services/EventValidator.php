<?php

declare(strict_types=1);

namespace App\Services;

final class EventValidator
{
    private const REQUIRED_FIELDS = [
        'event_name',
        'event_description',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'event_type',
    ];

    public function validate(array $event, int $rowNumber): array
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty(trim((string)($event[$field] ?? '')))) {
                $errors[] = "Row {$rowNumber}: {$field} is required.";
            }
        }

        if (!empty($event['start_date']) && !$this->isValidDate((string)$event['start_date'])) {
            $errors[] = "Row {$rowNumber}: start_date must be YYYY-MM-DD.";
        }

        if (!empty($event['end_date']) && !$this->isValidDate((string)$event['end_date'])) {
            $errors[] = "Row {$rowNumber}: end_date must be YYYY-MM-DD.";
        }

        if (!in_array(strtolower((string)($event['event_type'] ?? '')), ['in person', 'virtual'], true)) {
            $errors[] = "Row {$rowNumber}: event_type must be 'In person' or 'Virtual'.";
        }

        if (!empty($event['ticket_url']) && !filter_var($event['ticket_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "Row {$rowNumber}: ticket_url is not a valid URL.";
        }

        if (!empty($event['event_image_url']) && !filter_var($event['event_image_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "Row {$rowNumber}: event_image_url is not a valid URL.";
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
