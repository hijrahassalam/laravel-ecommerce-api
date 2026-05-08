# Laravel E-Commerce API

Production-ready REST API for e-commerce built with Laravel 13 and Stripe.

## Tech Stack

Laravel 13 · PHP 8.4 · MySQL 8 · Stripe SDK · PHPUnit · Docker

## Features

- [ ] Product management (CRUD)
- [ ] Shopping cart (session/database)
- [ ] Stripe Checkout Session
- [ ] Stripe Webhook handler
- [ ] Order management

## Setup

### Using Docker (Recommended)

```bash
git clone https://github.com/hijrahassalam/laravel-ecommerce-api.git
cd laravel-ecommerce-api
cp .env.example .env
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

API available at `http://localhost:8000`

### Without Docker

```bash
git clone https://github.com/hijrahassalam/laravel-ecommerce-api.git
cd laravel-ecommerce-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## API Endpoints

See `/api/documentation` (Swagger) when running.

## Running Tests

```bash
docker-compose exec app php artisan test
# or without docker:
php artisan test
```

## License

MIT
