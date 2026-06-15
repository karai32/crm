<?php

class ImportFileReader
{
    public function preview(string $path, string $fileType, int $limit = 10): array
    {
        $headers = [];
        $rows = [];
        $total = 0;

        foreach ($this->rows($path, $fileType) as $item) {
            $headers = $item['headers'];
            $total++;
            if (count($rows) < $limit) {
                $rows[] = $item['row'];
            }
        }
        if ($headers === []) {
            $headers = $this->headers($path, $fileType);
        }

        return ['headers' => $headers, 'rows' => $rows, 'total_rows' => $total];
    }

    public function rows(string $path, string $fileType): Generator
    {
        if ($fileType === 'xlsx') {
            yield from $this->xlsxRows($path);
            return;
        }

        yield from $this->csvRows($path);
    }

    private function csvRows(string $path): Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Could not open import file.');
        }

        try {
            $headers = fgetcsv($handle);
            if ($headers === false) {
                return;
            }
            $headers = $this->normalizeHeaders($headers);
            $rowNumber = 1;

            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $row = $this->combine($headers, $data);
                if ($this->hasValues($row)) {
                    yield ['row_number' => $rowNumber, 'headers' => $headers, 'row' => $row];
                }
            }
        } finally {
            fclose($handle);
        }
    }

    private function xlsxRows(string $path): Generator
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new RuntimeException('PhpSpreadsheet is required for XLSX imports.');
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $headers = [];

            foreach ($sheet->getRowIterator() as $sheetRow) {
                $rowNumber = $sheetRow->getRowIndex();
                $cells = [];
                $iterator = $sheetRow->getCellIterator();
                $iterator->setIterateOnlyExistingCells(false);

                foreach ($iterator as $cell) {
                    $cells[] = trim((string) $cell->getFormattedValue());
                }

                if ($rowNumber === 1) {
                    $headers = $this->normalizeHeaders($cells);
                    continue;
                }
                if ($headers === []) {
                    continue;
                }

                $row = $this->combine($headers, $cells);
                if ($this->hasValues($row)) {
                    yield ['row_number' => $rowNumber, 'headers' => $headers, 'row' => $row];
                }
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function normalizeHeaders(array $headers): array
    {
        $headers = array_map(fn (mixed $header): string => trim((string) $header), $headers);
        if ($headers !== []) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        return $headers;
    }

    private function headers(string $path, string $fileType): array
    {
        if ($fileType === 'xlsx') {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                return [];
            }
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            try {
                return $this->normalizeHeaders(
                    $spreadsheet->getActiveSheet()->rangeToArray('A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . '1')[0] ?? []
                );
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }
        try {
            return $this->normalizeHeaders(fgetcsv($handle) ?: []);
        } finally {
            fclose($handle);
        }
    }

    private function combine(array $headers, array $values): array
    {
        $row = [];
        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $row[$header] = trim((string) ($values[$index] ?? ''));
            }
        }
        return $row;
    }

    private function hasValues(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }
}
