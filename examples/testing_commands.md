# Команды для тестирования системы

## Запуск тестов

### Все тесты
```bash
docker compose exec laravel.test php artisan test
```

### Конкретные тесты
```bash
# API тесты
docker compose exec laravel.test php artisan test --filter TaskApiTest

# Unit тесты сервисов
docker compose exec laravel.test php artisan test --filter TranscriptionServiceTest

# Тесты с покрытием
docker compose exec laravel.test php artisan test --coverage
```

## Команды для разработки

### Миграции
```bash
# Запуск миграций
docker compose exec laravel.test php artisan migrate

# Откат миграций
docker compose exec laravel.test php artisan migrate:rollback

# Пересоздание БД
docker compose exec laravel.test php artisan migrate:fresh
```

### Очереди
```bash
# Запуск обработки очередей
docker compose exec laravel.test php artisan queue:work

# Мониторинг очередей
docker compose exec laravel.test php artisan queue:monitor

# Очистка очередей
docker compose exec laravel.test php artisan queue:clear
```

### Планировщик
```bash
# Запуск планировщика (для разработки)
docker compose exec laravel.test php artisan schedule:work

# Список запланированных задач
docker compose exec laravel.test php artisan schedule:list

# Тестирование конкретной команды
docker compose exec laravel.test php artisan schedule:test tasks:check-status
```

### Проверка статуса задач
```bash
# Проверка статуса (как в планировщике)
docker compose exec laravel.test php artisan tasks:check-status

# С ограничением количества
docker compose exec laravel.test php artisan tasks:check-status --limit=5

# Подробный вывод
docker compose exec laravel.test php artisan tasks:check-status -v
```

## Создание тестовых данных

### Через Tinker
```bash
docker compose exec laravel.test php artisan tinker
```

```php
// Создание тестовой задачи
$task = \App\Models\AudioTask::factory()->create([
    'audio_url' => 'https://example.com/test.wav',
    'status' => 'new'
]);

// Создание нескольких задач
\App\Models\AudioTask::factory()->count(5)->create();

// Создание задач в разных статусах
\App\Models\AudioTask::factory()->new()->count(2)->create();
\App\Models\AudioTask::factory()->processing()->count(1)->create();
\App\Models\AudioTask::factory()->completed()->count(3)->create();

// Запуск обработки задачи
\App\Jobs\ProcessAudioTask::dispatch($task);

// Тестирование сервисов
$service = new \App\Services\TranscriptionService();
$result = $service->processAudio($task);

$qualityService = new \App\Services\QualityAssessmentService();
$assessment = $qualityService->assessQuality($task, $result);
```

## Тестирование API через curl

### Создание задачи
```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token-123" \
  -d '{
    "audio_url": "https://example.com/test.wav",
    "parameters": {"quality": "high"}
  }'
```

### Получение списка
```bash
curl -X GET http://localhost/api/tasks \
  -H "Authorization: Bearer test-token-123"
```

### Получение конкретной задачи
```bash
curl -X GET http://localhost/api/tasks/1 \
  -H "Authorization: Bearer test-token-123"
```

## Мониторинг логов

### Laravel логи
```bash
tail -f storage/logs/laravel.log
```

### Логи очередей
```bash
# В отдельном терминале запустить queue:work с verbose
php artisan queue:work --verbose
```

### Проверка логов в БД
```bash
php artisan tinker
```

```php
// Последние логи сервисов
\App\Models\ServiceLog::latest()->limit(10)->get();

// Логи по конкретной задаче
\App\Models\ServiceLog::where('audio_task_id', 1)->get();

// Статистика по сервисам
\App\Models\ServiceLog::selectRaw('service_name, status, count(*) as count')
    ->groupBy('service_name', 'status')
    ->get();
```

## Отладка

### Проверка конфигурации
```bash
# Проверка переменных окружения
docker compose exec laravel.test php artisan env

# Проверка конфигурации
docker compose exec laravel.test php artisan config:show auth.api_tokens

# Очистка кэша
docker compose exec laravel.test php artisan config:clear
docker compose exec laravel.test php artisan cache:clear
```

### Проверка маршрутов
```bash
# Список всех маршрутов
docker compose exec laravel.test php artisan route:list

# Только API маршруты
docker compose exec laravel.test php artisan route:list --path=api
```

### Проверка middleware
```bash
# Тестирование middleware
curl -X GET http://localhost/api/tasks \
  -H "Authorization: Bearer invalid-token" \
  -v
```

## Производительность

### Профилирование
```bash
# Время выполнения команд
time docker compose exec laravel.test php artisan tasks:check-status

# Мониторинг памяти
docker compose exec laravel.test php artisan queue:work --memory=128
```

### Статистика БД
```bash
docker compose exec laravel.test php artisan tinker
```

```php
// Количество задач по статусам
\App\Models\AudioTask::selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->get();

// Среднее время обработки
\App\Models\AudioTask::whereNotNull('started_at')
    ->whereNotNull('completed_at')
    ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - started_at))) as avg_seconds')
    ->first();

// Статистика оценок качества
\App\Models\QualityAssessment::selectRaw('AVG(overall_score) as avg_score, MIN(overall_score) as min_score, MAX(overall_score) as max_score')
    ->first();
```

## Очистка данных

### Очистка тестовых данных
```bash
docker compose exec laravel.test php artisan tinker
```

```php
// Удаление всех задач
\App\Models\AudioTask::truncate();

// Удаление старых логов
\App\Models\ServiceLog::where('created_at', '<', now()->subDays(7))->delete();

// Очистка очередей
\Illuminate\Support\Facades\DB::table('jobs')->truncate();
\Illuminate\Support\Facades\DB::table('failed_jobs')->truncate();
```

## Docker команды

### Если используется Docker
```bash
# Запуск контейнеров
docker-compose up -d

# Выполнение команд в контейнере
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan test
docker-compose exec laravel.test php artisan queue:work

# Логи контейнеров
docker-compose logs -f laravel.test
docker-compose logs -f pgsql

# Подключение к БД
docker-compose exec pgsql psql -U sail -d aiston_audio
```

## Автоматизация тестирования

### Скрипт для полного тестирования
```bash
#!/bin/bash

echo "=== Запуск полного тестирования ==="

echo "1. Миграции..."
docker compose exec laravel.test php artisan migrate:fresh

echo "2. Unit тесты..."
docker compose exec laravel.test php artisan test --filter Unit

echo "3. Feature тесты..."
docker compose exec laravel.test php artisan test --filter Feature

echo "4. Создание тестовых данных..."
docker compose exec laravel.test php artisan tinker --execute="
\App\Models\AudioTask::factory()->count(5)->create();
echo 'Created 5 test tasks';
"

echo "5. Тестирование команды проверки статуса..."
docker compose exec laravel.test php artisan tasks:check-status

echo "6. Проверка API..."
curl -s -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token-123" \
  -d '{"audio_url": "https://example.com/test.wav"}' | jq .

echo "=== Тестирование завершено ==="
```