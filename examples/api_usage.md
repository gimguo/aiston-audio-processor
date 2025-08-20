# Примеры использования API

> Репозиторий: [gimguo/aiston-audio-processor](https://github.com/gimguo/aiston-audio-processor)

## Создание задачи

### С URL аудиофайла

```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token-123" \
  -d '{
    "audio_url": "https://example.com/audio/conversation.wav",
    "parameters": {
      "quality": "high",
      "format": "wav",
      "sample_rate": 44100
    },
    "metadata": {
      "source": "api",
      "client_id": "client-123",
      "duration_estimate": 180
    }
  }'
```

### С идентификатором аудио

```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token-123" \
  -d '{
    "audio_identifier": "AUDIO_20250820_001",
    "parameters": {
      "quality": "medium"
    }
  }'
```

### Ответ при создании

```json
{
  "success": true,
  "message": "Audio processing task created successfully",
  "data": {
    "id": 1,
    "audio_url": "https://example.com/audio/conversation.wav",
    "audio_identifier": null,
    "status": "new",
    "parameters": {
      "quality": "high",
      "format": "wav",
      "sample_rate": 44100
    },
    "metadata": {
      "source": "api",
      "client_id": "client-123",
      "duration_estimate": 180
    },
    "started_at": null,
    "completed_at": null,
    "error_message": null,
    "created_at": "2025-08-20T04:43:46.000000Z",
    "updated_at": "2025-08-20T04:43:46.000000Z",
    "transcription_result": null,
    "quality_assessment": null
  }
}
```

## Получение списка задач

### Все задачи

```bash
curl -X GET http://localhost/api/tasks \
  -H "Authorization: Bearer test-token-123"
```

### Фильтрация по статусу

```bash
curl -X GET "http://localhost/api/tasks?status=completed" \
  -H "Authorization: Bearer test-token-123"
```

### С пагинацией

```bash
curl -X GET "http://localhost/api/tasks?per_page=5&page=2" \
  -H "Authorization: Bearer test-token-123"
```

### Ответ со списком

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "audio_url": "https://example.com/audio/conversation.wav",
      "status": "completed",
      "created_at": "2025-08-20T04:43:46.000000Z",
      "transcription_result": {
        "transcription_data": [...]
      },
      "quality_assessment": {
        "overall_score": 85.5
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

## Получение конкретной задачи

```bash
curl -X GET http://localhost/api/tasks/1 \
  -H "Authorization: Bearer test-token-123"
```

### Полный ответ с результатами

```json
{
  "success": true,
  "data": {
    "id": 1,
    "audio_url": "https://example.com/audio/conversation.wav",
    "audio_identifier": null,
    "status": "evaluated",
    "parameters": {
      "quality": "high",
      "format": "wav"
    },
    "metadata": {
      "source": "api",
      "client_id": "client-123"
    },
    "started_at": "2025-08-20T04:44:00.000000Z",
    "completed_at": "2025-08-20T04:44:15.000000Z",
    "error_message": null,
    "created_at": "2025-08-20T04:43:46.000000Z",
    "updated_at": "2025-08-20T04:44:20.000000Z",
    "transcription_result": {
      "transcription_data": [
        {
          "speaker": "S1",
          "start": 0.0,
          "end": 5.0,
          "text": "Добрый день, как я могу помочь?",
          "confidence": 0.95
        },
        {
          "speaker": "S2",
          "start": 5.0,
          "end": 10.0,
          "text": "Здравствуйте, у меня проблема с заказом.",
          "confidence": 0.92
        },
        {
          "speaker": "S1",
          "start": 10.0,
          "end": 15.0,
          "text": "Понял вас, сейчас проверю информацию.",
          "confidence": 0.94
        }
      ],
      "created_at": "2025-08-20T04:44:15.000000Z"
    },
    "quality_assessment": {
      "assessment_data": {
        "overall_score": 85.5,
        "detailed_scores": {
          "politeness": 90,
          "clarity": 85,
          "responsiveness": 80,
          "problem_resolution": 88,
          "professionalism": 84
        },
        "conversation_metrics": {
          "total_duration": 15.0,
          "total_segments": 3,
          "speaker_distribution": {
            "S1": {
              "segments": 2,
              "total_time": 10.0,
              "word_count": 12
            },
            "S2": {
              "segments": 1,
              "total_time": 5.0,
              "word_count": 8
            }
          }
        },
        "recommendations": [
          "Отличная работа! Продолжайте в том же духе."
        ],
        "summary": "Разговор продолжительностью 15.0 секунд между 2 участниками. Качество обслуживания: хорошее (85.5 баллов)."
      },
      "overall_score": 85.5,
      "detailed_scores": {
        "politeness": 90,
        "clarity": 85,
        "responsiveness": 80,
        "problem_resolution": 88,
        "professionalism": 84
      },
      "score_grade": "Good",
      "created_at": "2025-08-20T04:44:20.000000Z"
    },
    "service_logs": [
      {
        "service_name": "transcription_service",
        "operation": "process_audio",
        "status": "success",
        "error_message": null,
        "response_time_ms": 2500,
        "created_at": "2025-08-20T04:44:15.000000Z"
      },
      {
        "service_name": "llm_service",
        "operation": "assess_quality",
        "status": "success",
        "error_message": null,
        "response_time_ms": 1800,
        "created_at": "2025-08-20T04:44:20.000000Z"
      }
    ]
  }
}
```

## Обработка ошибок

### Отсутствие токена

```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -d '{"audio_url": "https://example.com/audio.wav"}'
```

```json
{
  "error": "Authentication token is required",
  "message": "Please provide a valid API token in Authorization header or X-API-Token header"
}
```

### Неверный токен

```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer invalid-token" \
  -d '{"audio_url": "https://example.com/audio.wav"}'
```

```json
{
  "error": "Invalid authentication token",
  "message": "The provided API token is not valid"
}
```

### Ошибка валидации

```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer test-token-123" \
  -d '{"parameters": {"quality": "high"}}'
```

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "audio_source": [
      "Either audio_url or audio_identifier must be provided"
    ]
  }
}
```

### Задача не найдена

```bash
curl -X GET http://localhost/api/tasks/999 \
  -H "Authorization: Bearer test-token-123"
```

```json
{
  "message": "No query results for model [App\\Models\\AudioTask] 999"
}
```

## Альтернативный способ передачи токена

Вместо заголовка Authorization можно использовать X-API-Token:

```bash
curl -X GET http://localhost/api/tasks \
  -H "X-API-Token: test-token-123"
```

## Статусы задач

- `new` - задача создана, ожидает обработки
- `processing` - задача обрабатывается
- `completed` - транскрибация завершена
- `failed` - ошибка при обработке
- `evaluated` - оценка качества завершена

## Мониторинг прогресса

Для отслеживания прогресса обработки можно периодически запрашивать статус задачи:

```bash
# Проверяем каждые 30 секунд
while true; do
  curl -s -X GET http://localhost/api/tasks/1 \
    -H "Authorization: Bearer test-token-123" | \
    jq '.data.status'
  sleep 30
done
```