# Docker Setup Instructions

## Quick Start with Docker

1. Build and start the containers:
   ```bash
   docker-compose up -d
   ```

2. Import the database schema:
   ```bash
   docker-compose exec db mysql -uroot -prootpass < schema.sql
   ```

3. Access the application:
   - Open your browser and go to: http://localhost:8080

4. To stop the containers:
   ```bash
   docker-compose down
   ```

## Using Docker Database Configuration

If you want to use Docker, replace the database configuration file:

```bash
mv config/database.php config/database.local.php
mv config/database.docker.php config/database.php
```

Or update the database credentials in `config/database.php` to match your Docker setup:
- Host: `db`
- Database: `task_manager`
- User: `root`
- Password: `rootpass`

## Docker Commands

- View logs: `docker-compose logs -f`
- Restart containers: `docker-compose restart`
- Rebuild containers: `docker-compose up -d --build`
- Access MySQL: `docker-compose exec db mysql -uroot -prootpass`

## Troubleshooting

If you encounter issues, try:
1. `docker-compose down -v` (removes volumes and containers)
2. `docker-compose up -d --build` (rebuilds and starts)
3. Re-import the schema
