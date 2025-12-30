# Deployment Guide - Mr. Xie's Convenience Store

This guide outlines the steps to deploy the application to a production environment using Docker.

## Prerequisites
- Docker & Docker Compose installed on the host machine.
- Git (to pull the repository).
- A domain name (optional, but recommended for SSL).

## 1. Environment Configuration
1.  **Copy `.env.example` to `.env`**:
    ```bash
    cp .env.example .env
    ```
2.  **Update Production Values**:
    *   `APP_ENV`: Set to `production`
    *   `APP_DEBUG`: Set to `false`
    *   `APP_URL`: Your production URL (e.g., `https://store.example.com`)
    *   `DB_PASSWORD`: Set a strong password.
    *   `MEILISEARCH_KEY`: Set a strong master key.

## 2. Frontend Build
The frontend is a Vue SPA. For production, we build static files and serve them via Nginx.
(Note: Our Docker setup handles this automatically if using the production Dockerfile, but for manual verification:)
```bash
cd xie_vue
npm install
npm run build
# The 'dist/' directory now contains the production-ready assets.
```

## 3. Deployment Methods

### Option A: Quick Scripts (Recommended)

The project includes convenience scripts to handle building, bringing up containers, and running initial migrations.

**Windows:**
```bash
.\deploy.bat
```

**Linux / macOS:**
```bash
chmod +x deploy.sh
./deploy.sh
```

### Option B: Make Commands (If Make is installed)
The project includes a `Makefile` for common operations.

```bash
make up-prod     # Start production environment
make logs        # View logs
make shell       # Enter app container
```

### Option C: Manual Docker Deployment
If you prefer full control or need to debug:

```bash
# Build and Start Containers (Detached mode)
docker-compose -f docker-compose.prod.yml up -d --build

# Run Migrations (First time only)
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force --seed

# Optimize Backend (Critical for speed)
docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## 4. Verification
1.  Visit your URL (e.g., `http://localhost`).
2.  Check API Health: `http://localhost/api/settings` should return JSON.
3.  Test Login: Ensure you can log in as Admin.

## 5. Maintenance
*   **Logs**: `docker-compose logs -f app`
*   **Updates**: 
    ```bash
    git pull
    # Use deploy script again or:
    docker-compose -f docker-compose.prod.yml up -d --build
    ```
