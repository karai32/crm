<?php

return [
    'title' => 'Внутреннее устройство API',
    'description' => 'Архитектура публичного API ContactCore: маршрутизация, Basic Auth, scopes, сервисы ресурсов, транзакции, ответы и журналирование.',
    'icon' => 'ph-arrow-elbow-down-right',
    'sections' => [
        [
            'id' => 'api-internal-boundary',
            'title' => 'Место API в приложении',
            'paragraphs' => [
                'Публичный API — отдельная HTTP-граница того же модульного монолита ContactCore. Он работает с общей базой, репозиториями и предметными сущностями, но не использует браузерную сессию, HTML-представления или AJAX-контракты. Все маршруты текущей версии находятся под /api/v1 и возвращают JSON.',
                'API предназначен прежде всего для server-to-server интеграций: форм на сайтах клиентов, синхронизации с внешними приложениями и пакетного обмена. Внутренние /ajax/* используют сессию и CSRF, а /api/v1/* использует HTTP Basic Auth и scopes. Эти интерфейсы нельзя взаимозаменять даже тогда, когда они вызывают один репозиторий.',
                'Архитектурно запрос проходит через Router, специализированный ApiController, общий AbstractApiController, ApiAuthenticator, ресурсный ApiService и Repository. Контроллер отвечает за протокол, сервис — за прикладное поведение ресурса, репозиторий — за SQL. Ответ и журнал запроса снова формирует общий контроллер.',
            ],
            'examples' => [
                [
                    'title' => 'Полный путь API-запроса',
                    'code' => <<<'CODE'
External system
      │ HTTPS + Basic Auth + JSON
      ▼
public_html/index.php
      ▼
Router
      ▼
ContactApiController / ClientApiController / ...
      ▼
AbstractApiController::handle()
      ├── ApiAuthenticator
      ├── scope check
      ├── JSON decoding
      ▼
ContactApiService / ClientApiService / ...
      ▼
Repository → MySQL
      ▼
ApiResult → JSON response → api_logs
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-routes',
            'title' => 'Ресурсы и маршрутизация',
            'paragraphs' => [
                'Сейчас опубликованы четыре ресурса: contacts, clients, sectors и tags. Для каждого зарегистрирован одинаковый CRUD-набор. Коллекция обслуживает GET и POST, конкретная запись — GET, PATCH и DELETE. Отдельных action-маршрутов в /api/v1 нет.',
                'Router хранит точные и параметризованные маршруты отдельно. При совпадении шаблона значение {id} записывается в $_GET, после чего AbstractApiController::routeId() приводит его к int. Нулевое, отрицательное и нечисловое значение превращается в ошибку 404. Router не передаёт параметры аргументами метода контроллера.',
                'Версия является частью URL, а не заголовка. Несовместимое изменение контракта должно получить новый префикс, например /api/v2, при сохранении v1 на согласованный переходный период. Добавление необязательного поля или нового фильтра обычно совместимо в пределах v1.',
            ],
            'examples' => [
                [
                    'title' => 'Текущий CRUD-контракт',
                    'code' => <<<'CODE'
GET     /api/v1/{resource}
GET     /api/v1/{resource}/{id}
POST    /api/v1/{resource}
PATCH   /api/v1/{resource}/{id}
DELETE  /api/v1/{resource}/{id}

resource = contacts | clients | sectors | tags
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-controller',
            'title' => 'Контроллеры и Template Method',
            'paragraphs' => [
                'ContactApiController, ClientApiController, SectorApiController и TagApiController являются минимальными адаптерами. Каждый объявляет resource() для имени ресурса и makeService() для создания сервиса. Публичные методы index, show, create, update и destroy наследуются из AbstractApiController.',
                'Это применение Template Method: общий класс фиксирует протокол и жизненный цикл, а дочерний выбирает ресурсную реализацию. index и show требуют scope {resource}:read; create, update и destroy — {resource}:write. Путь, переданный в handle(), используется для журнала, поэтому маршруты конкретной записи сохраняются как шаблон /{id}, а не как фактический URL с числом.',
                'В начале handle() считывается php://input, выполняется аутентификация, создаётся 24-символьный request id и отправляется X-Request-Id. Затем проверяется scope, обновляется last_used_at и вызывается сервис. ApiException, PDOException и остальные Throwable преобразуются в стабильный JSON-ответ; необработанная внутренняя ошибка не раскрывает текст исключения клиенту.',
            ],
            'examples' => [
                [
                    'title' => 'Ресурсный контроллер',
                    'code' => <<<'PHP'
class ProjectApiController extends AbstractApiController
{
    protected function resource(): string
    {
        return 'projects';
    }

    protected function makeService(): AbstractApiService
    {
        return new ProjectApiService();
    }
}
PHP,
                ],
            ],
        ],
        [
            'id' => 'api-internal-auth',
            'title' => 'API-ключи и Basic Auth',
            'paragraphs' => [
                'Учётная запись интеграции состоит из client_id и случайного секрета. ApiKeyController генерирует client_id с префиксом crm_ и 16 случайными байтами, а секрет — из 32 случайных байтов. Client ID здесь обозначает API-клиента и никак не связан с сущностью Client в CRM.',
                'Секрет показывается администратору один раз и сохраняется только как SHA-256 hash. ApiAuthenticator извлекает Basic credentials из PHP_AUTH_USER/PHP_AUTH_PW либо вручную разбирает Authorization из HTTP_AUTHORIZATION, REDIRECT_HTTP_AUTHORIZATION или getallheaders(). Это учитывает различия PHP-FPM и конфигураций веб-сервера.',
                'После поиска активного ключа по client_id предоставленный секрет хешируется, а сравнение выполняется hash_equals(). Basic Auth не шифрует credentials, поэтому API допустим только через HTTPS. Отозванный ключ имеет is_active = 0 и больше не находится аутентификатором; повторное включение возвращает доступ с тем же секретом.',
                'last_used_at обновляется не чаще одного раза в пять минут, чтобы каждый API-запрос не создавал дополнительную запись. Удаление ключа физическое; связанные api_logs сохраняются благодаря ON DELETE SET NULL, но теряют ссылку на название интеграции.',
            ],
            'examples' => [
                [
                    'title' => 'Проверка credentials',
                    'code' => <<<'CODE'
Authorization: Basic base64(CLIENT_ID:SECRET)

client_id → SELECT active api_keys row
SECRET    → sha256(SECRET)
stored    → api_keys.secret_hash

hash_equals(stored, provided) → authenticated API key
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-scopes',
            'title' => 'Scopes и авторизация операций',
            'paragraphs' => [
                'Scopes хранятся JSON-массивом в api_keys. Для каждого ресурса определены read и write: contacts:read, contacts:write и аналогичные права для clients, sectors и tags. Новые ключи получают все восемь текущих scopes; syncScopes заменяет набор старого ключа полным актуальным списком.',
                'hasScope() сначала ищет точное совпадение. Для операции чтения также принимается соответствующий write: contacts:write неявно даёт contacts:read. Обратное неверно. Невалидный JSON в scopes трактуется как отсутствие прав.',
                'Scopes проверяются до декодирования JSON и обращения к ресурсному сервису. Ошибка аутентификации возвращает 401 и WWW-Authenticate, недостаток scope — 403. Проверка выполняется для каждого маршрута независимо; наличие ключа не означает доступ ко всем ресурсам.',
            ],
            'examples' => [
                [
                    'title' => 'Матрица доступа',
                    'code' => <<<'CODE'
contacts:read
  ✓ GET /api/v1/contacts
  ✓ GET /api/v1/contacts/{id}
  ✗ POST / PATCH / DELETE

contacts:write
  ✓ GET /api/v1/contacts
  ✓ GET /api/v1/contacts/{id}
  ✓ POST / PATCH / DELETE
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-input',
            'title' => 'Чтение и разбор входных данных',
            'paragraphs' => [
                'AbstractApiController считывает и trim-ит php://input один раз на запрос. PATCH вызывает jsonObject() и принимает только непустой JSON-объект. POST вызывает jsonBatch(): одиночный объект превращается в массив из одного элемента, а JSON-массив используется как пакет. Пустое тело, скаляр, повреждённый JSON и пакет более 100 элементов возвращают 422.',
                'Проверка заголовка Content-Type сейчас отсутствует: фактически принимается любое тело, которое удаётся разобрать как JSON. Для предсказуемого публичного контракта клиент всё равно должен отправлять application/json, а при дальнейшем ужесточении сервера неподходящий Content-Type следует отвечать статусом 415.',
                'GET-фильтры читаются из $_GET внутри ресурсного сервиса. Контакты и клиенты нормализуют page минимум до 1, per_page — в диапазон 1–100 с default 25. Сектора и теги возвращают весь отфильтрованный набор без постраничной выдачи.',
                'PATCH имеет семантику частичного обновления: отсутствие ключа сохраняет текущее значение, а явный null или пустая строка очищает поддерживаемое необязательное поле. Для tags и clients важно различать отсутствие ключа и переданный пустой набор: пустой набор удаляет все соответствующие связи. Пустой объект custom_fields ничего не меняет; чтобы очистить конкретное пользовательское поле, нужно передать его slug со значением null или пустой строкой.',
            ],
            'examples' => [
                [
                    'title' => 'Нормализация POST',
                    'code' => <<<'CODE'
Одиночный объект:
{"full_name":"Ana"}
→ items[0] = {"full_name":"Ana"}

Пакет:
[
  {"full_name":"Ana"},
  {"full_name":"Luis"}
]
→ items[0], items[1]

Максимум: 100 элементов
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-services',
            'title' => 'Сервисный слой ресурсов',
            'paragraphs' => [
                'AbstractApiService задаёт одинаковую поверхность CRUD: index(), show(), createBatch(), update() и destroy(). Он также содержит общие операции для nullable-строк, поиска записи, тегов, пользовательских полей, пакетной обработки и входных списков. Ресурсный сервис отвечает за валидацию, прикладные связи и форму data в ответе.',
                'ContactApiService проверяет full_name и email, запрещает внутренние email, выполняет EmailInspector, разрешает теги и клиентов по именам и при необходимости создаёт их. ClientApiService разрешает сектор и теги по именам. SectorApiService при удалении используемого сектора деактивирует его; TagApiService удаляет тег физически.',
                'Сервисы не возвращают строки базы напрямую. Методы detail() и format() приводят id к int, флаги к bool и явно выбирают поля ответа. Это защищает контракт от случайного появления password_hash, внутренних флагов или новых колонок после SELECT *.',
                'Общие предметные правила пока не полностью разделяются с HTML-интерфейсом и импортом. Например, валидация контакта реализована отдельно в ContactApiService. При добавлении правила, которое должно действовать во всех каналах, его следует вынести в общий доменный сервис, а не копировать ещё раз.',
            ],
            'examples' => [
                [
                    'title' => 'Ответственность слоёв',
                    'code' => <<<'CODE'
ApiController
  HTTP method, auth, scope, JSON, status, headers, log

ApiService
  validation, business operation, transaction, response DTO

Repository
  prepared SQL, persistence, queries
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-batch',
            'title' => 'Пакеты и транзакционные границы',
            'paragraphs' => [
                'Любой POST создания обрабатывается через AbstractApiService::batch(). Для каждого элемента отдельно открывается транзакция на общем PDO, вызывается процессор и выполняется commit. ApiException, PDOException или другая ошибка откатывает только текущий элемент; обработка следующих элементов продолжается.',
                'Поэтому пакет не является атомарным целиком. Если первые девять записей созданы, а десятая ошиблась, девять остаются в базе. Зато состав одного элемента — основная запись, автоматически созданные справочники, связи, теги и пользовательские поля — должен либо сохраниться весь, либо откатиться весь.',
                'Результат POST всегда имеет HTTP 207 Multi-Status, даже если передан один объект и даже если все элементы успешны или все ошибочны. Верхний success означает, что пакет разобран и обработан, а не что каждая запись создана. Интеграция обязана проверить data.results[*].success и сохранить index для сопоставления с исходным массивом.',
                'PATCH контакта и клиента также открывает транзакцию вокруг основной записи, связей и пользовательских полей. Обновление и удаление сектора или тега состоят из более простой операции репозитория. Встроенной идемпотентности POST нет: повтор после сетевого таймаута может создать дубликат, если предметная проверка не распознаёт запись.',
            ],
            'examples' => [
                [
                    'title' => 'Частично успешный пакет',
                    'code' => <<<'JSON'
HTTP/1.1 207 Multi-Status

{
  "success": true,
  "data": {
    "processed": 2,
    "created": 1,
    "failed": 1,
    "results": [
      {"index": 0, "success": true, "data": {"contact_id": 125}},
      {
        "index": 1,
        "success": false,
        "error": {
          "code": "duplicate_contact",
          "details": ["Contact with this email already exists"]
        }
      }
    ]
  }
}
JSON,
                ],
            ],
        ],
        [
            'id' => 'api-internal-relations',
            'title' => 'Связи, справочники и пользовательские поля',
            'paragraphs' => [
                'tags и clients принимают одно имя, строку с именами через запятую либо JSON-массив. splitNames() удаляет пустые значения и игнорирует сложные элементы массива. Имена разрешаются в id; отсутствующие теги создаются автоматически, а ContactApiService также создаёт минимального клиента по commercial_name. ClientApiService аналогично создаёт отсутствующий сектор.',
                'В PATCH переданный tags или clients является полным итоговым набором и заменяет существующие связи через sync(). Это не операция добавления. Если ключ отсутствует, связи не меняются. Интеграция должна сначала получить текущее состояние, если хочет сохранить старые элементы и добавить один новый.',
                'custom_fields поддерживает вложенный объект и ключи custom_fields.{slug}. expandCustomFieldKeys() сворачивает точечные ключи во вложенный массив. saveCustomFields() находит только заранее созданные поля подходящего entity_type; неизвестный slug молча пропускается. При создании применяются default_value полей, не переданных интеграцией.',
                'Типы ответа нормализуются: number становится float, checkbox — bool, date и текст остаются строками, отсутствующее значение — null. Обязательность и допустимые select-значения не закреплены единым валидатором API, поэтому при развитии контрактов требуется отдельная проверка типов и is_required.',
            ],
            'examples' => [
                [
                    'title' => 'Эквивалентные формы входа',
                    'code' => <<<'JSON'
{"tags":"Lead,Newsletter"}
{"tags":["Lead","Newsletter"]}

{"custom_fields":{"language":"ru","consent":true}}
{"custom_fields.language":"ru","custom_fields.consent":true}
JSON,
                ],
            ],
        ],
        [
            'id' => 'api-internal-results',
            'title' => 'ApiResult, ошибки и HTTP-статусы',
            'paragraphs' => [
                'ApiResult — простой объект результата со status, data и необязательным itemsCount для журнала. Успешные методы сервиса возвращают его явно. Если action вернул другой тип, общий контроллер считает это ошибкой программирования и формирует 500.',
                'ApiException переносит ожидаемую прикладную ошибку: HTTP-статус, стабильный errorCode, сообщение и details. AbstractApiController превращает её в {success:false,error:{code,message,details}}. Нарушение ограничения базы с SQLSTATE 23000 становится 409 conflict; прочие PDOException и Throwable журналируются сервером и возвращают безопасный 500 server_error.',
                'Чтение, PATCH и DELETE обычно возвращают 200. POST создания — 207. Используются также 401, 403, 404, 409, 422 и 500; ошибка внешнего сервиса в этом API сейчас не является отдельным сценарием. Ответ 401 дополнительно содержит WWW-Authenticate, каждый ответ — Content-Type JSON и X-Request-Id.',
                'Клиент должен принимать решение по HTTP-статусу и телу вместе. В частности, 207 является успешным 2xx для HTTP-библиотеки, но может содержать ошибки элементов. error.code предназначен для программной логики, message и details — для диагностики; сравнивать интеграционную логику с полным английским текстом message не следует.',
            ],
            'examples' => [
                [
                    'title' => 'Обычная ошибка контроллера',
                    'code' => <<<'JSON'
HTTP/1.1 422 Unprocessable Entity
X-Request-Id: a8d94b7b912ac2aeaa15cc11

{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Contact validation failed",
    "details": ["full_name is required"]
  }
}
JSON,
                ],
            ],
        ],
        [
            'id' => 'api-internal-logging',
            'title' => 'Журналирование и наблюдаемость',
            'paragraphs' => [
                'finish() сначала отправляет JSON, затем пытается записать api_logs. В журнал попадают api_key_id, request_id, метод, шаблон пути, статус, error_code, items_count, IP, длительность, origin, тело запроса и тело ответа. Неавторизованная попытка также журналируется, но api_key_id у неё равен NULL.',
                'Тела ограничиваются примерно 64 КБ каждое. Origin берётся из Origin, а при его отсутствии — из Referer, и сокращается до 255 символов. Ошибка записи журнала не меняет уже сформированный API-ответ: она уходит в PHP error_log с request id.',
                'Администратор просматривает журнал через /api-logs и фильтрует по ключу, методу, группе статуса, пути и датам. X-Request-Id нужно передавать службе поддержки и сохранять во внешней системе. Для маршрутов с id журнал сейчас содержит /api/v1/resource/{id}, поэтому одного поля path недостаточно для определения конкретной записи.',
                'request_body и response_body могут содержать имена, email, телефоны, адреса и значения пользовательских полей. Сейчас маскирование и автоматическая политика хранения не реализованы. Перед production-эксплуатацией необходимо определить срок хранения, ограничить доступ и редактировать либо не записывать секретные и чувствительные поля.',
            ],
            'examples' => [
                [
                    'title' => 'Связка журналов двух систем',
                    'code' => <<<'CODE'
External log:
  crm_request_id=a8d94b7b912ac2aeaa15cc11
  local_form_submission=98731

ContactCore api_logs:
  request_id=a8d94b7b912ac2aeaa15cc11
  response_status=422
  error_code=validation_error
  duration_ms=18
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-security',
            'title' => 'Безопасность и эксплуатационные ограничения',
            'paragraphs' => [
                'Публичный API исключён из сессионной CSRF-проверки, потому что не использует cookie-аутентификацию. Его граница безопасности — HTTPS, случайный секрет, Basic Auth, активность ключа и scopes. Секрет нельзя передавать в браузерный JavaScript, URL, репозиторий или журнал приложения интеграции.',
                'CORS-заголовки API не формирует, поэтому прямой запрос с чужого browser origin обычно блокируется браузером. Это соответствует server-to-server модели. Если появится необходимость браузерного клиента, нельзя просто разрешить Access-Control-Allow-Origin: требуется отдельная модель короткоживущих credentials, ограниченных origins и угроз.',
                'В API нет встроенного rate limiter, квот, idempotency key, replay-защиты поверх Basic Auth и ограничения общего размера HTTP-тела до json_decode. Лимит 100 относится к количеству элементов, а не байтам. Эти ограничения следует реализовать на reverse proxy и/или в приложении до подключения недоверенных или высоконагруженных источников.',
                'EmailInspector выполняет живой DNS-запрос при создании контакта и при PATCH email. Пакет из множества разных доменов может увеличить время ответа. Внешняя система должна иметь разумный timeout и retry с backoff, но повтор POST без идемпотентности нельзя выполнять вслепую.',
            ],
            'examples' => [
                [
                    'title' => 'Минимальный production-контур',
                    'code' => <<<'CODE'
Internet
   ▼
HTTPS reverse proxy
  - body size limit
  - request rate limit
  - access/error logs
  - Authorization forwarding
   ▼
PHP-FPM / ContactCore
  - Basic Auth
  - scopes
  - validation and transactions
  - api_logs with retention policy
CODE,
                ],
            ],
        ],
        [
            'id' => 'api-internal-known-gaps',
            'title' => 'Текущие рассогласования и технический долг',
            'paragraphs' => [
                'ClientRepository::create() требует параметры is_web_connected и is_active, однако ClientApiService::createOne() и ContactApiService::createBlankClient() их сейчас не передают. ClientRepository::update() также ожидает оба флага, а ClientApiService::update() формирует массив без них. В результате создание клиента через API, автоматическое создание клиента из contacts и PATCH клиента могут завершаться ошибкой параметров PDO. Контракт и репозиторий необходимо выровнять: сервис должен задавать явные defaults и сохранять текущие состояния при PATCH либо репозиторий должен делать это сам.',
                'Проверки уникальности и валидации распределены между сервисами. API контактов проверяет дубликат email, но база не имеет UNIQUE на contacts.email; конкурентные запросы способны одновременно пройти SELECT и создать повтор. Неизвестные custom field slug пропускаются без ошибки. Для строгой интеграции эти правила нужно централизовать и закрепить тестами или ограничениями базы.',
                'Полноценного автоматического набора API-тестов и опубликованной машинной спецификации OpenAPI в проекте нет. Поэтому изменение маршрута или ответа легко нарушит внешнюю интеграцию незаметно для интерфейса CRM. Приоритетные улучшения — integration tests, OpenAPI, redaction журналов, rate limiting и idempotency.',
            ],
            'examples' => [
                [
                    'title' => 'Ожидаемые значения клиента на границе репозитория',
                    'code' => <<<'PHP'
$data = [
    'commercial_name'  => $commercialName,
    // ...остальные реквизиты...
    'is_web_connected' => 0,
    'is_active'        => 1,
];

$clientId = $this->clients->create($data);
PHP,
                ],
            ],
        ],
        [
            'id' => 'api-internal-extension',
            'title' => 'Добавление или изменение ресурса',
            'paragraphs' => [
                'Новый ресурс требует репозитория, класса ApiService, тонкого ApiController, регистрации require_once и пяти маршрутов в public_html/index.php. Затем scope добавляется в создание ключа, syncScopes и административное отображение. Если ресурс не поддерживает какую-либо CRUD-операцию, это нужно выразить осознанным 405/контрактом, а не оставлять абстрактный метод с неожиданной 500.',
                'До публикации определяются поля запроса и ответа, фильтры, пределы пагинации, правила PATCH, транзакционная граница, стабильные error.code и последствия DELETE. Поля ответа следует собирать явно. Любое новое значение, которое может содержать персональные данные, нужно проверить в журналировании.',
                'Минимальная проверка охватывает: отсутствие Authorization, неверный и отозванный секрет, недостаточный scope, чтение по существующему и отсутствующему id, повреждённый JSON, пустой PATCH, одиночный и пакетный POST, частичный успех, конфликт базы, rollback связей, фильтры, границы page/per_page и запись api_logs. Тест должен проверять не только status, но и схему JSON и X-Request-Id.',
            ],
            'examples' => [
                [
                    'title' => 'Контрольный список нового ресурса',
                    'code' => <<<'CODE'
[ ] Repository и миграция/индексы
[ ] ResourceApiService implements 5 methods
[ ] ResourceApiController: resource() + makeService()
[ ] require_once и GET/POST/PATCH/DELETE routes
[ ] resource:read и resource:write во всех местах управления keys
[ ] validation, response DTO и stable error codes
[ ] transaction boundary and delete semantics
[ ] api_logs redaction/retention impact
[ ] integration tests and API help
[ ] backward compatibility or new /api/v2
CODE,
                ],
            ],
        ],
    ],
];
