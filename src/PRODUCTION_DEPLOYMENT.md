# Ehkam API - Production Deployment Checklist

This package contains the Sanctum authentication/authorization hardening added to the existing Ehkam Laravel API.

## Authentication

Public endpoints:

- `POST /api/login`
- `POST /api/register`
- `POST /api/loginStudent`
- `POST /api/admin/token`
- `POST /api/teacher/token`
- `POST /api/student/token`

Sanctum-protected application endpoints require:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

The existing JWT endpoints `/api/profile` and `/api/logout` remain on `auth:api` and were not replaced.

## Authorization

- **Admin:** full access to administrative routes plus teacher operations.
- **Teacher:** academic/record/attendance/schedule operations and read-only student/metadata endpoints.
- **Student:** only the explicitly student-owned profile/attendance endpoints. Student requests are scoped to the authenticated student's `student_id`/`user_id`.
- Teacher profile requests are scoped to the authenticated teacher unless the caller is an Admin.

## Before deployment

1. Copy `.env.production.example` to `.env`.
2. Set a strong unique `APP_KEY` with:

```bash
php artisan key:generate --force
```

3. Set production database credentials.
4. Set the real HTTPS `APP_URL`.
5. Keep `APP_DEBUG=false`.
6. Keep the database private; do not expose MySQL port 3306 to the public Internet.
7. Configure Nginx/Apache document root to Laravel's `public/` directory.
8. Use PHP-FPM in production rather than `php artisan serve`.

## Install and optimize

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If your deployment does not use Laravel views, `view:cache` is optional.

## Sanctum tokens

The token expiration is controlled by:

```env
SANCTUM_TOKEN_EXPIRATION=43200
```

`43200` minutes is 30 days. Change it to the shortest practical lifetime for your application.

Never commit `.env`, plaintext Sanctum tokens, database passwords, `APP_KEY`, or JWT secrets.

## Important student-login limitation

The existing student authentication contract is `name + student_id`. This is preserved to avoid breaking the current mobile application. It is not equivalent to a secret password/PIN: anyone who knows both values can request a student token. For a stronger security model, introduce a student PIN/password in a future version and update the mobile client deliberately.

## Verification

Run:

```bash
php artisan route:list
```

Confirm that token-generation endpoints do **not** have `auth:sanctum`, while normal protected endpoints do.

Then test with Postman:

1. Generate an Admin, Teacher, or Student token.
2. Call a protected endpoint with `Authorization: Bearer TOKEN`.
3. Call it without a token and confirm `401`.
4. Revoke the current token with `DELETE /api/token`.
5. Reuse the revoked token and confirm `401`.
