# Crypto Balance Management System

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php)](https://www.php.net)
[![Filament](https://img.shields.io/badge/Filament-4.x-FEAE4D?style=for-the-badge)](https://filamentphp.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=for-the-badge&logo=docker)](https://www.docker.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql)](https://www.postgresql.org)

> **Система учета крипто-балансов с акцентом на безопасность транзакций и защиту от Double Spending.**
> Разработано в качестве тестового задания для FinTech компании.

## 🎥 Видео-презентация
Ссылка на видео с техническими комментариями по выбранным решениям, архитектуре и реализации:
**[YouTube: Разбор проекта Crypto Balance](https://youtu.be/-Yj6Pew_4mU)**

---

## 📖 Описание проекта

Данный репозиторий содержит мини-приложение на стеке **Laravel 12 + Filament 4**, реализующее логику управления крипто-кошельками. Основной акцент сделан на финансовую безопасность и корректную обработку транзакций в высоконагруженной среде.

### Ключевые особенности:
*   **Защита от Double Spending:** Использование транзакций базы данных (`DB::transaction`) и блокировок `lockForUpdate` (Pessimistic Locking) для предотвращения состояния гонки (Race Condition).
*   **Асинхронная обработка:** Финансовые операции вынесены в Laravel Jobs, управляемые Supervisor, что гарантирует надежность и отказоустойчивость.
*   **Высокая точность:** Использование PostgreSQL с типом данных `decimal(36,18)` для работы с крипто-суммами без потери точности.
*   **Полный аудит:** Логирование всех изменений баланса в таблице `transactions` с отслеживанием статусов (`pending`, `completed`, `failed`).
*   **Админ-панель:** Современный интерфейс на базе Filament PHP v4.

---

## 🛠 Технологический стек

| Компонент | Технология |
| :--- | :--- |
| **Backend** | Laravel 12 (PHP 8.4) |
| **Frontend / Admin** | Filament PHP 4.x |
| **Database** | PostgreSQL (Decimal precision 36,18) |
| **Queue Driver** | Database (для атомарности операций) |
| **Infrastructure** | Docker (Nginx, PHP-FPM, PostgreSQL, Supervisor) |

---

## 🚀 Установка и Запуск

Проект полностью контейнеризирован. Для запуска вам понадобится установленный **Docker** и **Docker Compose**.

### 1. Клонирование репозитория
```bash
git clone https://github.com/Wmag-team/crypto_bank_test_app.git
cd crypto_bank_test_app
```

### 2. Запуск контейнеров
Выполните команду в корне проекта:
```bash
docker compose up -d --build
```

### 3. Инициализация приложения
Накатите миграции и заполните базу тестовыми данными (Seed):

```bash
docker compose exec app php artisan migrate --seed
```
*(Команда создаст таблицы и добавит тестовых пользователей)*

### 4. Доступ к приложению
После успешного запуска приложение будет доступно по адресу:
**[http://localhost:8080](http://localhost:8080)**

---

## 🔐 Учетные данные (Demo Access)

В системе созданы тестовые пользователи с ролями: Администратор и обычные пользователи.

**Пароль для всех пользователей:** `password`

| Роль | Email |
| :--- | :--- |
| **Admin** | `admin@mail.com` |
| **User 1** | `email1@mail.com` |
| **User 2** | `email2@mail.com` |
| **...** | `...` |
| **User 10** | `email10@mail.com` |

---

## 📂 Структура проекта

*   `/laravel` — Исходный код приложения (Models, Services, Jobs).
*   `/docker` — Конфигурации Docker-образов и Supervisor.
*   `/nginx` — Конфигурация веб-сервера Nginx.

### Бизнес-логика
Основная логика работы с балансом вынесена в сервис `CryptoBalanceService`, что обеспечивает чистоту кода и удобство тестирования. Обработка очередей осуществляется через Supervisor, что гарантирует выполнение фоновых задач даже при высоких нагрузках.

---

## 🏷️ ХэшТеги

`#laravel` `#php` `#fintech` `#crypto` `#docker` `#filamentphp` `#postgresql` `#test-task` `#queue` `#supervisor`
