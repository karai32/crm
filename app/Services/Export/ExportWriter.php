<?php

use Illuminate\Database\Query\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class ExportWriter
{
    public function csv(array $plan, $output): int
    {
        fputcsv($output, $plan['headers']);
        $rows = 0;

        foreach ($this->rows($plan['query']) as $row) {
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

        $writer = new XlsxWriter();
        $writer->openToFile($target);

        try {
            $writer->getCurrentSheet()->setName($title);
            $writer->addRow(Row::fromValues($plan['headers']));

            $rows = 0;
            foreach ($this->rows($plan['query']) as $row) {
                $writer->addRow(Row::fromValues(array_map([$this, 'safeCell'], $row)));
                $rows++;
            }
        } finally {
            $writer->close();
        }

        return $rows;
    }

    private function rows(Builder $query): iterable
    {
        $connection = $query->getConnection();
        if ($connection->getDriverName() === 'mysql') {
            $connection->statement('SET SESSION group_concat_max_len = 65535');
        }

        foreach ($query->cursor() as $row) {
            yield array_values((array) $row);
        }
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
