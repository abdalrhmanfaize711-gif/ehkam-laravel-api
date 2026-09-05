# Ehkam Production Security

## What was hardened in this package

- API-wide rate limiting (`throttle:api`) with a configurable `API_RATE_LIMIT` (default 60/minute).
- Login rate limiting (`throttle:login`) at 5/minute per identity + IP.
- Sanctum token expiration defaults to 7 days (10080 minutes).
- API security headers middleware.
- `Cache-Control: no-store, private` on API responses.
- Production API exceptions return a generic 500 response instead of stack traces.
- CORS is explicitly configured from `CORS_ALLOWED_ORIGINS`.
- Admin/teacher password fields are hidden from model serialization.
- Existing FormRequest validation files are preserved.
- Exact duplicate route declarations inside the same middleware scope were removed. Role-specific routes remain separate.
- Added automated route-security tests.

## Important: infrastructure is outside this Laravel source archive

The uploaded project archive does not contain the Docker Compose file, reverse proxy, MySQL/Redis service definitions, or phpMyAdmin service. Therefore this package does not invent or modify those files.

For production, configure: 

1. HTTPS at the reverse proxy/load balancer.
2. MySQL bound to the private Docker network only; never publish 3306 publicly.
3. Redis bound to the private Docker network only; never publish 6379 publicly.
4. Remove phpMyAdmin from production, or keep it on a private admin network with authentication and IP restrictions.
5. `APP_ENV=production` and `APP_DEBUG=false`.
6. Generate a unique production `APP_KEY`; never copy the development key.
7. Keep `.env` out of Git and inject secrets through the deployment environment/secret manager.
8. Use a dedicated DB user with only the privileges Ehkam needs.
9. Encrypt backups and test restoring them.
10. Keep Laravel/PHP/system dependencies patched.
11. Put the API behind a firewall/security group and expose only HTTPS.
12. Run `php artisan optimize` during deployment.
13. Review logs and alert on repeated 401/403/429/500 responses.

## Docker production rule

The application container should reach MySQL/Redis by Docker service name on a private network. Only the HTTP reverse proxy should publish a host port.

## Verification commands

```bash
php artisan config:clear
php artisan route:list --path=api
php artisan test
php artisan optimize
```

In Docker, run the equivalent commands with `docker compose exec app ...`.

## Security test scope

The automated route tests verify that API routes are authenticated (except the explicitly public login/register endpoints) and that duplicate method+URI signatures are absent. Functional, authorization/ownership, and penetration testing still require valid Ehkam test data and should be run against a staging database—not the production database.


## Before go-live

- Do not copy `.env` from development into production.
- Do not expose MySQL (3306) or Redis (6379) to the Internet.
- Do not ship phpMyAdmin in the public production network.
- Put only the HTTPS reverse proxy/load balancer on a public interface.
- Use a real production secret for `DB_PASSWORD`, `APP_KEY`, and `CORS_ALLOWED_ORIGINS`.
- Rotate any credentials that have ever been committed to Git or shared in chat.
- Run the security tests against a staging database populated with non-production data.
