<?php

return array (
  'id' => 'database-users',
  'title' => 'Пользователи, роли и настройки',
  'paragraphs' => 
  array (
    0 => 'roles содержит системные роли admin и user. users хранит имя, уникальный email, password_hash, активность и дату последнего входа. Пароль никогда не хранится открытым текстом: PHP создаёт его через password_hash(), а вход проверяет password_verify(). Удаление используемой роли запрещено ON DELETE RESTRICT.',
    1 => 'user_permissions хранит индивидуальное решение по каждому permission_key. Составной PRIMARY KEY (user_id, permission_key) не позволяет создать два значения одного разрешения для пользователя. При удалении пользователя разрешения удаляются каскадно.',
    2 => 'user_preferences — расширяемое key-value хранилище настроек интерфейса. Сейчас приложение использует ключ per_page. Уникальная пара user_id + preference_key позволяет применять INSERT ... ON DUPLICATE KEY UPDATE. Настройки не следует смешивать с разрешениями: preference влияет на удобство интерфейса, permission — на доступ к операции.',
  ),
  'examples' => 
  array (
    0 => 
    array (
      'title' => 'Пользователь и его явные разрешения',
      'code' => 'SELECT
    u.id,
    u.name,
    u.email,
    r.name AS role,
    up.permission_key,
    up.is_allowed
FROM users u
INNER JOIN roles r ON r.id = u.role_id
LEFT JOIN user_permissions up ON up.user_id = u.id
WHERE u.id = :user_id
ORDER BY up.permission_key;',
    ),
  ),
);

