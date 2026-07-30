<?php

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

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
        if (!class_exists(XlsxWriter::class)) {
            throw new RuntimeException('OpenSpout is required for XLSX exports.');
        }

        $statement = $this->statement($plan['sql'], $plan['params']);
        $writer = new XlsxWriter();
        $writer->openToFile($target);

        try {
            $writer->getCurrentSheet()->setName($title);
            $writer->addRow(Row::fromValues($plan['headers']));

            $rows = 0;
            while ($row = $statement->fetch(PDO::FETCH_NUM)) {
                $writer->addRow(Row::fromValues(array_map([$this, 'safeCell'], $row)));
                $rows++;
            }
        } finally {
            $writer->close();
        }

        return $rows;
    }

    private function statement(string $sql, array $params): PDOStatement
    {
        $pdo = Database::connect();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $pdo->exec('SET SESSION group_concat_max_len = 65535');
        }
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
