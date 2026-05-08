# Laravel E-Commerce API

**🌐 Live API:** `https://laravel-ecommerce-api-production.up.railway.app/api/products`

Production-ready REST API for e-commerce built with **Laravel 13** and **Stripe**.

[![Laravel](https://img.shields.io/badge/Laravel-13-red?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=flat-square&logo=postgresql)](https://neon.tech)
[![Stripe](https://img.shields.io/badge/Stripe-Ready-635BFF?style=flat-square&logo=stripe)](https://stripe.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat-square&logo=docker)](https://docker.com)
[![Railway](https://img.shields.io/badge/Deploy-Railway-744EF7?style=flat-square&logo=railway)](https://railway.app)
[![Neon](https://img.shields.io/badge/DB-Neon%20PostgreSQL-000000?style=flat-square&logo=postgresql)](https://neon.tech)
[![CI](https://github.com/hijrahassalam/laravel-ecommerce-api/actions/workflows/ci.yml/badge.svg)](https://github.com/hijrahassalam/laravel-ecommerce-api/actions/workflows/ci.yml)
[![Tests](https://img.shields.io/badge/Tests-31%20passing-10B981?style=flat-square)](https://github.com/hijrahassalam/laravel-ecommerce-api/actions)

## Deployment

This API is deployed on **Railway** with **Neon PostgreSQL** as the database.

### Infrastructure

```
┌─────────────────────────────────────────────────────────┐
│  Railway                                                │
│  ┌──────────────────┐                                  │
│  │  Laravel API     │  ← PHP 8.4 + Docker              │
│  │  (Dockerfile)    │  ← Auto-migrate + seed           │
│  └────────┬─────────┘                                  │
│           │                                             │
│           │ pgsql://                                    │
│           ▼                                             │
│  ┌──────────────────┐                                  │
│  │  Neon PostgreSQL  │  ← Serverless, 3GB free         │
│  └──────────────────┘                                  │
└─────────────────────────────────────────────────────────┘
```

### Deploy to Railway (Your Own)

1. Create account at [railway.app](https://railway.app) (free tier available)
2. Create **PostgreSQL** database at [neon.tech](https://neon.tech) (free, no credit card)
3. Fork this repo to your GitHub
4. In Railway → **New Project** → **Deploy from GitHub**
5. Add environment variables:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=your-neon-host.ep-xxx.aws.neon.tech
   DB_PORT=5432
   DB_DATABASE=laravel_ecommerce
   DB_USERNAME=your-neon-username
   DB_PASSWORD=your-neon-password

   APP_KEY=base64:<generate-with-php-artisan-key:generate>

   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

6. Railway auto-detects `Dockerfile` and deploys
7. On first deploy, **migrations + seeding run automatically**

## Features

- [x] Product management (CRUD + search + pagination)
- [x] Shopping cart (session-based, stock validation)
- [x] Stripe Checkout Session
- [x] Stripe Webhook handler (payment confirmed, failed, expired)
- [x] Order management (customer + admin)
- [x] Refund flow
- [x] PHPUnit feature tests (31 tests, all passing)
- [x] Docker + docker-compose for local dev
- [x] Dockerfile for Railway deployment
- [x] Auto-migration + seeding on first deploy
- [x] PostgreSQL via Neon (serverless, no card required)

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 13 |
| Language | PHP 8.4 |
| Database | PostgreSQL 16 (Neon) |
| Payments | Stripe SDK |
| Container | Docker |
| Hosting | Railway |
| Tests | PHPUnit |

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

## Live Demo

```bash
# List products
curl https://laravel-ecommerce-api-production.up.railway.app/api/products

# Health check
curl https://laravel-ecommerce-api-production.up.railway.app/api/up

# View cart
curl https://laravel-ecommerce-api-production.up.railway.app/api/cart
```

## Local Development

### With Docker

```bash
git clone https://github.com/hijrahassalam/laravel-ecommerce-api.git
cd laravel-ecommerce-api
cp .env.example .env
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
php artisan serve
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
3. Set in environment variables:
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
├── tests/Feature/   (31 tests)
├── docker-compose.yml
└── Dockerfile       (Railway deployment)
```

## 🔗 Related Projects

| Project | Description | Live |
|---|---|---|
| [laravel-ecommerce-api](https://github.com/hijrahassalam/laravel-ecommerce-api) | Backend REST API (Laravel 13) | [railway.app](https://laravel-ecommerce-api-production.up.railway.app/api/products) |
| [vue-ecommerce-store](https://github.com/hijrahassalam/vue-ecommerce-store) | Frontend SPA (Vue 3) | [netlify.app](https://vue-ecommerce-store-fe.netlify.app) |

## 👤 Author

**Hijrah Assalam** — [hijrahassalam.com](https://hijrahassalam.com) · [GitHub](https://github.com/hijrahassalam)

## License

MIT
