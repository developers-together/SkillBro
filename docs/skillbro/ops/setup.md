# Environment and Setup

## Install

```bash
composer install --no-interaction --prefer-dist
npm install
```

## Env bootstrap

```bash
cp .env.example .env
php artisan key:generate --force --no-interaction
```

## Local SQLite bootstrap

```bash
mkdir -p database
touch database/database.sqlite
php artisan migrate --force --no-interaction
```

## Run app

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000/skillbro`.
