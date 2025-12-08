# Multi-Vendor E-Commerce Marketplace

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A production-ready **multi-vendor marketplace** built with Laravel 12, targeting the **Indonesian market** with full **PMK 37/2025 tax compliance**.

## 🎯 Project Scope

- **Type:** Multi-Vendor Marketplace (Sellers can register and sell products)
- **Target Scale:** 4,000,000 concurrent users
- **Market:** Indonesia (with localization and tax compliance)
- **Stack:** 100% Open-Source

## ✨ Key Features

### For Customers
- 🛍️ Browse products from multiple vendors
- 🔍 Advanced search with Meilisearch (typo-tolerant, faceted)
- 🛒 Multi-vendor shopping cart
- 💳 Secure payment via Midtrans
- 📦 Real-time order tracking
- ⚡ Real-time notifications (Laravel Reverb)

### For Vendors
- 🏪 Vendor registration with NPWP verification
- 📦 Product management with variants (SKU system)
- 📊 Sales analytics dashboard
- 💰 Automated weekly payouts
- 🚚 Shipping integration (RajaOngkir)
- 📧 Order notifications

### For Admins
- 👥 Vendor approval workflow
- ✅ Product moderation
- 💵 Commission management (variable per category)
- 📈 Platform analytics
- 💸 Payout processing
- 📋 Tax reports (PMK 37/2025)

## 🏗️ Architecture

### Tech Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | Laravel 12 + PHP 8.3 |
| **Frontend** | Inertia.js + Vue 3 + SSR |
| **Admin Panel** | FilamentPHP 3 |
| **Database** | MySQL 8.0 (Primary + 2 Read Replicas) |
| **Cache/Session** | Redis Cluster (3 nodes) |
| **Search** | Meilisearch (self-hosted) |
| **Queue** | Laravel Horizon |
| **WebSockets** | Laravel Reverb |
| **Monitoring** | Prometheus + Grafana |
| **Deployment** | Docker Compose |

### Database Design

**Three-layer product model:**
```
products (abstract catalog)
  ↓
skus (concrete inventory with pricing)
  ↓
attributes/attribute_options (variants: size, color)
```

**Multi-vendor order splitting:**
- Orders contain items from multiple vendors
- Each order item tracks vendor commission
- Independent shipping per vendor
- Vendor-specific order status

### Performance Optimizations

- **Database:** Read/write splitting with 2 read replicas
- **Caching:** Multi-layer Redis cluster with intelligent invalidation
- **Queue:** Horizon with auto-scaling workers
- **Search:** Meilisearch with typo tolerance
- **Static Assets:** Self-hosted CDN
- **OPcache:** Enabled for production

## 📦 Installation

### Prerequisites
- Docker & Docker Compose
- Git
- Node.js 18+ (for frontend assets)

### Quick Start

```bash
# 1. Clone repository
cd FULLSTACK-WEB-APP

# 2. Copy environment file
cp .env.example .env

# 3. Configure environment (edit .env)
# Set Midtrans keys, RajaOngkir API key, database credentials

# 4. Start Docker stack
docker-compose up -d

# 5. Install dependencies & setup Laravel
docker exec -it marketplace-app composer install
docker exec -it marketplace-app php artisan key:generate
docker exec -it marketplace-app php artisan migrate --seed
docker exec -it marketplace-app php artisan storage:link

# 6. Build frontend assets
npm install
npm run dev
```

**Access:**
- Customer site: http://localhost
- Admin panel: http://localhost/admin
- Vendor panel: http://localhost/vendor
- Monitoring: http://localhost:3000 (Grafana)

## 🔧 Configuration

### Commission Settings

Edit `.env`:
```env
COMMISSION_DEFAULT_RATE=0.10  # 10% default
COMMISSION_MINIMUM_PAYOUT=100000  # IDR 100,000
```

Override per:
- **Category:** Admin Panel → Categories → Set commission rate
- **Vendor:** Admin Panel → Vendors → Edit → Commission override

### Tax Compliance (PMK 37/2025)

```env
TAX_VAT_RATE=0.11  # 11% VAT
TAX_MARKETPLACE_WITHHOLDING_RATE=0.025  # 2.5% marketplace withholding
```

### Payment Gateway (Midtrans)

Get credentials from [Midtrans Dashboard](https://dashboard.midtrans.com):
```env
MIDTRANS_SERVER_KEY=your-server-key
MIDTRANS_CLIENT_KEY=your-client-key
MIDTRANS_IS_PRODUCTION=false  # Set true for production
```

### Shipping (RajaOngkir)

Get API key from [RajaOngkir](https://rajaongkir.com):
```env
RAJAONGKIR_API_KEY=your-api-key
RAJAONGKIR_TYPE=pro  # Required for sub-district rates
```

## 🧪 Testing

```bash
# Run all tests
docker exec -it marketplace-app ./vendor/bin/pest

# Run specific test suite
docker exec -it marketplace-app ./vendor/bin/pest --filter=MultiVendorCheckout

# With coverage
docker exec -it marketplace-app ./vendor/bin/pest --coverage
```

## 📊 Monitoring

### Grafana Dashboards
- URL: http://localhost:3000
- User: `admin` / Password: `admin123`

**Pre-configured dashboards:**
- Application performance metrics
- Database query performance
- Redis cluster health
- Queue job statistics
- Order processing metrics

### Laravel Horizon
- URL: http://localhost/horizon
- Monitor queue workers, failed jobs, and throughput

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Generate production `APP_KEY`
- [ ] Configure production database credentials
- [ ] Set Midtrans to production mode
- [ ] Configure SSL/TLS certificates
- [ ] Enable OPcache (`OPCACHE_VALIDATE_TIMESTAMPS=0`)
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run `php artisan optimize`
- [ ] Set up automated backups
- [ ] Configure monitoring alerts
- [ ] Enable rate limiting

### Zero-Downtime Deployment

```bash
# Run deployment script
bash deploy.sh
```

Script performs:
1. Install dependencies
2. Build frontend assets
3. Run migrations (with backup)
4. Cache configuration
5. Restart queue workers
6. Restart WebSocket server

## 📁 Project Structure

```
FULLSTACK-WEB-APP/
├── app/
│   ├── Models/          # Eloquent models
│   ├── Services/        # Business logic (Commission, Payout, Tax)
│   ├── Http/Controllers/
│   └── Filament/        # Admin & vendor panels
│       ├── Resources/   # Admin resources
│       └── Vendor/      # Vendor panel (multi-tenancy)
├── database/
│   ├── migrations/      # 15+ migrations for complete schema
│   └── seeders/         # Demo data seeders
├── resources/
│   ├── js/Pages/        # Inertia.js Vue 3 pages
│   └── views/           # Email templates
├── docker-compose.yml   # Full infrastructure
├── Dockerfile           # PHP 8.3 container
├── nginx/               # Web server config
└── prometheus/          # Monitoring config
```

## 🤝 Contributing

This is a production project. Contributions welcome via pull requests.

### Development Workflow

1. Create feature branch
2. Make changes
3. Run tests (`./vendor/bin/pest`)
4. Create pull request

## 📄 License

MIT License. See `LICENSE` file for details.

## 📞 Support

For questions or issues:
1. Check `implementation_plan.md` for architecture details
2. Review `QUICKSTART.md` for setup guides
3. Check Docker logs: `docker-compose logs`

## 🎯 Roadmap

- [x] Multi-vendor architecture
- [x] Commission & payout system
- [x] PMK 37/2025 tax compliance
- [x] High-scale infrastructure (4M users)
- [ ] Mobile app (React Native)
- [ ] AI product recommendations
- [ ] Social commerce features
- [ ] Vendor subscription tiers

---

**Built with ❤️ for the Indonesian e-commerce ecosystem**
