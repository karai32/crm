<?php

use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Cell\TextRunCell;
use OpenSpout\Reader\XLSX\Options as XlsxReaderOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Reader\XLSX\Sheet as XlsxSheet;

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
        $headers = [];

        foreach ($this->xlsxValues($path) as $rowNumber => $cells) {
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
            $reader = $this->xlsxReader();
            $reader->open($path);
            $headers = [];
            try {
                foreach ($this->activeSheet($reader)->getRowIterator() as $row) {
                    if ($headers === []) {
                        $headers = $this->normalizeHeaders(array_map([$this, 'cellText'], $row->cells));
                    }
                }
            } finally {
                $reader->close();
            }
            return $headers;
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

    private function xlsxValues(string $path): Generator
    {
        $reader = $this->xlsxReader();
        $reader->open($path);

        try {
            foreach ($this->activeSheet($reader)->getRowIterator() as $rowNumber => $row) {
                yield $rowNumber => array_map([$this, 'cellText'], $row->cells);
            }
        } finally {
            $reader->close();
        }
    }

    private function xlsxReader(): XlsxReader
    {
        if (!class_exists(XlsxReader::class)) {
            throw new RuntimeException('OpenSpout is required for XLSX imports.');
        }

        return new XlsxReader(new XlsxReaderOptions(
            SHOULD_FORMAT_DATES: true,
            SHOULD_PRESERVE_EMPTY_ROWS: true
        ));
    }

    private function activeSheet(XlsxReader $reader): XlsxSheet
    {
        $first = null;
        foreach ($reader->getSheetIterator() as $sheet) {
            $first ??= $sheet;
            if ($sheet->isActive()) {
                return $sheet;
            }
        }

        return $first ?? throw new RuntimeException('The XLSX file has no worksheets.');
    }

    private function cellText(Cell $cell): string
    {
        if ($cell instanceof TextRunCell) {
            return trim($cell->getStringValue());
        }

        $value = $cell instanceof FormulaCell
            ? ($cell->getComputedValue() ?? $cell->getValue())
            : $cell->getValue();

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if ($value instanceof DateInterval) {
            return $value->format('%r%a days %H:%I:%S');
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) ($value ?? ''));
    }
}
