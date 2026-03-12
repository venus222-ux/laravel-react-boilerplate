To download and reuse your Laravel + React boilerplate from GitHub later, 
follow this simple workflow:

✅ 1. Clone the Boilerplate
From any terminal or command prompt:
git clone https://github.com/venus222-ux/laravel-react-boilerplate.git my-new-project
cd my-new-project

Replace my-new-project with your actual project name.

✅ 2. Set Up Laravel Backend
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


✅ 3. Set Up React Frontend
cd ../frontend
cp .env.example .env
npm install
npm run dev
