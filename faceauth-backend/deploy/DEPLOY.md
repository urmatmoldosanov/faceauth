# Deploy guide (Nginx + PHP-FPM)

## 1) Подготовка

```bash
sudo mkdir -p /var/www/faceauth-backend
sudo rsync -av ./faceauth-backend/ /var/www/faceauth-backend/
```

## 2) Конфигурация окружения

Установите env vars для php-fpm пула (например в `/etc/php/8.1/fpm/pool.d/www.conf`):

```ini
env[FACEAUTH_CORE_URL] = https://core.example.com
env[FACEAUTH_TENANT_ID] = tenant_demo
env[FACEAUTH_TENANT_SECRET] = change_me
env[FACEAUTH_MOODLE_SHARED_SECRET] = change_me
env[FACEAUTH_ADMIN_USER] = admin
env[FACEAUTH_ADMIN_PASS_HASH] = $2y$10$...
env[FACEAUTH_VIEWER_USER] = viewer
env[FACEAUTH_VIEWER_PASS_HASH] = $2y$10$...
```

> Hash сгенерировать: `php scripts/generate_password_hash.php "StrongPassword"`.

## 3) Nginx

```bash
sudo cp deploy/nginx-faceauth-backend.conf /etc/nginx/sites-available/faceauth-backend.conf
sudo ln -s /etc/nginx/sites-available/faceauth-backend.conf /etc/nginx/sites-enabled/faceauth-backend.conf
sudo nginx -t
sudo systemctl reload nginx
```

## 4) Права

```bash
sudo chown -R www-data:www-data /var/www/faceauth-backend/storage
sudo chmod -R 770 /var/www/faceauth-backend/storage
```

## 5) Проверка

```bash
curl -sS http://faceauth-backend.local/health
curl -sS http://faceauth-backend.local/license/status
```

## 6) Рекомендации

- Включить HTTPS (Let's Encrypt).
- Ограничить доступ к `/admin/` по IP/VPN.
- Настроить ротацию логов для `storage/logs`.
