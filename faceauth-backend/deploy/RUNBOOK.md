# Runbook (FaceAuth Backend)

## Daily checks

1. Service health:

```bash
curl -sS http://faceauth-backend.local/health
```

2. License cache status:

```bash
curl -sS http://faceauth-backend.local/license/status
```

3. Admin access:
- open `/admin/index.php`
- verify latest events are updating

## If backend is down

```bash
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx
sudo systemctl status php8.1-fpm nginx
```

Then rerun smoke test:

```bash
cd /var/www/faceauth-backend
./scripts/smoke_test.sh http://faceauth-backend.local
```

## If snapshots stop arriving

1. Check Moodle relay logs / plugin status.
2. Check backend app logs:

```bash
tail -n 200 /var/www/faceauth-backend/storage/logs/events-$(date +%F).log
```

3. Check Nginx/PHP errors:

```bash
sudo journalctl -u nginx -n 200 --no-pager
sudo journalctl -u php8.1-fpm -n 200 --no-pager
```

## Security rotation

- Rotate admin/viewer credentials monthly.
- Update `*_PASS_HASH` env vars.
- Reload PHP-FPM after env changes:

```bash
sudo systemctl restart php8.1-fpm
```
