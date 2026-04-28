# Windows + Shared Hosting checklist

## Backend (shared hosting)

1. Upload `faceauth-backend/`.
2. Set web root to `faceauth-backend/public`.
3. Create `faceauth-backend/.env` from `.env.example`.
4. Run env check (CLI):

```bash
php scripts/check_env.php
```

5. Run smoke test:

```bash
php scripts/smoke_test.php https://your-backend-domain
```

## Moodle (Windows server)

1. Place plugin folder into Moodle `mod/quiz/accessrule/faceauth`.
2. Open Moodle admin notifications and complete plugin install.
3. Set plugin config:
   - backend URL
   - tenant id
   - shared secret
   - snapshot interval

## Core (Windows)

1. Configure `.env` using `faceauth-core/.env.example`.
2. Initialize MySQL:

```bat
scripts\init_mysql_windows.bat
```

3. Run service:

```bat
scripts\run_windows.bat
```
