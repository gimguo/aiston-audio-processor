# Aiston Audio Processor

[![GitHub Repository](https://img.shields.io/badge/GitHub-gimguo%2Faiston--audio--processor-blue?logo=github)](https://github.com/gimguo/aiston-audio-processor)
[![Laravel](https://img.shields.io/badge/Laravel-12.25.0-red?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-336791?logo=postgresql)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)](https://docker.com)

Мини backend-сервис для обработки аудиозаписей разговоров диспетчеров с имитацией этапов транскрибации, диоризации и оценки качества разговора.

## Описание

Сервис принимает задачи через API с аутентификацией по токену, хранит их в базе данных PostgreSQL и периодически проверяет статус выполнения с использованием планировщика и очередей. При завершении задачи данные передаются в фейковую LLM-систему для оценки качества, а результаты сохраняются в БД.

## Основные возможности

- ✅ **API для работы с задачами** - создание и получение статуса задач
- ✅ **Аутентификация по токену** - защита всех API эндпоинтов
- ✅ **Фейковая транскрибация и диоризация** - имитация обработки аудио
- ✅ **Оценка качества через LLM** - анализ разговоров с детальными метриками
- ✅ **Планировщик задач** - автоматическая проверка статуса каждые 5 минут
- ✅ **Система очередей** - асинхронная обработка задач
- ✅ **Логирование операций** - детальные логи всех обращений к сервисам
- ✅ **Unit тесты** - покрытие основных сценариев

## Технический стек

- **Laravel 12.25.0** - основной фреймворк
- **PostgreSQL** - база данных
- **Docker** - контейнеризация
- **Laravel Queues** - система очередей
- **Laravel Scheduler** - планировщик задач

## Установка и запуск

### Требования

- Docker и Docker Compose

### Быстрый старт

1. **Клонирование репозитория**
```bash
git clone git@github.com:gimguo/aiston-audio-processor.git
cd aiston-audio-processor
```

2. **Настройка окружения**
```bash
# Копируем файл конфигурации
cp .env.example .env

# Файл .env.example уже содержит все необходимые настройки
# При необходимости можете изменить API токены или другие параметры
```

3. **Запуск через Docker**
```bash
# Запуск контейнеров
docker compose up -d

# Установка зависимостей
docker compose exec laravel.test composer install

# Генерация ключа приложения
docker compose exec laravel.test php artisan key:generate

# Запуск миграций
docker compose exec laravel.test php artisan migrate

# Запуск очередей (в отдельном терминале)
docker compose exec laravel.test php artisan queue:work

# Запуск планировщика (в отдельном терминале)
docker compose exec laravel.test php artisan schedule:work
```

**Готово!** Приложение доступно по адресу http://localhost

4. **Быстрая проверка работоспособности**
```bash
# Создание тестовой задачи
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token-123" \
  -d '{"audio_url": "https://example.com/test.wav"}'

# Проверка списка задач
curl -X GET http://localhost/api/tasks \
  -H "Authorization: Bearer test-token-123"

# Запуск тестов
docker compose exec laravel.test php artisan test
```



## API Документация

### Аутентификация

Все API запросы требуют токен аутентификации. Токен можно передать:
- В заголовке `Authorization: Bearer YOUR_TOKEN`
- В заголовке `X-API-Token: YOUR_TOKEN`

**Доступные токены по умолчанию:**
- `test-token-123`
- `demo-token-456`

### Эндпоинты

#### POST /api/tasks
Создание новой задачи на обработку аудио.

**Параметры:**
```json
{
  "audio_url": "https://example.com/audio.wav",     // URL аудиофайла (опционально)
  "audio_identifier": "AUDIO_123",                 // Идентификатор аудио (опционально)
  "parameters": {                                  // Параметры обработки (опционально)
    "quality": "high",
    "format": "wav"
  },
  "metadata": {                                    // Метаданные (опционально)
    "source": "api",
    "client_id": "uuid"
  }
}
```

**Ответ:**
```json
{
  "success": true,
  "message": "Audio processing task created successfully",
  "data": {
    "id": 1,
    "audio_url": "https://example.com/audio.wav",
    "status": "new",
    "created_at": "2025-08-20T04:43:46.000000Z"
  }
}
```

#### GET /api/tasks
Получение списка задач с пагинацией и фильтрацией.

**Параметры запроса:**
- `status` - фильтр по статусу (new, processing, completed, failed, evaluated)
- `per_page` - количество записей на страницу (по умолчанию 15, максимум 100)

**Ответ:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

#### GET /api/tasks/{id}
Получение детальной информации о задаче.

**Ответ:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "audio_url": "https://example.com/audio.wav",
    "status": "evaluated",
    "transcription_result": {
      "transcription_data": [
        {
          "speaker": "S1",
          "start": 0.0,
          "end": 5.0,
          "text": "Добрый день, как я могу помочь?",
          "confidence": 0.95
        }
      ]
    },
    "quality_assessment": {
      "overall_score": 85.5,
      "score_grade": "Good",
      "detailed_scores": {
        "politeness": 90,
        "clarity": 85,
        "responsiveness": 80
      }
    },
    "service_logs": [...]
  }
}
```

## Структура базы данных

### Таблицы

1. **audio_tasks** - основная таблица задач
2. **transcription_results** - результаты транскрибации и диоризации
3. **quality_assessments** - результаты оценки качества
4. **service_logs** - логи обращений к внешним сервисам
5. **jobs** - очередь задач Laravel

### Статусы задач

- `new` - новая задача
- `processing` - обрабатывается
- `completed` - транскрибация завершена
- `failed` - ошибка обработки
- `evaluated` - оценка качества завершена

## Фейковые сервисы

### TranscriptionService
Имитирует работу сервиса транскрибации и диоризации:
- Генерирует реалистичные диалоги между диспетчером и клиентом
- Возвращает данные в формате с временными метками и спикерами
- Имитирует различные статусы обработки

### QualityAssessmentService
Имитирует LLM-систему для оценки качества:
- Анализирует вежливость, ясность, отзывчивость
- Оценивает решение проблем и профессионализм
- Генерирует рекомендации и общую оценку

## Планировщик и очереди

### Команды

```bash
# Проверка статуса задач (запускается каждые 5 минут)
docker compose exec laravel.test php artisan tasks:check-status

# Запуск планировщика
docker compose exec laravel.test php artisan schedule:work

# Обработка очередей
docker compose exec laravel.test php artisan queue:work
```

### Настройка cron (для продакшена)

```bash
# Для Docker окружения
* * * * * cd /path-to-your-project && docker compose exec laravel.test php artisan schedule:run >> /dev/null 2>&1

# Или для локального окружения
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Тестирование

### Запуск тестов

```bash
# Все тесты
docker compose exec laravel.test php artisan test

# Конкретный тест
docker compose exec laravel.test php artisan test --filter TaskApiTest

# С покрытием
docker compose exec laravel.test php artisan test --coverage
```

### Примеры тестов

- Создание задач с валидными токенами
- Отклонение запросов без аутентификации
- Валидация входных данных
- Фильтрация и пагинация
- Получение детальной информации о задачах

## Логирование

Все операции логируются в:
- **Laravel Log** - общие события приложения
- **service_logs** таблица - детальные логи обращений к сервисам

Логи включают:
- Время выполнения операций
- Данные запросов и ответов
- Ошибки и их детали
- Статистику обработки

## Мониторинг

### Проверка состояния

```bash
# Статус очередей
docker compose exec laravel.test php artisan queue:monitor

# Список запланированных задач
docker compose exec laravel.test php artisan schedule:list

# Проверка "зависших" задач
docker compose exec laravel.test php artisan tasks:check-status --limit=50
```

### Метрики

Сервис автоматически отслеживает:
- Время обработки задач
- Количество успешных/неудачных операций
- Статистику по спикерам и продолжительности разговоров
- Распределение оценок качества

## Конфигурация

### Переменные окружения

```env
# API токены
API_TOKEN_1=your-secure-token-1
API_TOKEN_2=your-secure-token-2

# База данных
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=aiston_audio
DB_USERNAME=sail
DB_PASSWORD=password

# Очереди
QUEUE_CONNECTION=database

# Логирование
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

## Архитектура

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   API Client    │───▶│   TaskController │───▶│   AudioTask     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
                       ┌──────────────────┐    ┌─────────────────┐
                       │ ProcessAudioTask │───▶│ TranscriptionSvc│
                       │      (Job)       │    └─────────────────┘
                       └──────────────────┘              │
                                │                        ▼
                                ▼               ┌─────────────────┐
                       ┌──────────────────┐    │QualityAssessment│
                       │   Scheduler      │    │    Service      │
                       │  (5 minutes)     │    └─────────────────┘
                       └──────────────────┘
```

## Возможные улучшения

1. **Аутентификация** - интеграция с OAuth2/JWT
2. **Кэширование** - Redis для часто запрашиваемых данных
3. **Файловое хранилище** - S3 для аудиофайлов
4. **Уведомления** - webhook'и о завершении обработки
5. **Метрики** - интеграция с Prometheus/Grafana
6. **API документация** - Swagger/OpenAPI
7. **Rate limiting** - ограничение частоты запросов

## Репозиторий

**GitHub**: [gimguo/aiston-audio-processor](https://github.com/gimguo/aiston-audio-processor)

```bash
# Клонирование по SSH
git clone git@github.com:gimguo/aiston-audio-processor.git

# Клонирование по HTTPS
git clone https://github.com/gimguo/aiston-audio-processor.git
```

## Лицензия

MIT License

## Поддержка

Для вопросов и предложений:
- Создавайте [Issues](https://github.com/gimguo/aiston-audio-processor/issues) в репозитории
- Отправляйте [Pull Requests](https://github.com/gimguo/aiston-audio-processor/pulls) с улучшениями