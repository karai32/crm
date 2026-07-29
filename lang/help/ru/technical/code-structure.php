<?php

return array (
  'title' => 'Структура кода',
  'description' => 'Архитектура ContactCore, жизненный цикл запроса и правила разработки новых функций.',
  'icon' => 'ph-arrow-elbow-down-right',
  'sections' => 
  array (
    0 => 
    array (
      'id' => 'code-architecture',
      'title' => 'Архитектура приложения',
      'paragraphs' => 
      array (
        0 => 'ContactCore — серверное PHP-приложение без полноценного фреймворка. Оно построено как модульный монолит: интерфейс, API, бизнес-операции и доступ к данным находятся в одном проекте и работают с общей базой MySQL. Код разделён на слои по ответственности, поэтому его удобнее воспринимать как упрощённую MVC-архитектуру с отдельными репозиториями и сервисами.',
        1 => 'Основные паттерны проекта: Front Controller в public_html/index.php, Router для выбора обработчика, Controller для HTTP-сценария, Repository для SQL, Service Layer для бизнес-процессов и View с layout для HTML. API использует композицию: единый ApiController получает имя ресурса и соответствующий сервис; результат представляется ApiResult, а ожидаемая ошибка — ApiException.',
        2 => 'Граница слоёв важнее названия класса: контроллер управляет запросом, сервис принимает бизнес-решения, репозиторий работает с хранением, а представление только выводит подготовленные данные. Новый код следует размещать по этому правилу, а не в том файле, который оказался ближе.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Упрощённая схема слоёв',
          'code' => 'Браузер или внешний сервис
            │
            ▼
public_html/index.php  — Front Controller и bootstrap
            │
            ▼
Router                 — маршрут и HTTP-метод
            │
            ▼
Controller             — доступ, ввод, выбор сценария
        │           │
        ▼           ▼
     Service     Repository
        │           │
        └─────┬─────┘
              ▼
           MySQL/PDO
              │
              ▼
View + Layout → HTML   или   ApiResult → JSON',
        ),
      ),
    ),
    1 => 
    array (
      'id' => 'code-directories',
      'title' => 'Каталоги проекта',
      'paragraphs' => 
      array (
        0 => 'Весь исполняемый код приложения находится в app, публичная точка входа и статические файлы — в public_html. Конфигурация и storage специально расположены выше document root, чтобы их нельзя было получить обычным HTTP-запросом.',
        1 => 'Контроллеры, репозитории и представления обычно группируются по сущности: ContactController работает с ContactRepository и представлениями app/Views/contacts. Сложные процессы получают отдельную папку внутри Services, как сделано для импорта, экспорта и API.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Назначение основных каталогов',
          'code' => 'app/
├── Controllers/       HTTP-сценарии интерфейса, AJAX и API
├── Core/              Router, Database, Auth, View, Lang, CSRF
├── Helpers/           общие функции представлений
├── Repositories/      SQL и получение данных через PDO
├── Services/          бизнес-процессы и интеграции
└── Views/             PHP-шаблоны, layouts и partials

public_html/
├── index.php          единственная точка входа PHP
└── assets/            готовые CSS, JavaScript, шаблоны CSV/XLSX

bin/                   команды для запуска из CLI и cron
config/                локальная конфигурация и секреты
database/              первоначальная SQL-схема
lang/                  переводы интерфейса
storage/               изменяемые данные приложения и журналы
vendor/                зависимости Composer',
        ),
      ),
    ),
    2 => 
    array (
      'id' => 'code-request-lifecycle',
      'title' => 'Жизненный цикл HTTP-запроса',
      'paragraphs' => 
      array (
        0 => 'Nginx передаёт виртуальный URL в public_html/index.php. Точка входа выбирает каталог сессий, запускает сессию, устанавливает защитные заголовки, подключает Composer и классы приложения, загружает язык, создаёт контроллеры и регистрирует маршруты.',
        1 => 'Перед dispatch выполняется общая CSRF-проверка всех POST-запросов, кроме /api/v1. Затем Router отделяет путь от query string, учитывает установку в подкаталоге, ищет точный или параметризованный маршрут и вызывает назначенный метод. Значения сегментов вроде {id} Router записывает в $_GET, поэтому методы контроллеров не принимают их аргументами.',
        2 => 'Необработанные PDOException и Throwable перехватываются в конце точки входа. Пользователь получает нейтральный ответ 500, а техническое сообщение записывается в error_log и storage/app.log.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Последовательность обычного запроса',
          'code' => 'GET /clients/show?id=42
  → Nginx: /index.php
  → session_start() и Lang::load()
  → Router::dispatch(\'GET\', \'/clients/show?id=42\')
  → Router проверяет policy: auth = user
  → ClientController::show()
  → ClientRepository::find(42)
  → View::render(\'clients/show\', $data)
  → app/Views/layouts/main.php
  → HTML-ответ',
        ),
      ),
    ),
    3 => 
    array (
      'id' => 'code-bootstrap-routing',
      'title' => 'Bootstrap, загрузка классов и маршруты',
      'paragraphs' => 
      array (
        0 => 'Composer автоматически загружает только сторонние библиотеки. Собственные классы пока не используют namespaces и PSR-4: каждый новый PHP-файл нужно добавить через require_once в public_html/index.php до создания объекта, который от него зависит. Порядок подключения важен для наследования и type declarations.',
        1 => 'После подключений точка входа вручную создаёт экземпляры контроллеров и связывает HTTP-метод и путь с callable. Router поддерживает GET, POST, PATCH и DELETE, точные маршруты и параметры {name}. Третьим аргументом маршрут принимает политику доступа: auth, permission и формат отказа response. Автоматического поиска контроллера по URL нет.',
        2 => 'Сначала регистрируйте более конкретные маршруты, затем общие параметризованные. Каждый маршрут обязан получить policy: auth = public для сознательно открытой точки, auth = user/admin либо permission для защищённой. Router отвергнет регистрацию без политики. Для формирования внутренних ссылок используйте Auth::url(), иначе установка приложения в подкаталоге может сломаться.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Подключение и регистрация нового контроллера',
          'code' => 'require_once __DIR__ . \'/../app/Repositories/ProjectRepository.php\';
require_once __DIR__ . \'/../app/Services/ProjectService.php\';
require_once __DIR__ . \'/../app/Controllers/ProjectController.php\';

$projectController = new ProjectController();

// Сначала добавьте projects.manage в Auth::permissionDefinitions().

$router->get(\'/projects\', [$projectController, \'index\'], [\'auth\' => \'user\']);
$router->get(\'/projects/create\', [$projectController, \'create\'], [
    \'permission\' => \'projects.manage\',
]);
$router->post(\'/projects/store\', [$projectController, \'store\'], [
    \'permission\' => \'projects.manage\',
]);
$router->get(\'/projects/{id}\', [$projectController, \'show\'], [\'auth\' => \'user\']);',
        ),
      ),
    ),
    4 => 
    array (
      'id' => 'code-controllers',
      'title' => 'Контроллеры',
      'paragraphs' => 
      array (
        0 => 'Контроллер является адаптером между HTTP и кодом приложения. Router проверяет вход, роль или разрешение до вызова публичного метода. Контроллер читает $_GET, $_POST либо $_FILES, приводит простые значения к ожидаемым типам, вызывает репозиторий или сервис и выбирает ответ: HTML, redirect, JSON или код ошибки.',
        1 => 'Контроллеру допустимо координировать сценарий и выполнять простую валидацию формы. Сложные правила, повторно используемые операции и транзакции лучше выносить в сервис. SQL, HTML-разметка и прямое чтение конфигурационных файлов в контроллер добавлять не следует.',
        2 => 'После успешного POST обычно используется Post/Redirect/Get: данные сохраняются и выполняется Auth::redirect(). Это защищает от повторной отправки формы при обновлении страницы. При ошибке контроллер повторно рендерит форму с введёнными значениями и понятным сообщением.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Типовая операция создания',
          'code' => 'public function store(): void
{
    $name = trim($_POST[\'name\'] ?? \'\');
    if ($name === \'\') {
        View::render(\'projects/create\', [
            \'title\' => Lang::get(\'projects.create_title\'),
            \'error\' => Lang::get(\'projects.name_required\'),
            \'name\'  => $name,
        ]);
        return;
    }

    $this->projects->create($name);
    Auth::redirect(\'/projects\');
}',
        ),
      ),
    ),
    5 => 
    array (
      'id' => 'code-repositories',
      'title' => 'Репозитории и доступ к данным',
      'paragraphs' => 
      array (
        0 => 'Repository изолирует SQL конкретной области и возвращает обычные PHP-массивы, числа либо null. Подключение выдаёт Database::connect(): в пределах одного запроса метод повторно использует один экземпляр PDO, включает исключения для ошибок и возвращает строки как ассоциативные массивы.',
        1 => 'Значения всегда передаются в prepared statements. Имена столбцов и направление сортировки нельзя подставлять как параметры PDO, поэтому они выбираются только из жёсткого allowlist. Репозиторий может выполнять небольшое правило, непосредственно связанное с хранением, например удалить сектор без связей или деактивировать используемый.',
        2 => 'Не возвращайте PDOStatement за пределы репозитория без необходимости и не формируйте SQL из непроверенных значений запроса. Операцию над несколькими репозиториями объединяет сервис, а граница транзакции задаётся на уровне полного бизнес-сценария.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Безопасный запрос с allowlist сортировки',
          'code' => 'public function paginate(int $page, int $perPage, string $sort): array
{
    $allowed = [\'name\' => \'name\', \'created_at\' => \'created_at\'];
    $column = $allowed[$sort] ?? \'name\';
    $offset = ($page - 1) * $perPage;

    $statement = Database::connect()->prepare(
        "SELECT * FROM projects ORDER BY {$column} ASC LIMIT :limit OFFSET :offset"
    );
    $statement->bindValue(\'limit\', $perPage, PDO::PARAM_INT);
    $statement->bindValue(\'offset\', $offset, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}',
        ),
      ),
    ),
    6 => 
    array (
      'id' => 'code-services',
      'title' => 'Сервисы и бизнес-процессы',
      'paragraphs' => 
      array (
        0 => 'Service Layer используется там, где действие не сводится к одному запросу в одну таблицу. ContactWriteService и ClientWriteService являются единственной прикладной точкой создания и обновления контактов и клиентов: они нормализуют полный контракт основной записи и сохраняют теги, связи и пользовательские поля. HTML-контроллеры, API-сервисы и процессоры импорта адаптируют свой ввод и вызывают эти общие сервисы.',
        1 => 'Сервис может использовать несколько репозиториев и другие сервисы, но не должен зависеть от HTML. Составные операции выполняются через Database::transaction(). Метод открывает транзакцию, если её ещё нет, либо присоединяется к транзакции API-пакета или строки импорта. Поэтому вложенный сервис не пытается открыть неподдерживаемую PDO nested transaction.',
        2 => 'Небольшой сервис допускается создавать непосредственно в конструкторе контроллера: DI-контейнера в проекте пока нет. При развитии кода сохраняйте зависимости в типизированных private-свойствах, чтобы состав объекта оставался видимым.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Транзакционный бизнес-сценарий',
          'code' => '$contactId = $this->contactWriter->create(
    data: $contact,
    tagIds: $tagIds,
    clientIds: $clientIds,
    customFields: $fields,
    customValues: $values
);

// ContactWriteService сохраняет весь состав записи через
// Database::transaction() и возвращает id после успешного commit.',
        ),
      ),
    ),
    7 => 
    array (
      'id' => 'code-views',
      'title' => 'Представления, layouts и JavaScript',
      'paragraphs' => 
      array (
        0 => 'View::render() получает путь шаблона и массив данных, преобразует ключи массива в локальные переменные через extract(EXTR_SKIP), буферизует результат и вставляет его в layout. По умолчанию используется app/Views/layouts/main.php, а страницы входа используют layout auth. Общие части формы находятся в partial-файлах с именем, начинающимся с подчёркивания.',
        1 => 'В представление должны приходить уже подготовленные данные. SQL и бизнес-решения в шаблоне недопустимы. Любые динамические значения экранируются через htmlspecialchars(..., ENT_QUOTES, UTF-8); функция t() уже возвращает экранированный перевод. URL строятся через Auth::url(), а POST-форма содержит Csrf::field().',
        2 => 'CSS и JavaScript не собираются bundler-ом. Контроллер передаёт имена дополнительных файлов в styles и scripts, layout подключает их из public_html/assets. JavaScript отвечает за поведение интерфейса и AJAX, но сервер повторно проверяет доступ и входные данные: браузерная проверка не является защитой.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Передача данных из контроллера',
          'code' => 'View::render(\'projects/index\', [
    \'title\'    => Lang::get(\'projects.title\'),
    \'styles\'   => [\'settings.css\'],
    \'scripts\'  => [\'projects.js\'],
    \'projects\' => $this->projects->paginate($page, $perPage, $sort),
]);',
        ),
        1 => 
        array (
          'title' => 'Безопасный PHP-шаблон формы',
          'code' => '<form method="post" action="<?= htmlspecialchars(Auth::url(\'/projects/store\'), ENT_QUOTES, \'UTF-8\') ?>">
    <?= Csrf::field() ?>
    <input
        name="name"
        value="<?= htmlspecialchars($name ?? \'\', ENT_QUOTES, \'UTF-8\') ?>"
        required
    >
    <button type="submit"><?= t(\'common.save\') ?></button>
</form>',
        ),
      ),
    ),
    8 => 
    array (
      'id' => 'code-auth-security',
      'title' => 'Авторизация и границы безопасности',
      'paragraphs' => 
      array (
        0 => 'Auth хранит минимальные данные вошедшего пользователя в сессии, восстанавливает remember-login и вычисляет разрешения. Router централизованно применяет политику каждого веб- и AJAX-маршрута до вызова обработчика. Администратор получает все известные права; неизвестный ключ, отсутствующая строка и ошибка загрузки запрещают действие. Скрытый пункт меню является только элементом интерфейса и не заменяет policy.',
        1 => 'CSRF-токен хранится в сессии. Точка входа централизованно проверяет обычные POST-запросы, поэтому каждая такая форма должна добавлять Csrf::field(). API исключён из CSRF-проверки, потому что использует HTTP Basic с client_id и secret, scopes и собственный журнал запросов.',
        2 => 'Данные из $_GET, $_POST, $_FILES, JSON и заголовков всегда считаются недоверенными. Их нужно нормализовать, проверять по allowlist и только затем передавать дальше. Экранирование выполняется при HTML-выводе, а не при сохранении в базу.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Выбор уровня доступа',
          'code' => '$router->get(\'/dashboard\', [$dashboardController, \'index\'], [
    \'auth\' => \'user\',
]);

$router->post(\'/contacts/update\', [$contactController, \'update\'], [
    \'permission\' => \'contacts.edit\',
]);

$router->post(\'/ajax/admin-task\', [$ajaxController, \'adminTask\'], [
    \'auth\' => \'admin\',
    \'response\' => \'json\',
]);',
        ),
      ),
    ),
    9 => 
    array (
      'id' => 'code-localization',
      'title' => 'Локализация',
      'paragraphs' => 
      array (
        0 => 'Язык хранится в сессии и загружается через Lang::load(). Поддерживаются ru, es и en; если в русском или испанском файле нет ключа, Lang добавляет английские значения как fallback. Переводы находятся в плоских массивах lang/ru.php, lang/es.php и lang/en.php.',
        1 => 'Для текста интерфейса используйте Lang::get() в PHP-логике и t() в HTML. Новый ключ нужно добавить во все языковые файлы с одинаковым именем. Пользовательские и полученные из базы значения не являются переводами и экранируются отдельно.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Ключ с подстановкой',
          'code' => '// lang/ru.php
\'projects.created\' => \'Проект «:name» создан.\',

// Контроллер или сервис
$message = Lang::get(\'projects.created\', [\'name\' => $project[\'name\']]);

// Представление: t() сразу экранирует результат
<h1><?= t(\'projects.title\') ?></h1>',
        ),
      ),
    ),
    10 => 
    array (
      'id' => 'code-api-internals',
      'title' => 'Внутренняя структура API',
      'paragraphs' => 
      array (
        0 => 'API использует тот же Front Controller и Router, но имеет отдельную цепочку классов. Единый ApiController реализует стандартные CRUD-методы, аутентификацию, scopes, разбор JSON, единый формат ошибок, X-Request-Id и запись api_logs. В точке входа создаются два экземпляра с ContactApiService и ClientApiService. Различия ресурсов находятся в сервисах, а микроклассы-контроллеры не используются.',
        1 => 'Каждый метод API-сервиса возвращает ApiResult со статусом, телом и количеством элементов. Ожидаемая бизнес-ошибка представляется ApiException со статусом, кодом и деталями. Неожиданные исключения не раскрываются клиенту, но попадают в серверный журнал с request ID.',
        2 => 'Пакетное создание обрабатывается AbstractApiService::batch(): каждый элемент получает отдельную транзакцию и отдельный результат, а общий ответ имеет статус 207. При добавлении API-ресурса нужно создать содержательный сервис и настроить общий контроллер, а не копировать обработку ключей, JSON и журналирования.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Настройка контроллера ресурса',
          'code' => '$apiControllers = [
    \'contacts\' => new ApiController(\'contacts\', new ContactApiService()),
    \'clients\' => new ApiController(\'clients\', new ClientApiService()),
];

// Один цикл регистрирует GET/POST/PATCH/DELETE для каждого ресурса.',
        ),
        1 => 
        array (
          'title' => 'Результат и ожидаемая ошибка',
          'code' => 'return new ApiResult(200, [
    \'success\' => true,
    \'data\' => $project,
], 1);

throw new ApiException(
    422,
    \'validation_error\',
    \'Project validation failed\',
    [\'name is required\']
);',
        ),
      ),
    ),
    11 => 
    array (
      'id' => 'code-feature-flow',
      'title' => 'Как проходит одна операция',
      'paragraphs' => 
      array (
        0 => 'Например, создание контакта через интерфейс начинается с проверки contacts.create политикой POST-маршрута. Затем ContactController нормализует поля формы, проверяет обязательное имя и классифицирует email. Он передаёт данные, id тегов, клиентов и пользовательские значения в ContactWriteService. Сервис одной транзакцией создаёт основную запись и все её связи, после чего контроллер выполняет redirect.',
        1 => 'Та же операция через API идёт в настроенный для contacts экземпляр ApiController, затем в ContactApiService. API-сервис проверяет JSON, запрещает внутреннюю почту и дубликаты, преобразует имена тегов и клиентов в id и вызывает тот же ContactWriteService. Пакетная транзакция охватывает также автоматически созданные справочники, а результат возвращается как ApiResult и журналируется в api_logs.',
        2 => 'Импорт использует ту же границу записи: ContactImportProcessor и ClientImportProcessor подготавливают значения строки и вызывают ContactWriteService или ClientWriteService внутри транзакции строки. Транспортная валидация пока различается между HTML, API и импортом, но сам контракт хранения, значения состояний клиента и атомарность составной записи едины.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Два входа в одну предметную область',
          'code' => 'HTML-форма
  → ContactController
  → EmailInspector → ContactWriteService
  → redirect + HTML

JSON API
  → ApiController::handle() [contacts]
  → ContactApiService
  → ContactWriteService
  → ApiResult + api_logs',
        ),
      ),
    ),
    12 => 
    array (
      'id' => 'code-new-feature',
      'title' => 'Порядок добавления нового раздела',
      'paragraphs' => 
      array (
        0 => 'Сначала определите сущность, пользовательские сценарии и разрешения. Затем подготовьте изменение базы — этот шаг будет подробно описан в следующем разделе документации. После базы создайте Repository, при необходимости Service, Controller, маршруты и Views. В конце добавьте переводы, пункт меню, CSS/JavaScript и серверные проверки доступа.',
        1 => 'Для простого справочника обычно достаточно Repository + Controller + Views. Для операции над несколькими сущностями нужен Service. Для нового внешнего JSON-ресурса добавляется содержательный ApiService и конфигурация общего ApiController. Универсальным остаётся только HTTP-протокол; предметную логику разных областей не следует смешивать в одном сервисе.',
        2 => 'Перед завершением проверьте успешный сценарий, пустые и некорректные данные, пользователя без разрешения, отсутствующую запись, повторную отправку формы, экранирование вывода и откат транзакции. Для API дополнительно проверяются неверный ключ, недостаточный scope, некорректный JSON и запись request ID в журнал.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Минимальный комплект новой сущности',
          'code' => 'app/Repositories/ProjectRepository.php
app/Controllers/ProjectController.php
app/Views/projects/index.php
app/Views/projects/create.php
app/Views/projects/edit.php
app/Views/projects/_form.php
public_html/assets/js/projects.js        # если нужно поведение
public_html/assets/css/projects.css      # если базовых стилей недостаточно
lang/ru.php, lang/en.php, lang/es.php
public_html/index.php                    # require_once, объект, маршруты',
        ),
      ),
    ),
    13 => 
    array (
      'id' => 'code-conventions',
      'title' => 'Соглашения и текущие ограничения',
      'paragraphs' => 
      array (
        0 => 'Классы приложения пока находятся в глобальном пространстве имён, а зависимости создаются вручную. Название файла должно совпадать с назначением класса, один основной класс размещается в одном файле, свойства и возвращаемые значения типизируются. Не меняйте этот стиль точечно в одном модуле: переход на namespaces, PSR-4 или DI следует проводить как отдельный согласованный рефакторинг.',
        1 => 'В проекте нет ORM, механизма миграций и каталога автоматических тестов. database/schema.sql предназначен для первой установки и не является миграцией. До появления тестового контура изменение проверяется PHP lint, сценариями интерфейса и API и просмотром журналов; критичные предметные правила желательно постепенно покрывать модульными или интеграционными тестами.',
        2 => 'Комментарии должны объяснять причину или ограничение, а не повторять код. Новая логика не должна выводить секреты и внутренние исключения пользователю. Неожиданная ошибка журналируется с достаточным контекстом, но пароли, API secrets, SMTP-ключи и полные чувствительные payload в обычный журнал не записываются.',
      ),
      'examples' => 
      array (
        0 => 
        array (
          'title' => 'Базовые проверки изменённых PHP-файлов',
          'code' => 'php -l app/Controllers/ProjectController.php
php -l app/Repositories/ProjectRepository.php
php -l app/Views/projects/index.php
composer check-platform-reqs --no-dev',
        ),
      ),
    ),
  ),
);
