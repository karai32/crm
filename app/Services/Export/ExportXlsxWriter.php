<?php

class ExportXlsxWriter
{
    public function write(string $sql, array $params, array $headers, string $title, string $target): int
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            throw new RuntimeException('PhpSpreadsheet is required for XLSX exports.');
        }

        Database::connect()->exec('SET SESSION group_concat_max_len = 65535');
        $statement = Database::connect()->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);
        $sheet->fromArray($headers, null, 'A1');

        $rowNumber = 2;
        while ($row = $statement->fetch(PDO::FETCH_NUM)) {
            $sheet->fromArray(array_map([$this, 'safeCell'], $row), null, 'A' . $rowNumber);
            $rowNumber++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($target);
        $spreadsheet->disconnectWorksheets();

        return $rowNumber - 2;
    }

    private function safeCell(mixed $value): mixed
    {
        $value ??= '';
        if (!is_string($value)) {
            return $value;
        }
        return preg_match('/^[=+@]|^-(?!\d+(?:[.,]\d+)?$)/', $value) ? "'" . $value : $value;
    }
}
