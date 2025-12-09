# Multi-Vendor E-Commerce Platform

Laravel 12 + Vue 3 + Inertia.js multi-vendor marketplace for Indonesian market.

## Features
- Multi-vendor support with commission management
- Payment integration (Midtrans)
- Shipping integration (RajaOngkir Pro)
- PMK 37/2025 tax compliance
- Docker infrastructure
- Designed for 4M users

## Quick Start

```bash
composer install
composer require midtrans/midtrans-php
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --class=IndonesianLocationSeeder
php artisan db:seed --class=DemoDataSeeder
npm run dev & php artisan serve
```

## Demo Accounts
- Customer: `customer@demo.com` / `password`
- Vendor: `vendor1@demo.com` / `password`

## Documentation
See `/brain` folder for complete guides.

## Tech Stack
Laravel 12, Vue 3, MySQL, Redis, Docker

## License
MIT
