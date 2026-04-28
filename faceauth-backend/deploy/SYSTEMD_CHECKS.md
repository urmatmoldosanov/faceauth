# Systemd/Service checks

## PHP-FPM

```bash
sudo systemctl status php8.1-fpm
sudo systemctl restart php8.1-fpm
sudo systemctl enable php8.1-fpm
```

## Nginx

```bash
sudo nginx -t
sudo systemctl status nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

## Quick health checks

```bash
curl -sS http://faceauth-backend.local/health
curl -sS http://faceauth-backend.local/license/status
```

## Troubleshooting

```bash
sudo journalctl -u nginx -n 100 --no-pager
sudo journalctl -u php8.1-fpm -n 100 --no-pager
tail -n 100 /var/www/faceauth-backend/storage/logs/events-$(date +%F).log
```
