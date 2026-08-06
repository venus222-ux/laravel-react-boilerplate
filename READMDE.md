Laravel_react_boilerplate\
├── backend/ # Laravel 12 API
└── frontend/ # React + Vite + TS SPA

## Comenzi utile de dezvoltare

✅ 1. Set Up Laravel Backend
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve

php artisan jwt:secret
php artisan config:clear
php artisan config:cache

✅ 2. Set Up React Frontend
cd ../frontend
cp .env.example .env
npm install
npm run dev

✅ 3. Run in the root project:
npm run dev
docker-compose up -d

## Stack tehnic

**Backend**

- Laravel (PHP) + MySQL
- Redis (queue, cache) + Laravel Horizon (monitorizare cozi)
- MongoDB (loguri de upload)

**Frontend**

- React + TypeScript
- Zustand (state management)
- React Query (data fetching pentru unele hook-uri)
- Bootstrap + CSS Modules


```env
QUEUE_CONNECTION=redis
MAIL_MAILER=smtp
MAIL_ADMIN_ADDRESS=admin@yourcompany.com
FRONTEND_URL=http://localhost:5173
ELASTICSEARCH_HOST=http://127.0.0.1:9200