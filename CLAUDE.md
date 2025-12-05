# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ManySales is a multi-vendor e-commerce platform built on Laravel 12 (6valley-based). It supports multiple sellers, customers, delivery personnel, and admin users with comprehensive order management, payment processing, and product catalog features.

## Development Environment

- **PHP**: 8.2+
- **Framework**: Laravel 12
- **Database**: PostgreSQL or MySQL
- **Local Server**: ServBay (Windows) at `C:\ServBay\www\manysales`

### Common Commands

```bash
# Run migrations
php artisan migrate

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Regenerate autoload
composer dump-autoload -o

# Run tests
php artisan test
```

## Architecture

### Multi-Panel System

The application has four distinct user panels:
- **Admin** (`routes/admin/routes.php`) - Full system management
- **Vendor** (`routes/vendor/routes.php`) - Seller store management
- **Customer/Web** (`routes/web/routes.php`) - Frontend storefront
- **REST API** (`routes/rest_api/`) - Mobile app endpoints (v1, v2, v3)

### Modular System

Uses `nwidart/laravel-modules` for extensibility:
- `Modules/Blog` - Blog functionality
- `Modules/AI` - AI-powered features
- `Modules/TaxModule` - Tax calculations

Module namespace: `Modules\{ModuleName}\app\`

### Theme System

Two themes in `resources/themes/`:
- `default` - Base theme
- `theme_aster` - Alternative theme

Theme helper functions in `app/Utils/theme-helpers.php`

### Key Directories

```
app/
├── Http/Controllers/
│   ├── Admin/           # Admin panel controllers
│   ├── Payment_Methods/ # Payment gateway integrations
│   ├── RestAPI/         # API controllers (v1, v2)
│   ├── Web/             # Frontend controllers
│   └── Customer/        # Customer account controllers
├── Models/              # Eloquent models
├── Services/            # Business logic services
├── Traits/              # Reusable traits
└── Utils/               # Helper functions (auto-loaded)
```

### Auto-loaded Helper Files

Global functions defined in `app/Utils/`:
- `Helpers.php` - General utility functions
- `settings.php` - `getWebConfig()`, settings retrieval
- `language.php` - `translate()` function
- `currency.php` - Currency conversion
- `OrderManager.php` - Order processing
- `CartManager.php` - Cart operations
- `ProductManager.php` - Product utilities

### Payment System

Payment gateway trait: `App\Traits\Payment`

Adding new payment gateway:
1. Create controller in `app/Http/Controllers/Payment_Methods/`
2. Add route mapping in `app/Traits/Payment.php` `$routes` array
3. Create view in `resources/views/payment/`
4. Add routes in `routes/web/routes.php`

Integrated gateways: Stripe, PayPal, Razorpay, YooKassa, SSL Commerz, Mercadopago, Paytm, and 15+ others.

### Database Considerations

- Uses `doctrine/dbal` for column modifications
- `PaymentRequest` model stores payment attempts with hooks (`success_hook`, `failure_hook`)
- Multi-language support: Many models store translatable fields as JSON

## Language Configuration

**Default language is Russian (ru)**. When working with form validation or data access:
- Use `'ru'` as primary locale key, not `'en'`
- Fallback pattern: `$data['ru'] ?? $data[array_key_first($data)] ?? null`

Translation files: `resources/lang/ru/`

## Key Models

- `Product` - Catalog items with variants
- `Order` / `OrderDetail` - Order management
- `Seller` / `Shop` - Vendor entities
- `Customer` / `User` - Customer accounts
- `PaymentRequest` - Payment transaction tracking
- `BusinessSetting` - System configuration (key-value)

## Configuration

System settings stored in `business_settings` table, accessed via:
```php
getWebConfig(name: 'setting_key')
```

Payment gateway configs stored in `addon_settings` table.

## SSL/Development Notes

For local development with external APIs (YooKassa, etc.), SSL verification may need to be disabled:
```
YOOKASSA_DISABLE_SSL_VERIFY=true
```
