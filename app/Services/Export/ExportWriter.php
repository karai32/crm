<?php

class ExportWriter
{
    public function csv(array $plan, $output): int
    {
        $statement = $this->statement($plan['sql'], $plan['params']);
        fputcsv($output, $plan['headers']);
        $rows = 0;

        while ($row = $statement->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, array_map([$this, 'safeCell'], $row));
            $rows++;
        }
        return $rows;
    }

    public function xlsx(array $plan, string $title, string $target): int
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            throw new RuntimeException('PhpSpreadsheet is required for XLSX exports.');
        }

        $statement = $this->statement($plan['sql'], $plan['params']);
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($title);
        $sheet->fromArray($plan['headers'], null, 'A1');

        $rowNumber = 2;
        while ($row = $statement->fetch(PDO::FETCH_NUM)) {
            $sheet->fromArray(array_map([$this, 'safeCell'], $row), null, 'A' . $rowNumber++);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($target);
        $spreadsheet->disconnectWorksheets();

        return $rowNumber - 2;
    }

    private function statement(string $sql, array $params): PDOStatement
    {
        $pdo = Database::connect();
        $pdo->exec('SET SESSION group_concat_max_len = 65535');
        $statement = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();
        return $statement;
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
