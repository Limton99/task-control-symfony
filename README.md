# Tasks Control — Первый запуск (Docker + Symfony + JWT)

Этот проект — REST API на Symfony c JWT‑авторизацией. **Все эндпоинты, кроме авторизации (и, при необходимости, регистрации), требуют токен** в заголовке `Authorization: Bearer <JWT>`.

## Предварительные требования
- Docker Desktop + Docker Compose v2
- Git, Make (необязательно)
- (Windows) Рекомендуется WSL2 для лучшей производительности

## Быстрый старт
1. **Скопируйте окружение и настройте переменные**

   Создайте файл `.env.local` в корне проекта:
   ```dotenv
   APP_ENV=dev
   APP_DEBUG=1
   JWT_PASSPHRASE="change_me"
   # Имя БД подставьте своё (по умолчанию app или task_control)
   DATABASE_URL="postgresql://symfony:secret@db:5432/task_control?serverVersion=16&charset=utf8"
   ```

2. **Поднимите контейнеры**
   ```bash
   docker compose up -d --build
   ```

3. **Установите зависимости (если не запечены в образ)**
   ```bash
   docker compose exec app composer install --no-interaction --prefer-dist
   ```

4. **Сгенерируйте ключи JWT**
   ```bash
   docker compose exec app php bin/console lexik:jwt:generate-keypair --overwrite -n
   ```

5. **Накатите миграции**
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate -n
   ```

6. **Создайте первого пользователя**

   **Вариант A (если включена регистрация):**
   ```bash
   curl -X POST http://localhost:8000/api/user/register \
     -H "Content-Type: application/json" \
     -d '{"name":"Admin","login":"admin","password":"admin123","passwordConfirm":"admin123"}'
   ```
   В ответ придёт `token`.

   **Вариант B (вручную через SQL):**
   1) Сгенерируйте хеш пароля:
   ```bash
   docker compose exec app php bin/console security:hash-password
   # Class: App\\Entity\\User
   # Plain password: admin123
   # Скопируйте "Hashed password: ..."
   ```
   2) Вставьте пользователя в БД:
   ```bash
   docker compose exec -T db psql -U symfony -d task_control -c \
   "INSERT INTO app_user (name, login, roles, password) VALUES ('Admin','admin','[\"ROLE_ADMIN\"]','<ВСТАВЬТЕ_ХЕШ>');"
   ```

## Авторизация и доступ к API
- **Получить токен (логин):**
  ```bash
  curl -X POST http://localhost:8000/api/login \
    -H "Content-Type: application/json" \
    -d '{"login":"admin","password":"admin123"}'
  ```
  Ответ:
  ```json
  { "token": "<JWT>" }
  ```

- **Вызывать защищённые эндпоинты:**
  ```bash
  curl http://localhost:8000/api/tasks \
    -H "Authorization: Bearer <JWT>"
  ```

> Без корректного JWT большинство маршрутов вернут **401 Unauthenticated**. Публичными обычно являются только `POST /api/login` и (опционально) `POST /api/user/register`.

## Полезные команды
```bash
# Логи
docker compose logs -f web
docker compose logs -f app
docker compose logs -f db

# Проверка состояния
docker compose ps

# Пересобрать только PHP
docker compose up -d --no-deps --build app

# Консоль Symfony
docker compose exec app php bin/console

# Открыть psql
docker compose exec -T db psql -U symfony -d task_control -c "\dt"
```

## Частые проблемы
- **401 Unauthenticated:** не передан/просрочен/битый JWT → заново получить токен через `/api/login`.
- **500 при регистрации/логине:** проверьте миграции и схемы (`doctrine:schema:validate`), что таблица пользователей существует (`app_user`).
- **Конфликт порта 5432 на хосте:** поменяйте маппинг у `db` на `55432:5432` и перезапустите контейнеры.
- **Медленные ответы на Windows:** храните проект в WSL2 или вынесите `vendor`/`var` в именованные volumes.

## Конфигурация безопасности (кратко)
- Публичные маршруты:
  - `POST /api/login`
  - `POST /api/user/register` (если регистрация разрешена)
- Остальные `^/api/*` — **только с JWT**.

## URL
- API: http://localhost:8000
- Авторизация: `POST /api/user/login`
- Регистрация (опц.): `POST /api/user/register`
- Пример защищённого ресурса: `GET /api/task`

---
Если что-то не заводится — смело смотрите логи `app` и `web`, а также `var/log/dev.log` внутри контейнера:  
```bash
docker compose exec app sh -lc "tail -n 200 var/log/dev.log"
```

