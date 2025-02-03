# API розкладу

Це API для керування розкладом занять. Воно дозволяє отримувати та оновлювати дані про розклад для різних тижнів і днів. Розклад зберігається у JSON-файлі `all.json`, і всі зміни до нього виконуються безпосередньо в цьому файлі.

Реалізована реєстрація та авторизація користувачів за допомогою **JWT**. Для зберігання даних використано **SQL Server**, а взаємодія з базою даних здійснюється через **Doctrine ORM**. Авторизація реалізована з використанням пакету **LexikJWTAuthenticationBundle**.

# Документація:
Повну документацію можна знайти за посиланням: <br>
https://documenter.getpostman.com/view/31544367/2sAYX3s3qV <br>
_**```У блоках Example Request по праву сторону можна обирати різні варіанти відповідей на запити залежно від їх вмісту!```**_
# Демонстраційне відео:
Також є відео з демонстрацією взаємодії html сторінки з API <br>
https://drive.google.com/file/d/115nz5ew1w0xJDf4X_aaiQ1WDezFNgFzD/view

# Запуск проєкту

Цей проєкт є базовою Symfony установкою, яка використовує Composer для керування залежностями. Щоб запустити проєкт, виконайте наступні кроки:

## Потрібні умови:

1. **PHP** версії 8.2 або новішої.
2. **Composer** для керування залежностями.
3. **Symfony** встановлений у вашій системі.
4. **OpenSSL** встановлений у вашій системі.
5. **SQL Server** встановлений у вашій системі, розширення для PHP і їх підключення у PHP.ini..

## Кроки для запуску:

### Крок 1: Клонувати репозиторій
Клонуйте репозиторій на вашу локальну машину за допомогою Git:

```bash
git clone https://github.com/ipzk241-zhdv/api_practice.git
```

### Крок 2: Встановити залежності
```bash
cd ваш-проект
composer install
```

### Крок 3: Генерація пари ключів JWT
Після того як ви встановите плагін LexikJWTAuthenticationBundle, ви можете скористатися командою для генерації пари приватного і публічного ключа.
```bash
php bin/console lexik:jwt:generate-keypair
```
### Крок 4: Налаштування підключення до SQL Server
У файлі `.env.local` додати налаштування підключення до бази даних:
```ini
DATABASE_URL="sqlsrv://<username>:<password>@<host>:<port>/<database_name>"
```

### Крок 5: Встановлення пакета для підтримки SQL Server
Оскільки використовується SQL Server, потрібно встановити спеціальний драйвер для роботи з ним через Doctrine. Це можна зробити за допомогою Composer:
```bash
composer require doctrine/doctrine-bundle doctrine/orm symfony/orm-pack
composer require doctrine/dbal --with-platform=sqlsrv
```

### Крок 6: Налаштування Doctrine в Symfony
Перевірте файл `config/packages/doctrine.yaml`, щоб переконатися, що він налаштований для роботи з SQL Server:
```yaml
doctrine:
    dbal:
        driver: 'pdo_sqlsrv'
        url: '%env(DATABASE_URL)%'
        server_version: '13.0' # Вказуйте вашу версію SQL Server
        charset: UTF8
    orm:
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore
        auto_mapping: true
```

### Створення та запуск першої міграції
Після налаштування підключення до бази даних можна створити міграції для вашої моделі. Для цього потрібно виконати команду:
```bash
php bin/console make:migration
```

Для застосування створених міграцій до бази даних, виконайте команду:
```bash
php bin/console doctrine:migrations:migrate
```

Ця команда повинна автоматично створити таблицю Users в SQL Server.
### Крок 7: Запуск сервера Symfony
```bash
symfony server:start
```

### Опціонально
HTML сторінку можна просто відкрити у **браузері**, в такому випадку вас цікавить файл `index.html` 
Можна також запустити і через **Live Server** у **Visual Studio Code**

## Структура збереження даних в `all.json`:
    [
    {
        "name": "Факультет гірничої справи, природокористування та будівництва",
        "shortname": "ФГСПБ",
        "course": [
            {
                "name": "1 курс",
                "groups": [
                    {
                        "group_name": "АГР-1",
                        "link": "\/schedule\/group\/АГР-1?new",
                        "schedule": {
                            "firstweek": {
                                "monday": [
                                    {
                                        "time": "8:30-9:50",
                                        "discipline": "Вступ до фаху",
                                        "teacher": "Ключевич Михайло Михайлович",
                                        "auditory": "310"
                                    },
                                    {
                                        "time": "10:00-11:20",
                                        "discipline": "Фізичне виховання",
                                        "teacher": "Гресь Марина Ярославівна",
                                        "auditory": "Спортзал 4"
                                    }
                                ],
                                "tuesday": [...],
                                ...,
                                "saturday": [...],
                            },
                            "secondweek": {
                                "monday": [...],
                                ...,
                                "saturday": [],
                            }
                        }
                    },
                    {
                        "group_name": "...",
                        ...,
                    },
                    ..., // безліч груп для факультету
            },
            ..., безліч курсів для факультету
     },
     {
        "name": "Факультет гірничої справи, природокористування та будівництва",
        ...,
     },
     ..., // безліч факультетів
