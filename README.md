# Laravel E-Commerce API

Production-ready REST API for e-commerce built with **Laravel 13** and **Stripe**.

[![CI](https://github.com/hijrahassalam/laravel-ecommerce-api/actions/workflows/ci.yml/badge.svg)](https://github.com/hijrahassalam/laravel-ecommerce-api/actions/workflows/ci.yml)

## Tech Stack

Laravel 13 · PHP 8.4 · MySQL 8 · Stripe SDK · PHPUnit · Docker

## Features

- [x] Product management (CRUD + search)
- [x] Shopping cart (session-based, stock validation)
- [x] Stripe Checkout Session
- [x] Stripe Webhook handler (payment confirmed, failed, expired)
- [x] Order management (customer + admin)
- [x] Refund flow
- [x] PHPUnit feature tests (35+ tests)
- [x] Docker + docker-compose setup
- [x] GitHub Actions CI

## API Endpoints

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | List products (search, pagination) |
| GET | `/api/products/{id}` | Get single product |
| GET | `/api/up` | Health check |

### Cart (Session-based)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/cart` | View cart |
| POST | `/api/cart/items` | Add item |
| PUT | `/api/cart/items/{id}` | Update quantity |
| DELETE | `/api/cart/items/{id}` | Remove item |
| DELETE | `/api/cart` | Clear cart |

### Checkout
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/checkout` | Create Stripe checkout session |

### Orders (Customer)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List orders |
| GET | `/api/orders/{id}` | Get order |
| POST | `/api/orders/{id}/refund` | Request refund |

### Admin
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/orders` | List all orders (filterable) |
| GET | `/api/admin/orders/stats` | Revenue/order stats |
| GET | `/api/admin/orders/{id}` | Get order details |
| PATCH | `/api/admin/orders/{id}/status` | Update order status |

### Webhooks
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/webhook/stripe` | Stripe webhook |

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

## Stripe Setup

1. Create account at [stripe.com](https://stripe.com)
2. Get API keys from Dashboard → Developers → API keys
3. Set in `.env`:
   ```
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   ```
4. For webhooks, use Stripe CLI:
   ```bash
   stripe listen --forward-to localhost:8000/api/webhook/stripe
   ```
5. Copy webhook secret from output and set:
   ```
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

## Running Tests

```bash
php artisan test
# or with coverage:
php artisan test --coverage
```

## Project Structure

```
laravel-ecommerce-api/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/OrderAdminController.php
│   │   ├── CartController.php
│   │   ├── OrderController.php
│   │   ├── ProductController.php
│   │   └── StripeWebhookController.php
│   └── Models/
│       ├── Cart.php, CartItem.php
│       ├── Order.php, OrderItem.php
│       └── Product.php
├── database/
│   ├── factories/   (Product, Order, OrderItem)
│   ├── migrations/
│   └── seeders/     (ProductSeeder - 6 products)
├── config/          (app, database, cors, session, stripe)
├── routes/api.php
├── tests/Feature/   (35+ tests)
├── docker-compose.yml
└── Dockerfile
```

## License

MIT
