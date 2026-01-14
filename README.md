# Sibali.id - PT. Siap Belajar Indonesia Portal

<p align="center">
  <img src="https://via.placeholder.com/400x200/800000/FFFFFF?text=Sibali.id" alt="Sibali.id Logo" width="400">
</p>

<p align="center">
  <a href="https://github.com/sibali-id/portal/actions"><img src="https://github.com/sibali-id/portal/workflows/tests/badge.svg" alt="Build Status"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Laravel Version"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Sibali.id

Sibali.id is a comprehensive web portal developed for PT. Siap Belajar Indonesia, integrating multiple functionalities including:

- **Landing Page**: Marketing, order processing, newsletter, and articles
- **Learning Management System (LMS)**: Course management, student enrollment, and progress tracking
- **Customer Relationship Management (CRM)**: Customer interactions, lead management, and analytics
- **Internal Tools**: Employee dashboards, reporting, and administrative functions

Built with Laravel 10.50.0, PHP 8.1+, MySQL 8, Redis, and Nginx, this portal provides a robust platform for educational and business operations.

### Architecture Diagram

```
[Landing Page] <-- Vue 3 + Tailwind CSS
    |
    v
[LMS Module] <-- Sanctum API Authentication
    |
    v
[CRM Module] <-- Database-driven
    |
    v
[Internal Tools] <-- Role-based Access Control
```

### Tech Stack

- **Backend**: Laravel 10.50.0 (PHP 8.1+)
- **Frontend**: Vue 3, Vite, Tailwind CSS
- **Database**: MySQL 8 with Redis caching
- **Authentication**: Laravel Sanctum
- **Queue**: Database driver
- **Testing**: PHPUnit with >80% coverage
- **Deployment**: Nginx + PHP-FPM

## Local Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8 or PostgreSQL
- Redis (optional, for caching)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/sibali-id/portal.git
   cd portal
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database configuration**
   - Create a MySQL database
   - Update `.env` with database credentials
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

### Testing

Run the test suite:
```bash
php artisan test
```

For coverage report:
```bash
php artisan test --coverage
```

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Configure production database
- [ ] Set `APP_KEY` from `php artisan key:generate`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Set up queue workers: `php artisan queue:work`
- [ ] Configure Nginx with SSL
- [ ] Set up monitoring and logging

### Troubleshooting

**Common Issues:**

- **Permission errors**: Ensure storage directories are writable
- **Asset compilation fails**: Clear node_modules and reinstall
- **Database connection**: Verify credentials in `.env`
- **Queue not processing**: Check supervisor configuration

**Logs:**
- Application logs: `storage/logs/laravel.log`
- Queue logs: Check supervisor logs
- Nginx logs: `/var/log/nginx/error.log`

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Development Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make changes and add tests
4. Run tests: `php artisan test`
5. Commit with conventional commits
6. Push and create a pull request

## Security

If you discover a security vulnerability, please email security@sibali.id instead of creating an issue.

## License

This project is proprietary software owned by PT. Siap Belajar Indonesia. All rights reserved.

## Contact

- **Project Maintainers**: Dev Team
- **Email**: dev@sibali.id
- **Website**: https://sibali.id
- **Documentation**: [Internal Wiki](https://wiki.sibali.id)
