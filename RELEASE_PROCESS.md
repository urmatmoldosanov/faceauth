# FaceAuth Release Process

## 1) Перед релизом

- Убедиться, что CI (`.github/workflows/ci.yml`) зелёный.
- Обновить changelog/документацию при необходимости.
- Проверить `PRODUCTION_READINESS_CHECKLIST.md`.

## 2) Создание релиза

Вариант A: вручную через GitHub Actions `workflow_dispatch`:
- Запустить workflow `faceauth-release`.

Вариант B: через git tag:

```bash
git tag v0.1.0
git push origin v0.1.0
```

## 3) Артефакты релиза

Workflow собирает и загружает:
- `faceauth-core.zip`
- `faceauth-backend.zip`
- `quizaccess_faceauth.zip`
- `docs.zip`

## 4) Проверка артефактов

- Убедиться, что zip открываются.
- Проверить структуру внутри архивов.
- Для Moodle: в zip должен быть корень `faceauth` (содержимое plugin-папки).

## 5) Раскатка

- Установить/обновить Core.
- Установить/обновить Backend.
- Обновить Moodle plugin.
- Выполнить smoke checks и E2E проверку.
