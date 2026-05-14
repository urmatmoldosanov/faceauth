# FaceAuth Premium — состав цельного проекта

Этот репозиторий уже содержит все файлы, нужные для установки проекта на обычный PHP-хостинг или VPS.

## Минимальный комплект для загрузки на хостинг

Загрузите на сервер весь каталог проекта, кроме `.git` и локального `dist`:

```text
.htaccess
index.php
README.md
PROJECT_FILES.md
config/config.example.php
config/.htaccess
database/mysql.sql
database/sqlite.sql
public/.htaccess
public/index.php
public/assets/css/app.css
public/assets/img/student-placeholder.svg
public/assets/img/violation-placeholder.svg
storage/.htaccess
storage/uploads/.gitkeep
```

## Быстрый вариант

1. Скопируйте файлы проекта на хостинг.
2. Если хостинг позволяет выбрать document root — укажите папку `public`.
3. Если document root изменить нельзя — оставьте корнем весь проект: корневые `index.php` и `.htaccess` перенаправят запросы в `public` и закроют служебные папки.
4. Откройте домен в браузере и пройдите installer.

## Архив проекта

Чтобы собрать архив для передачи или загрузки на хостинг, выполните:

```bash
./scripts/package.sh
```

Скрипт создаст `dist/faceauth-premium-project.zip` или `dist/faceauth-premium-project.tar.gz` и исключит `.git`, локальный конфиг, SQLite-базу и загруженные файлы пользователей.

## База данных

- Installer сам создает таблицу для SQLite или MySQL.
- SQL-файлы в `database/` нужны для ручной проверки или ручного создания таблицы, если хостинг запрещает автоматический `CREATE TABLE`.
