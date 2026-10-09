# Docker setup

Full instructions live in the [README](README.md). This page covers the Docker commands you are most likely to need.

## Start

```bash
cp .env.example .env
docker compose up -d --build
```

Open http://localhost:8080. The schema in `schema.sql` is imported automatically the first time the database volume is created.

The application reads `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASSWORD` from the environment (via `.env`). Missing variables stop the application with a clear error instead of falling back to defaults.

## Stop and reset

```bash
docker compose down          # stop containers, keep the database volume
docker compose down -v       # stop containers and delete the database
docker compose up -d --build # rebuild and start
docker compose logs -f       # follow web and database logs
docker compose logs web      # application errors are logged here, never printed to the browser
```

## Database access

```bash
docker compose exec db mysql -uroot -p"$DB_PASSWORD"
```

## Tests

```bash
docker compose exec web composer install
docker compose exec web php vendor/bin/phpunit
```

The suite creates its own database (`task_manager_test` by default) and never touches your development data.

## Troubleshooting

1. `docker compose down -v` removes volumes and containers.
2. `docker compose up -d --build` rebuilds and starts.
3. The schema is imported on first start of an empty database. After `down -v` it is imported again.
