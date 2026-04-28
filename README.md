# FaceAuth

FaceAuth — MVP-система проверки личности по лицу для Moodle-quiz:

- `faceauth-core` — сервис на FastAPI (сессии, верификация, лицензии).
- `faceauth-backend` — PHP relay/admin для интеграции и хранения снапшотов.
- `quiz/accessrule/faceauth` — Moodle plugin для съёмки кадров во время попытки.

## Почему репозиторий может выглядеть «пустым» на GitHub

Если на GitHub виден только `.gitkeep` и один коммит `Initialize repository`, значит изменения находятся в локальной ветке и ещё не опубликованы в удалённый репозиторий (или опубликованы не в `main`).

Проверьте локально:

```bash
git branch --show-current
git log --oneline --decorate -n 5
```

Опубликуйте текущую ветку и обновите `main`:

```bash
git remote add origin <YOUR_GITHUB_REPO_URL>    # если origin ещё не настроен
git push -u origin work                          # публикуем рабочую ветку
git checkout main                                # или создайте main, если её нет
git merge --ff-only work                         # переносим изменения
git push origin main
```

После этого на главной странице GitHub появятся файлы проекта и README.
