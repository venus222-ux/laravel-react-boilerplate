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

php artisan queue:work

✅ 2. Set Up React Frontend
cd ../frontend
cp .env.example .env
npm install

✅ 3.
In the root directory run:
npm run dev
docker-compose up -d
