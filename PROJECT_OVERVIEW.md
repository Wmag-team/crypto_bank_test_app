# Crypto Balance Management System (Laravel 12 + Docker)

## Обзор проекта
Система учета крипто-баланса пользователя с акцентом на безопасность (защита от Double Spending) и асинхронную обработку транзакций.

## Технологический стек
- **Framework:** Laravel 12 (PHP 8.4)
- **Database:** PostgreSQL (точность decimal 36,18)
- **Admin Panel:** Filament PHP
- **Queue Driver:** Database (для обеспечения атомарности)
- **Infrastructure:** Docker (Nginx, PHP-FPM, PostgreSQL, Supervisor)

## Ключевые особенности реализации
1. **Безопасность транзакций:** Использование `DB::transaction` и `lockForUpdate` на уровне БД для предотвращения Race Condition.
2. **Асинхронность:** Обработка финансовых операций через Laravel Jobs, управляемые Supervisor.
3. **Аудит:** Полное логирование каждого изменения баланса в таблице `transactions` со статусами (pending, completed, failed).
4. **Изоляция логики:** Вынос бизнес-логики в `CryptoBalanceService`.

## Структура папок
- `/laravel` — Исходный код приложения.
- `/docker` — Конфигурации Docker и Supervisor.
- `/nginx` — Настройки веб-сервера.

## Первый запуск (Docker)
1. В корне проекта: `docker compose up -d --build`
2. Миграции (включая таблицу `jobs` для очередей):  
   `docker compose exec app php artisan migrate --force`
3. Приложение: http://localhost:8080