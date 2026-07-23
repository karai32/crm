<?php

class HelpController
{
    public function index(): void
    {
        $this->renderTopic('start');
    }

    public function show(): void
    {
        $topic = trim((string) ($_GET['topic'] ?? ''), '/');

        $aliases = [
            'getting-started' => 'start',
            'tags-sectors' => 'sectors-tags',
            'imports' => 'import-export',
            'exports' => 'import-export',
            'ai' => 'ai-tools',
            'users-roles' => 'users-settings',
            'technical-guide' => 'technical',
        ];

        $this->renderTopic($aliases[$topic] ?? $topic);
    }

    private function renderTopic(string $topic): void
    {
        Auth::requireLogin();

        $locale = in_array(Lang::locale(), ['ru', 'es', 'en'], true) ? Lang::locale() : 'en';
        $copy = $this->copy()[$locale];
        $navigation = $copy['navigation'];
        $activeIndex = array_search($topic, array_column($navigation, 'id'), true);

        if ($activeIndex === false) {
            Auth::redirect('/help');
            return;
        }

        $page = $navigation[$activeIndex];
        $page['sections'] = $topic === 'technical'
            ? $copy['technical_sections']
            : $this->articleSections($topic, $page, $copy['article']);

        View::render('help/index', [
            'title' => $page['title'] . ' — ' . $copy['center_title'],
            'styles' => ['help.css'],
            'scripts' => ['help.js'],
            'locale' => $locale,
            'copy' => $copy,
            'navigation' => $navigation,
            'activeIndex' => $activeIndex,
            'page' => $page,
        ]);
    }

    private function articleSections(string $topic, array $page, array $article): array
    {
        if (isset($article['sections'][$topic])) {
            return $article['sections'][$topic];
        }

        $headings = $article['headings'][$topic] ?? $article['default_headings'];

        return [
            [
                'id' => 'overview',
                'title' => $headings[0],
                'paragraphs' => [
                    $page['description'],
                    $article['overview'],
                ],
            ],
            [
                'id' => 'workflow',
                'title' => $headings[1],
                'paragraphs' => [$article['workflow']],
            ],
            [
                'id' => 'details',
                'title' => $headings[2],
                'paragraphs' => [$article['details']],
            ],
        ];
    }

    private function copy(): array
    {
        return [
            'ru' => [
                'center_label' => 'База знаний',
                'center_title' => 'Справочный центр',
                'center_intro' => 'Руководство по работе с ContactCore и техническая документация платформы.',
                'search_placeholder' => 'Найти раздел',
                'search_empty' => 'Разделы не найдены',
                'navigation_label' => 'Разделы справки',
                'on_this_page' => 'На этой странице',
                'article_label' => 'Руководство',
                'technical_label' => 'Техническая документация',
                'updated_label' => 'Документация ContactCore',
                'previous_label' => 'Предыдущий раздел',
                'next_label' => 'Следующий раздел',
                'open_navigation' => 'Открыть разделы',
                'close_navigation' => 'Закрыть разделы',
                'navigation' => [
                    ['id' => 'start', 'title' => 'Начало', 'description' => 'Знакомство с системой, навигацией и основным рабочим процессом.', 'icon' => 'ph-house-line'],
                    ['id' => 'clients', 'title' => 'Клиенты', 'description' => 'Работа с организациями, их реквизитами и связанными данными.', 'icon' => 'ph-buildings'],
                    ['id' => 'contacts', 'title' => 'Контакты', 'description' => 'Работа с людьми, контактными данными и связями с клиентами.', 'icon' => 'ph-address-book'],
                    ['id' => 'sectors-tags', 'title' => 'Сектора и теги', 'description' => 'Классификация записей с помощью отраслей и гибких меток.', 'icon' => 'ph-tag'],
                    ['id' => 'custom-fields', 'title' => 'Пользовательские поля', 'description' => 'Расширение карточек клиентов и контактов собственными полями.', 'icon' => 'ph-sliders-horizontal'],
                    ['id' => 'import-export', 'title' => 'Импорт и экспорт', 'description' => 'Загрузка, проверка и выгрузка данных в CSV и XLSX.', 'icon' => 'ph-arrows-down-up'],
                    ['id' => 'ai-tools', 'title' => 'ИИ-инструменты', 'description' => 'Автоматическое определение компаний и контроль результатов ИИ.', 'icon' => 'ph-sparkle'],
                    ['id' => 'users-settings', 'title' => 'Пользователи и настройки', 'description' => 'Учётные записи, параметры интерфейса и управление системой.', 'icon' => 'ph-users-three'],
                    ['id' => 'api', 'title' => 'API', 'description' => 'Подключение внешних систем к данным и операциям ContactCore.', 'icon' => 'ph-plugs-connected'],
                    ['id' => 'technical', 'title' => 'Техническая документация', 'description' => 'Архитектура, безопасность, конфигурация и эксплуатация платформы.', 'icon' => 'ph-code'],
                ],
                'article' => [
                    'overview' => 'В статье последовательно объясняется назначение раздела и место его данных в общей структуре CRM.',
                    'workflow' => 'Работа рассматривается как связный процесс: от открытия раздела и поиска нужной записи до сохранения результата и проверки связанных данных.',
                    'details' => 'Отдельное внимание уделяется ограничениям, связанным возможностям и ситуациям, в которых выбранный инструмент используется наиболее эффективно.',
                    'default_headings' => ['О разделе', 'Основной порядок работы', 'Важные особенности'],
                    'sections' => [
                        'start' => [
                            [
                                'id' => 'purpose',
                                'title' => 'Для чего нужна ContactCore',
                                'paragraphs' => [
                                    'ContactCore — это система для централизованной работы с клиентами и контактами. Она помогает хранить сведения о компаниях и людях в одном месте, связывать их между собой и поддерживать данные в понятном состоянии. Вместо нескольких таблиц, разрозненных файлов и записей у разных сотрудников команда получает общую базу, в которой можно быстро найти нужную информацию и понять контекст отношений с клиентом.',
                                    'Система рассчитана не только на хранение адресов и телефонов. Её основная задача — показать структуру данных: с какой организацией связан человек, к какому сектору относится компания, какие теги назначены записи, какие дополнительные сведения были собраны и когда информация менялась. Благодаря этому карточка клиента или контакта становится рабочей точкой, а не просто строкой в справочнике.',
                                    'ContactCore подходит для повседневной работы с базой: добавления новых записей, уточнения существующих данных, поиска нужных людей и компаний, подготовки выборок, обмена информацией с другими системами и постепенного улучшения качества данных.',
                                ],
                            ],
                            [
                                'id' => 'contents',
                                'title' => 'Что входит в систему',
                                'paragraphs' => [
                                    'Основу ContactCore составляют два связанных раздела: клиенты и контакты. Клиентом в системе считается организация или компания. В его карточке хранятся название, юридические и адресные данные, сайт, сектор деятельности, заметки и другие сведения. Контакт — это конкретный человек с именем, электронной почтой, телефоном и информацией о компании. Один клиент может быть связан с несколькими контактами, а один контакт — сразу с несколькими клиентами.',
                                    'Для классификации данных используются сектора и теги. Сектор описывает основное направление деятельности клиента, например технологии, образование или туризм. Теги работают гибче: ими можно отмечать статус, тип отношений, принадлежность к проекту или любую другую характеристику, важную для команды. Пользовательские поля позволяют расширять стандартные карточки без изменения кода и хранить именно те данные, которые нужны конкретному бизнесу.',
                                    'В систему также входят инструменты импорта и экспорта. С их помощью можно загружать существующие базы из CSV или XLSX, сопоставлять колонки с полями CRM, контролировать ошибки и выгружать нужные данные обратно. ИИ-инструменты помогают обрабатывать отдельные задачи по обогащению данных, а REST API позволяет подключать внешние сайты, формы и внутренние сервисы.',
                                    'Отдельные разделы предназначены для управления пользователями, настройками и интеграциями. В них настраиваются учётные записи, параметры интерфейса, API-ключи и другие служебные возможности. Техническая документация описывает внутреннее устройство платформы, конфигурацию и развёртывание.',
                                ],
                            ],
                            [
                                'id' => 'capabilities',
                                'title' => 'Что позволяет делать ContactCore',
                                'paragraphs' => [
                                    'В обычной работе ContactCore позволяет создать карточку компании, добавить связанных с ней людей и постепенно дополнить записи всей доступной информацией. Данные можно редактировать по мере их уточнения, связывать с новыми клиентами, распределять по секторам и отмечать тегами. Для контактов и клиентов поддерживаются отдельные пользовательские поля, поэтому структура карточек может развиваться вместе с задачами команды.',
                                    'Списки контактов и клиентов поддерживают поиск, сортировку и фильтрацию. Можно находить записи по основным данным, связанным сущностям, датам и пользовательским полям. Массовые действия помогают обрабатывать сразу несколько записей: например, назначить общий тег, добавить связь или удалить выбранные элементы. Глобальный поиск в верхней панели используется, когда нужно быстро перейти к конкретному человеку или организации из любого раздела CRM.',
                                    'Для переноса больших объёмов информации не требуется создавать каждую запись вручную. Импорт загружает данные пакетно и сохраняет результат обработки каждой строки, а экспорт формирует выборку с нужным набором полей. Через API те же основные сущности могут использовать внешние приложения. Это позволяет применять ContactCore и как самостоятельную CRM, и как центральный источник данных для других инструментов.',
                                    'Главная ценность системы проявляется тогда, когда данные ведутся последовательно. Единые правила именования, аккуратные связи, понятные теги и заполненные карточки позволяют быстрее находить информацию, избегать дубликатов и не терять важный контекст при совместной работе.',
                                ],
                            ],
                            [
                                'id' => 'data-model',
                                'title' => 'Как связаны основные данные',
                                'paragraphs' => [
                                    'При работе с системой важно различать клиента и контакт. Клиент отвечает на вопрос «с какой организацией мы работаем», а контакт — «с каким человеком мы общаемся». Эти записи могут существовать самостоятельно, но наиболее полную картину дают именно связи между ними. Открыв карточку клиента, можно увидеть относящихся к нему людей; из карточки контакта можно перейти к связанным организациям.',
                                    'Сектор назначается клиенту и описывает его отрасль. Теги могут назначаться как клиентам, так и контактам и используются для более свободной классификации. Пользовательские поля также создаются отдельно для клиентов и контактов: поле, добавленное для организаций, не появляется автоматически в карточках людей. Такое разделение помогает сохранять структуру базы и не смешивать данные разного назначения.',
                                    'Если информация приходит из внешнего файла или интеграции, она в итоге попадает в те же сущности и связи. Поэтому перед импортом или подключением API полезно заранее определить, какие данные являются клиентами, какие — контактами, какие значения должны стать тегами, а какие лучше хранить в пользовательских полях.',
                                ],
                            ],
                            [
                                'id' => 'using-help',
                                'title' => 'Как пользоваться справкой',
                                'paragraphs' => [
                                    'Справочный центр построен по тем же разделам, что и сама CRM. Навигация слева позволяет перейти к нужной теме: клиентам, контактам, классификации, пользовательским полям, обмену данными, ИИ-инструментам, настройкам или API. На небольшом экране список разделов открывается отдельной кнопкой над статьёй.',
                                    'Поле поиска в верхней части страницы помогает найти раздел по его названию или краткому описанию. Это поиск по структуре справки, а не по всему тексту статей. Внутри каждого раздела материал разбит на смысловые подразделы, поэтому статью можно читать последовательно или сразу перейти к интересующему вопросу. Внизу страницы находятся ссылки на предыдущую и следующую тему.',
                                    'Пользовательские разделы объясняют назначение функций и обычный порядок работы с ними. Раздел API предназначен для настройки интеграций и содержит описание запросов и ответов. Техническая документация рассматривает архитектуру приложения, базу данных, безопасность, конфигурацию и развёртывание. Если задача связана с ежедневной работой в CRM, лучше начать с соответствующего пользовательского раздела; если вопрос касается устройства или обслуживания платформы — перейти к технической документации.',
                                    'Язык справки следует выбранному языку интерфейса. Содержание будет дополняться вместе с развитием ContactCore, поэтому справочный центр можно использовать как основную точку для знакомства с возможностями системы и уточнения рабочего процесса.',
                                ],
                            ],
                        ],
                        'clients' => [
                            [
                                'id' => 'client-meaning',
                                'title' => 'Кого система считает клиентом',
                                'paragraphs' => [
                                    'Клиенты в ContactCore — это компании и организации, которые являются клиентами нашей организации. Мы оказываем им услуги, сопровождаем их сайты или работаем с ними в рамках другого действующего сотрудничества. Поэтому раздел «Клиенты» описывает не людей, оставивших заявку, а сами компании, для которых эти заявки собираются.',
                                    'Люди, которые отправляют формы на сайтах наших клиентов, сохраняются в другом разделе — «Контакты». Такой контакт представляет потенциального или существующего клиента обслуживаемой компании. После поступления заявки контакт связывается с соответствующей компанией-клиентом, благодаря чему всегда можно понять, с какого проекта или сайта он пришёл.',
                                    'Это различие важно соблюдать при ручном вводе, импорте и настройке интеграций: организация создаётся как клиент, а человек из заявки — как контакт, связанный с этим клиентом. Если смешивать эти сущности, отчёты, фильтры и списки связанных контактов перестают отражать реальную структуру базы.',
                                ],
                            ],
                            [
                                'id' => 'default-fields',
                                'title' => 'Поля карточки клиента',
                                'paragraphs' => [
                                    'Единственное обязательное поле стандартной карточки — коммерческое название. Это привычное название компании, под которым её удобно искать в CRM. Юридическое название заполняется отдельно и может отличаться от коммерческого. Поле «ИНН / CIF» предназначено для налогового или регистрационного номера организации.',
                                    'Сектор показывает основное направление деятельности клиента. Теги используются для более свободной классификации: ими можно обозначить тип обслуживания, внутренний статус, проект или любую другую характеристику. В отличие от сектора, тег не обязан описывать отрасль, и одной компании можно назначить несколько тегов.',
                                    'Адресная часть карточки включает адрес, почтовый индекс, город, провинцию или регион и страну. Поле сайта должно содержать адрес основного сайта клиента. В заметках можно хранить важную рабочую информацию, для которой нет отдельного структурированного поля. Если стандартного набора недостаточно, карточка дополняется пользовательскими полями, созданными специально для клиентов.',
                                    'При создании карточки стоит сначала проверить, нет ли компании в базе. Коммерческое название обязательно, но система не запрещает создать несколько клиентов с одинаковым названием, поэтому дубликаты необходимо контролировать организационно.',
                                ],
                            ],
                            [
                                'id' => 'active-status',
                                'title' => 'Что означает «Активный клиент»',
                                'paragraphs' => [
                                    'Активный клиент — это компания, с которой наша организация сотрудничает в настоящее время. Для нового клиента этот признак включён по умолчанию. Пока договорённости действуют и клиент получает наши услуги, статус следует оставлять активным.',
                                    'Если сотрудничество завершилось или временно прекращено, признак можно выключить. Карточка при этом не удаляется: сохраняются сведения о компании и связи с ранее полученными контактами. Это позволяет отделить текущих клиентов от бывших, не теряя историю и накопленные данные.',
                                    'Статус используется в списке и фильтрах клиентов. При его изменении система также фиксирует дату изменения. Не следует использовать признак активности как оценку качества клиента или отдельной заявки — он описывает именно текущее состояние сотрудничества между нашей организацией и компанией.',
                                ],
                            ],
                            [
                                'id' => 'api-status',
                                'title' => 'Что означает подключение к Web / API',
                                'paragraphs' => [
                                    'Признак «Подключён к Web / API» означает, что сайт клиента интегрирован с ContactCore. После такого подключения заявки, отправленные через формы на сайте, передаются на платформу по API и создаются здесь как контакты. Созданный контакт должен быть связан с клиентом, с сайта которого поступила заявка.',
                                    'Этот признак является отметкой о состоянии интеграции. Само включение переключателя в карточке не подключает сайт, не создаёт API-ключ и не настраивает отправку форм. Его следует включать только после того, как интеграция действительно настроена и проверено поступление заявок. При изменении признака система сохраняет дату изменения статуса.',
                                    'Для корректной связи входящих контактов название или идентификатор клиента в интеграции должны использоваться последовательно. API может создавать недостающие сущности, поэтому ошибка или другое написание названия способно привести к появлению отдельной карточки вместо связи с уже существующим клиентом. После запуска интеграции полезно проверить первые поступившие заявки вручную.',
                                ],
                            ],
                            [
                                'id' => 'related-contacts',
                                'title' => 'Связанные контакты',
                                'paragraphs' => [
                                    'В нижней части карточки клиента отображаются связанные контакты. В первую очередь это люди, которые оставили заявки на сайте этой компании и были добавлены вручную, через импорт или по API. Из списка можно перейти в карточку конкретного контакта и посмотреть его данные.',
                                    'Один клиент может иметь любое количество контактов. Один и тот же человек при необходимости может быть связан с несколькими клиентами, например если он обращался по разным проектам. Такая связь не создаёт копию контакта: в базе остаётся одна запись человека, доступная из карточек всех связанных компаний.',
                                    'Удаление связи и удаление самого контакта — разные действия. Если компания больше не связана с человеком, связь можно изменить в карточке контакта. При удалении клиента его контакты не удаляются автоматически, но связь с удалённой компанией исчезает.',
                                ],
                            ],
                            [
                                'id' => 'client-list',
                                'title' => 'Работа со списком клиентов',
                                'paragraphs' => [
                                    'В общем списке клиентов можно искать компании по коммерческому и юридическому названию, сортировать записи и применять фильтры. Доступны фильтры по сектору, тегам, активности, подключению к Web/API, сайту, местоположению, дате создания и пользовательским полям. Активные фильтры сохраняются в адресе страницы, поэтому подготовленную выборку можно открыть повторно или передать другому пользователю.',
                                    'Массовые действия позволяют назначать или снимать теги сразу у нескольких клиентов. Удаление клиента является окончательным действием для самой карточки, поэтому перед ним нужно убедиться, что компания действительно больше не нужна в базе. Если сотрудничество просто завершилось, обычно правильнее отключить статус «Активный клиент», а не удалять запись.',
                                    'Карточки клиентов следует поддерживать в актуальном состоянии: использовать единое написание названий, вовремя менять признаки активности и подключения, указывать правильный сайт и проверять связи с поступающими контактами. От качества этих данных зависит, насколько точно ContactCore сможет разделять заявки между клиентскими проектами.',
                                ],
                            ],
                        ],
                    ],
                    'headings' => [
                        'start' => ['О системе', 'Как устроена работа', 'Как пользоваться справкой'],
                        'clients' => ['Что такое клиент', 'Работа с карточкой клиента', 'Связанные данные'],
                        'contacts' => ['Что такое контакт', 'Работа с карточкой контакта', 'Связи и классификация'],
                        'sectors-tags' => ['Принципы классификации', 'Сектора', 'Теги'],
                        'custom-fields' => ['Назначение полей', 'Создание и настройка', 'Хранение и использование значений'],
                        'import-export' => ['Обмен данными', 'Импорт', 'Экспорт'],
                        'ai-tools' => ['Назначение ИИ-инструментов', 'Подготовка и обработка данных', 'Проверка результата'],
                        'users-settings' => ['Учётные записи', 'Настройки пользователя', 'Администрирование'],
                        'api' => ['Назначение API', 'Аутентификация и запросы', 'Ресурсы и ответы'],
                    ],
                ],
                'technical_sections' => [
                    ['id' => 'platform', 'title' => 'Обзор платформы', 'description' => 'Назначение системы, используемые технологии и ключевые архитектурные решения.'],
                    ['id' => 'architecture', 'title' => 'Архитектура приложения', 'description' => 'Слои приложения, структура каталогов, маршрутизация и жизненный цикл HTTP-запроса.'],
                    ['id' => 'database', 'title' => 'База данных', 'description' => 'Модель данных, связи между сущностями, индексы и правила изменения схемы.'],
                    ['id' => 'security', 'title' => 'Аутентификация и безопасность', 'description' => 'Сессии, права доступа, CSRF, API-ключи и защита конфиденциальных данных.'],
                    ['id' => 'configuration', 'title' => 'Конфигурация и интеграции', 'description' => 'Настройка базы данных, почты, Gemini, API и фоновых задач.'],
                    ['id' => 'deployment', 'title' => 'Развёртывание и обслуживание', 'description' => 'Требования окружения, установка, журналы, диагностика и регулярное обслуживание.'],
                ],
            ],
            'en' => [
                'center_label' => 'Knowledge base',
                'center_title' => 'Help Center',
                'center_intro' => 'Guidance for working with ContactCore and technical platform documentation.',
                'search_placeholder' => 'Find a section',
                'search_empty' => 'No sections found',
                'navigation_label' => 'Help sections',
                'on_this_page' => 'On this page',
                'article_label' => 'Guide',
                'technical_label' => 'Technical documentation',
                'updated_label' => 'ContactCore documentation',
                'previous_label' => 'Previous section',
                'next_label' => 'Next section',
                'open_navigation' => 'Open sections',
                'close_navigation' => 'Close sections',
                'navigation' => [
                    ['id' => 'start', 'title' => 'Getting started', 'description' => 'An introduction to the system, navigation and core workflow.', 'icon' => 'ph-house-line'],
                    ['id' => 'clients', 'title' => 'Clients', 'description' => 'Organizations, company details and related records.', 'icon' => 'ph-buildings'],
                    ['id' => 'contacts', 'title' => 'Contacts', 'description' => 'People, contact details and their links to clients.', 'icon' => 'ph-address-book'],
                    ['id' => 'sectors-tags', 'title' => 'Sectors and tags', 'description' => 'Classifying records with industries and flexible labels.', 'icon' => 'ph-tag'],
                    ['id' => 'custom-fields', 'title' => 'Custom fields', 'description' => 'Extending client and contact records with your own fields.', 'icon' => 'ph-sliders-horizontal'],
                    ['id' => 'import-export', 'title' => 'Import and export', 'description' => 'Loading, validating and exporting CSV and XLSX data.', 'icon' => 'ph-arrows-down-up'],
                    ['id' => 'ai-tools', 'title' => 'AI tools', 'description' => 'Automated company discovery and review of AI results.', 'icon' => 'ph-sparkle'],
                    ['id' => 'users-settings', 'title' => 'Users and settings', 'description' => 'Accounts, interface preferences and system management.', 'icon' => 'ph-users-three'],
                    ['id' => 'api', 'title' => 'API', 'description' => 'Connecting external systems to ContactCore data and operations.', 'icon' => 'ph-plugs-connected'],
                    ['id' => 'technical', 'title' => 'Technical documentation', 'description' => 'Architecture, security, configuration and platform operations.', 'icon' => 'ph-code'],
                ],
                'article' => [
                    'overview' => 'The article explains the purpose of this area and how its data fits into the wider CRM.',
                    'workflow' => 'The workflow is presented from opening the section and finding a record through saving the result and reviewing related data.',
                    'details' => 'Additional notes cover constraints, connected features and the situations where this part of the system is most useful.',
                    'default_headings' => ['About this section', 'Core workflow', 'Important details'],
                    'headings' => [],
                ],
                'technical_sections' => [
                    ['id' => 'platform', 'title' => 'Platform overview', 'description' => 'System purpose, technology stack and key architectural decisions.'],
                    ['id' => 'architecture', 'title' => 'Application architecture', 'description' => 'Application layers, directory structure, routing and the HTTP request lifecycle.'],
                    ['id' => 'database', 'title' => 'Database', 'description' => 'Data model, entity relationships, indexes and schema change rules.'],
                    ['id' => 'security', 'title' => 'Authentication and security', 'description' => 'Sessions, access control, CSRF, API keys and confidential data protection.'],
                    ['id' => 'configuration', 'title' => 'Configuration and integrations', 'description' => 'Database, mail, Gemini, API and scheduled task configuration.'],
                    ['id' => 'deployment', 'title' => 'Deployment and operations', 'description' => 'Environment requirements, installation, logs, diagnostics and maintenance.'],
                ],
            ],
            'es' => [
                'center_label' => 'Base de conocimiento',
                'center_title' => 'Centro de ayuda',
                'center_intro' => 'Guía de uso de ContactCore y documentación técnica de la plataforma.',
                'search_placeholder' => 'Buscar una sección',
                'search_empty' => 'No se encontraron secciones',
                'navigation_label' => 'Secciones de ayuda',
                'on_this_page' => 'En esta página',
                'article_label' => 'Guía',
                'technical_label' => 'Documentación técnica',
                'updated_label' => 'Documentación de ContactCore',
                'previous_label' => 'Sección anterior',
                'next_label' => 'Sección siguiente',
                'open_navigation' => 'Abrir secciones',
                'close_navigation' => 'Cerrar secciones',
                'navigation' => [
                    ['id' => 'start', 'title' => 'Primeros pasos', 'description' => 'Introducción al sistema, la navegación y el flujo de trabajo principal.', 'icon' => 'ph-house-line'],
                    ['id' => 'clients', 'title' => 'Clientes', 'description' => 'Organizaciones, datos de empresa y registros relacionados.', 'icon' => 'ph-buildings'],
                    ['id' => 'contacts', 'title' => 'Contactos', 'description' => 'Personas, datos de contacto y sus relaciones con clientes.', 'icon' => 'ph-address-book'],
                    ['id' => 'sectors-tags', 'title' => 'Sectores y tags', 'description' => 'Clasificación mediante industrias y etiquetas flexibles.', 'icon' => 'ph-tag'],
                    ['id' => 'custom-fields', 'title' => 'Campos personalizados', 'description' => 'Ampliación de clientes y contactos con campos propios.', 'icon' => 'ph-sliders-horizontal'],
                    ['id' => 'import-export', 'title' => 'Importación y exportación', 'description' => 'Carga, validación y exportación de datos CSV y XLSX.', 'icon' => 'ph-arrows-down-up'],
                    ['id' => 'ai-tools', 'title' => 'Herramientas de IA', 'description' => 'Detección automática de empresas y revisión de resultados.', 'icon' => 'ph-sparkle'],
                    ['id' => 'users-settings', 'title' => 'Usuarios y ajustes', 'description' => 'Cuentas, preferencias de interfaz y gestión del sistema.', 'icon' => 'ph-users-three'],
                    ['id' => 'api', 'title' => 'API', 'description' => 'Conexión de sistemas externos con los datos y operaciones de ContactCore.', 'icon' => 'ph-plugs-connected'],
                    ['id' => 'technical', 'title' => 'Documentación técnica', 'description' => 'Arquitectura, seguridad, configuración y operación de la plataforma.', 'icon' => 'ph-code'],
                ],
                'article' => [
                    'overview' => 'El artículo explica la finalidad del área y cómo encajan sus datos en el conjunto del CRM.',
                    'workflow' => 'El flujo se presenta desde la apertura de la sección y la búsqueda de un registro hasta el guardado y la revisión de datos relacionados.',
                    'details' => 'Las notas adicionales cubren limitaciones, funciones relacionadas y los casos en que esta parte del sistema resulta más útil.',
                    'default_headings' => ['Acerca de esta sección', 'Flujo de trabajo', 'Detalles importantes'],
                    'headings' => [],
                ],
                'technical_sections' => [
                    ['id' => 'platform', 'title' => 'Resumen de la plataforma', 'description' => 'Propósito del sistema, tecnologías y decisiones arquitectónicas principales.'],
                    ['id' => 'architecture', 'title' => 'Arquitectura de la aplicación', 'description' => 'Capas, estructura de directorios, rutas y ciclo de una petición HTTP.'],
                    ['id' => 'database', 'title' => 'Base de datos', 'description' => 'Modelo de datos, relaciones, índices y reglas de cambio del esquema.'],
                    ['id' => 'security', 'title' => 'Autenticación y seguridad', 'description' => 'Sesiones, control de acceso, CSRF, claves API y protección de datos.'],
                    ['id' => 'configuration', 'title' => 'Configuración e integraciones', 'description' => 'Configuración de base de datos, correo, Gemini, API y tareas programadas.'],
                    ['id' => 'deployment', 'title' => 'Despliegue y operación', 'description' => 'Requisitos, instalación, registros, diagnóstico y mantenimiento.'],
                ],
            ],
        ];
    }
}
