<?php

return array (
  'id' => 'database-conventions',
  'title' => 'Типы, идентификаторы и временные поля',
  'paragraphs' => 
  array (
    0 => 'Обычные сущности используют INT UNSIGNED AUTO_INCREMENT. Быстро растущие журналы, партии импорта и значения пользовательских полей используют BIGINT UNSIGNED. Внешний ключ должен иметь тот же размер и признак UNSIGNED, что и связанный первичный ключ; несовпадение типов не позволит создать constraint.',
    1 => 'Булевы значения хранятся как TINYINT(1), ограниченные наборы состояний — как ENUM, произвольные структуры параметров — как JSON. ENUM удобен для фиксированного статуса, но добавление нового значения требует изменения схемы. JSON применяется только там, где структура действительно переменная: mapping импорта, фильтры экспорта, scopes и снимки аудита.',
    2 => 'created_at обычно заполняется CURRENT_TIMESTAMP, updated_at меняется автоматически через ON UPDATE CURRENT_TIMESTAMP. Предметные даты вроде last_login_at, started_at и finished_at являются DATETIME и устанавливаются приложением. Время сервера, PHP и MySQL должно быть согласовано, иначе фильтры и отчёты будут давать смещённые периоды.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Типовой каркас таблицы',
      'code' => 'CREATE TABLE projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_projects_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_projects_name (name),
    INDEX idx_projects_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
    ),
  ),
);

