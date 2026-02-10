<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

final class ExcelParser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $events = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $event = [
                'event_name' => trim((string)$sheet->getCell("A{$row}")->getValue()),
                'event_description' => trim((string)$sheet->getCell("B{$row}")->getValue()),
                'start_date' => trim((string)$sheet->getCell("C{$row}")->getFormattedValue()),
                'start_time' => trim((string)$sheet->getCell("D{$row}")->getFormattedValue()),
                'end_date' => trim((string)$sheet->getCell("E{$row}")->getFormattedValue()),
                'end_time' => trim((string)$sheet->getCell("F{$row}")->getFormattedValue()),
                'event_type' => trim((string)$sheet->getCell("G{$row}")->getValue()),
                'venue_name' => trim((string)$sheet->getCell("H{$row}")->getValue()),
                'city' => trim((string)$sheet->getCell("I{$row}")->getValue()),
                'state' => trim((string)$sheet->getCell("J{$row}")->getValue()),
                'country' => trim((string)$sheet->getCell("K{$row}")->getValue()),
                'ticket_url' => trim((string)$sheet->getCell("L{$row}")->getValue()),
                'category' => trim((string)$sheet->getCell("M{$row}")->getValue()),
                'event_image_url' => trim((string)$sheet->getCell("N{$row}")->getValue()),
                'status' => 'Pending',
                'facebook_event_id' => '',
                'error_message' => '',
            ];

            if ($this->isEmptyRow($event)) {
                continue;
            }

            $events[] = $event;
        }

        return $events;
    }

    private function isEmptyRow(array $event): bool
    {
        foreach ($event as $key => $value) {
            if (in_array($key, ['status', 'facebook_event_id', 'error_message'], true)) {
                continue;
            }
            if (!empty($value)) {
                return false;
            }
        }
        return true;
    }
}
