<?php

class ExportCsvWriter
{
    public function write(string $sql, array $params, array $headers, $output): int
    {
        Database::connect()->exec('SET SESSION group_concat_max_len = 65535');
        $statement = $this->statement($sql, $params);
        fputcsv($output, $headers);
        $count = 0;

        while ($row = $statement->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, array_map([$this, 'safeCell'], $row));
            $count++;
        }
        return $count;
    }

    private function statement(string $sql, array $params): PDOStatement
    {
        $statement = Database::connect()->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();
        return $statement;
    }

    private function safeCell(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        return preg_match('/^[=+@]|^-(?!\d+(?:[.,]\d+)?$)/', $value) ? "'" . $value : $value;
    }
}
