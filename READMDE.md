# News Aggregator — Laravel + React (Docker)

Aplicație full-stack rulată complet în Docker.

### Stack
- **Backend:** Laravel 12 + FrankenPHP
- **Frontend:** React + Vite
- **Baze de date:** MySQL 8, MongoDB 7, Redis 7
- **Altele:** Elasticsearch, Soketi (WebSockets), Horizon, Prometheus, Grafana, phpMyAdmin

---

## 🚀 Pornire rapidă

```bash
# 1. Pornește totul
docker compose up -d --build

# 2. Backend setup
cp backend/.env.example backend/.env
docker compose exec laravel_app composer install
docker compose exec laravel_app php artisan key:generate
docker compose exec laravel_app php artisan jwt:secret
docker compose exec laravel_app php artisan migrate --seed

# 3. Frontend setup
cp frontend/.env.example frontend/.env
docker compose exec frontend npm install
```

Apoi deschide:
- Frontend → http://localhost:5173
- API → http://localhost:8000
- phpMyAdmin → http://localhost:8081
- Grafana → http://localhost:3000 (user: `admin`)

---

## 🌐 URL-uri importante

| Serviciu          | URL                      |
|-------------------|--------------------------|
| React (Vite)      | http://localhost:5173    |
| Laravel API       | http://localhost:8000    |
| phpMyAdmin        | http://localhost:8081    |
| Grafana           | http://localhost:3000    |
| Prometheus        | http://localhost:9090    |
| Elasticsearch     | http://localhost:9200    |
| Soketi (WS)       | http://localhost:6001    |
| MySQL (host)      | localhost:3307           |
| MongoDB (host)    | localhost:27018          |
| Redis (host)      | localhost:6380           |

---

## ⚙️ Configurație .env (important!)

### Backend (`backend/.env`)
```dotenv
DB_HOST=mysql
DB_PORT=3306
REDIS_HOST=redis
DB_MONGO_HOST=mongodb
ELASTICSEARCH_HOST=elasticsearch
PUSHER_HOST=soketi          # ← din interiorul Docker
PUSHER_PORT=6001
```

### Frontend (`frontend/.env`)
```dotenv
VITE_API_URL=http://localhost:8000
VITE_PUSHER_HOST=localhost  # ← din browser
VITE_PUSHER_PORT=6001
```

> **Regulă simplă:**  
> - Din **containere** → folosești numele serviciului (`mysql`, `redis`, `soketi`...)  
> - Din **browser** → folosești `localhost` + portul publicat

---

## 🛠️ Comenzi utile

```bash
# Status
docker compose ps

# Loguri
docker compose logs -f
docker compose logs -f laravel_app
docker compose logs -f laravel_worker

# Restart
docker compose restart laravel_app
docker compose restart laravel_worker

# Oprește tot
docker compose down

# Oprește + șterge volume (atenție: șterge datele!)
docker compose down -v
```

### Laravel
```bash
docker compose exec laravel_app bash
docker compose exec laravel_app php artisan migrate
docker compose exec laravel_app php artisan optimize:clear
docker compose exec laravel_app php artisan horizon:status
docker compose exec laravel_app php artisan schedule:work
docker compose restart prometheus
```

### Frontend
```bash
docker compose exec frontend npm install
docker compose exec frontend npm run build
```

---

## 📋 Servicii Docker

| Service            | Port host | Rol                        |
|--------------------|-----------|----------------------------|
| `laravel_app`      | 8000      | API + FrankenPHP           |
| `laravel_worker`   | —         | Horizon (queues)           |
| `frontend`         | 5173      | React + Vite               |
| `mysql`            | 3307      | MySQL                      |
| `mongodb`          | 27018     | MongoDB                    |
| `redis`            | 6380      | Cache + Queue              |
| `elasticsearch`    | 9200      | Search                     |
| `soketi`           | 6001      | WebSockets                 |
| `prometheus`       | 9090      | Metrics                    |
| `grafana`          | 3000      | Dashboards                 |
| `phpmyadmin`       | 8081      | Admin MySQL                |

---

## 🔑 Credențiale

**MySQL / phpMyAdmin**
- Database: `news_aggregator`
- User: `news_aggregator`
- Password: `secret`

**Soketi / Pusher**
- App ID: `news_aggregator`
- Key: `news_aggregator_key`
- Secret: `news_aggregator_secret`

**Grafana**
- User: `admin`
```
