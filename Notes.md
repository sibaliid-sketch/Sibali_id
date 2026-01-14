# Workspace Specifications for Sibali.id

## Project Overview
This workspace is for the development of a website portal integrating multiple functionalities for PT. Siap Belajar Indonesia (Sibali.id). The portal includes:
- Landingpage (order, marketing, newsletter, articles)
- Learning Management System (LMS)
- Customer Relationship Management (CRM)
- Internal Tools for employees

## Framework and Version Details
- **Laravel Version**: 10.50.0 (confirmed via `php artisan --version`)
- **Laravel Framework Requirement**: ^10.10 (from composer.json)
- **PHP Version Requirement**: ^8.1
- **Application Name**: Laravel (default, configurable via .env)
- **Application Environment**: Production (default, configurable via .env)
- **Debug Mode**: Disabled (default, configurable via .env)
- **Application URL**: http://localhost (default, configurable via .env)
- **Timezone**: UTC
- **Locale**: en (English)
- **Fallback Locale**: en
- **Faker Locale**: en_US

## Dependencies and Packages
### PHP Dependencies (from composer.json)
- **PHP**: ^8.1
- **Laravel Framework**: ^10.10
- **Guzzle HTTP Client**: ^7.2
- **Laravel Sanctum**: ^3.3 (for API authentication)
- **Laravel Tinker**: ^2.8 (for command-line interaction)

### Development Dependencies
- **Faker**: ^1.9.1 (for generating fake data)
- **Laravel Pint**: ^1.0 (for code style fixing)
- **Laravel Sail**: ^1.18 (for local development with Docker)
- **Mockery**: ^1.4.4 (for mocking in tests)
- **Nunomaduro Collision**: ^7.0 (for enhanced error reporting)
- **PHPUnit**: ^10.1 (for testing)
- **Spatie Laravel Ignition**: ^2.0 (for error page improvements)

### Frontend Dependencies (from package.json)
- **Vite**: ^5.0.0 (for build tool and development server)
- **Axios**: ^1.6.4 (for HTTP requests)
- **Laravel Vite Plugin**: ^1.0.0 (for Laravel integration with Vite)

## Database Configuration
- **Default Connection**: MySQL
- **Supported Connections**: SQLite, MySQL, PostgreSQL, SQL Server
- **MySQL Settings**:
  - Host: 127.0.0.1 (default)
  - Port: 3306
  - Charset: utf8mb4
  - Collation: utf8mb4_unicode_ci
  - Strict Mode: Enabled
- **Redis Configuration**: Available for caching and sessions
  - Default Database: 0
  - Cache Database: 1

## Mail Configuration
- **Default Mailer**: SMTP
- **Supported Mailers**: SMTP, SES, Postmark, Mailgun, Sendmail, Log, Array, Failover, Roundrobin
- **SMTP Settings**:
  - Host: smtp.mailgun.org (default)
  - Port: 587
  - Encryption: TLS
- **From Address**: hello@example.com (default, configurable)

## Authentication and Security
- **Authentication**: Laravel Sanctum for API token authentication
- **Encryption Key**: Must be set in .env (APP_KEY)
- **Cipher**: AES-256-CBC
- **Session Driver**: File (default)
- **Cache Driver**: File (default)

## User Types
### Employee Account User Types
The system supports the following user types for employee accounts:
- Basic Staff
- Senior Staff
- Leader
- Supervisor
- Manager
- Header
- Executives

### Customer Account User Types
For customer accounts, the user type assignment depends on student age and education level:
- If student >18 years old or in SMA class 2 and above: Account parents = student
- If student <18 years old or below SMA class 2: Account Parents = CRM, Account student = LMS

## Staff Access & Registration Rules

### Staff Login & Registration
1. **Staff Login**: Akun staff dapat login melalui landing page menggunakan kredensial yang sama dengan user lainnya
2. **Staff Registration**: Staff TIDAK dapat melakukan registrasi mandiri melalui website. Pembuatan akun staff harus dilakukan oleh **Admin Operational** melalui panel admin internal
3. **Staff Onboarding Flow**: Admin Operational → Create Employee Account → Assign Department → Assign Role & Permissions → Send Welcome Email with temporary password

### Department Responsibilities & Access Control

#### IT Department
Bertanggung jawab atas semua aspek teknis dan infrastruktur:
- Performance monitoring & optimization
- Error tracking & debugging
- System analytics & metrics
- Website updates & maintenance
- Database backup & recovery
- Server infrastructure management
- Security monitoring (technical aspects)
- System health checks
- Cache management & optimization
- API performance tuning

**Access**: Full access ke SystemHealthController, PerformanceMonitorController, ErrorTrackerController, DatabaseHealthController, ITMonitoringController, BackupManagementController, PerformanceController

#### Sales & Marketing Department
Bertanggung jawab atas semua konten dan aktivitas marketing:
- Website content management (articles, news, announcements)
- Marketing content creation & publishing
- Promotional content on website
- Newsletter management
- Email marketing campaigns
- Digital marketing activities
- Social media content & scheduling
- SEO optimization
- Campaign analytics
- Lead generation content

**Access**: Full access ke ContentManagementController, DigitalMarketingController, semua marketing-related endpoints, CMS publishing workflow

#### Operations Department (Admin Operational)
Bertanggung jawab atas operasional harian dan customer-facing processes:
- Order processing & management
- Invoice generation & management
- Receipt (kuitansi) creation
- Student enrollment & registration
- Class scheduling & assignment
- Student-to-class assignment
- Student-to-schedule assignment
- Room & facility booking
- Operational checklists
- Customer service coordination
- Staff account creation & management

**Access**: Full access ke OperationalController, FinanceController (invoice/receipt only), student enrollment endpoints, class assignment, scheduling tools, UserManagementController (for staff creation)

#### Finance & Accounting Department
Bertanggung jawab atas aspek keuangan (bukan operasional):
- Financial reporting & analysis
- Payment verification (after operational creates invoice)
- Refund processing
- Financial reconciliation
- Budget management
- Tax compliance
- Accounting records
- Financial audits

**Access**: Full access ke FinanceController (verification, reports, reconciliation), limited access to invoice creation (view only)

#### Academic & Curriculum Department
Bertanggung jawab atas konten akademik dan kurikulum:
- Curriculum development
- Course material creation
- Academic standards & policies
- Teacher training & development
- Learning outcome assessment
- Academic calendar management

**Access**: Full access ke AcademicController, curriculum management, material creation, academic policies

#### Engagement & Retention Department
Bertanggung jawab atas student retention dan engagement:
- Student retention programs
- Churn prevention strategies
- Engagement analytics
- Student feedback analysis
- Community management
- Gamification strategies

**Access**: Full access ke StudentRetentionController, engagement analytics, community management, gamification settings

#### Human Resource Department
Bertanggung jawab atas manajemen SDM:
- Employee lifecycle management
- Leave & attendance management
- Performance reviews
- Payroll coordination (not processing)
- Employee training & development
- HR policies & compliance

**Access**: Full access ke HRController, employee records, leave management, performance reviews

#### Public Relation Department
Bertanggung jawab atas komunikasi eksternal:
- Press releases
- Media relations
- Public announcements
- Crisis communication
- Brand reputation management
- External partnerships communication

**Access**: Limited access to ContentManagementController (announcements only), external communication tools

#### Product Research & Development Department
Bertanggung jawab atas inovasi produk:
- New program development
- Product feature research
- Market analysis
- Competitive analysis
- Product roadmap
- Beta testing coordination

**Access**: Read access to analytics, limited write access to experimental features, product feedback analysis

## Internal Tools Departments
The internal tools are organized by the following departments:
- Operations
- Finance & Accounting
- IT
- Engagement & Retention
- Academic & Curriculum
- Sales & Marketing
- Human Resource
- Public Relation
- Product Research & Development

## File Structure
- **App Directory**: Standard Laravel structure with Controllers, Models, Providers, etc.
- **Config Directory**: Standard Laravel configuration files
- **Database Directory**: Migrations, Seeders, Factories
- **Public Directory**: Web-accessible files (index.php, assets)
- **Resources Directory**: Views (Blade templates), CSS, JS
- **Routes Directory**: API, web, console, channels routes
- **Storage Directory**: Logs, cache, sessions, uploaded files
- **Tests Directory**: Feature and Unit tests

## Development Tools
- **Build Tool**: Vite (configured in vite.config.js)
- **Testing Framework**: PHPUnit
- **Code Style**: Laravel Pint
- **Local Development**: Laravel Sail (Docker-based)
- **Package Manager**: Composer (PHP), npm (Node.js)

## Environment Setup
- **Operating System**: Windows 11
- **Default Shell**: C:\WINDOWS\system32\cmd.exe
- **Home Directory**: C:/Users/Deva Bangsawan
- **Current Working Directory**: c:/laragon/www/Sibali.id
- **Local Development Server**: Laragon (Apache/Nginx, MySQL, PHP)
- **Version Control**: Git
- **IDE**: VS Code with extensions for PHP, Vue, Laravel
- **Package Managers**: Composer (PHP), npm (Node.js)
- **Build Tools**: Vite for frontend assets
- **Testing**: PHPUnit for backend, Vitest for frontend
- **Code Quality**: ESLint, Prettier, PHP CS Fixer, Laravel Pint

## Production Environment
- **Hosting Provider**: Hostinger (www.hostinger.com)
- **Domain**: www.sibali.id
- **Application URL**: https://www.sibali.id
- **Database**:
  - Connection: MySQL
  - Host: (Hostinger MySQL host, e.g., mysql.hostinger.com or as provided in .env)
  - Port: 3306
  - Database: u486134328_sibaliid
  - Username: u486134328_sibali
  - Password: Sibali123!
  - Charset: utf8mb4
  - Collation: utf8mb4_unicode_ci
  - Strict Mode: Enabled
- **Mail Configuration**:
  - Driver: SMTP
  - Host: smtp.gmail.com
  - Port: 587
  - Encryption: TLS
  - Username: sibaliid@gmail.com
  - Password: aide jhnh qmgm gbjy
  - From Address: cs@sibali.id (preferred) or sibaliid@gmail.com
  - From Name: Sibali Support

## Key Configurations
- **Maintenance Mode**: File-based driver
- **Broadcasting**: Disabled (BroadcastServiceProvider commented out)
- **Queue**: Database driver (default)
- **Filesystem**: Local driver (default)
- **Logging**: Single channel (default)

## Migration Status
Existing migrations include:
- Users table
- Password reset tokens
- Failed jobs
- Personal access tokens

## Notes for Development
- Ensure APP_KEY is set in .env file before deployment
- Configure database credentials in .env
- Set up mail configuration for email functionality
- Use Laravel Sail for consistent local development environment
- Follow Laravel conventions for MVC structure
- Utilize Sanctum for API authentication in LMS/CRM features
- Implement proper error handling and logging
- Consider implementing caching for performance optimization
- Ensure responsive design for landing page components
- Plan database schema for LMS (courses, lessons, enrollments) and CRM (customers, interactions)

## Website Color Specifications

The website should use a professional and subdued color palette focusing on shades of red, blue, and white that are not too bright or flashy. The primary colors are:

- **Dark Red**: #8B0000 (Deep red for accents and important elements)

- **Dark Maroon Red**: #800000 (Rich maroon for headers and branding)

- **Dark Blue**: #00008B (Navy blue for primary buttons and links)

- **Light Sky Blue**: #87CEEB (Soft sky blue for backgrounds and secondary elements)

- **White**: #FFFFFF (Clean white for backgrounds and neutral elements)

These colors should be used consistently across the landing page, LMS, CRM, and internal tools to maintain brand coherence. Avoid bright or neon variations to ensure a professional appearance suitable for an educational and business platform.

## Product Specifications

The website features templated products for English learning programs, managed through a MySQL database. These products can be created, edited, deleted, and modified via the UI/UX frontend. The product structure includes the following fields:

- Index Code: Unique identifier for the product entry
- Produk_kode: Product code
- Tingkat_Pendidikan: Education level (e.g., SD/Sederajat, SMP/Sederajat, etc.)
- Layanan: Service type (Privat, Regular, Rumah Belajar, Special Program)
- Program: Program name (Bahasa Inggris, Preparation for IELTS/TOEFL, Program ECLAIR)
- Kelas: Class type (Rumah siswa, Kelas Sibali, etc.)
- Jumlah Pertemuan: Number of meetings
- HPP: Cost price
- Harga Kelas: Class price
- Satuan: Unit (e.g., 1 Bulan)
- Min. Tingkat Pendidikan: Minimum education level
- Maks. Tingkat Pendidikan: Maximum education level
- Desc: Description
- link visual: Visual link (possibly for images or videos)

The products are categorized by education level and service type, with pricing and descriptions tailored to each.

Below is the current product template data:

| Index Code | Produk_kode | Tingkat_Pendidikan | Layanan | Program | Kelas | Jumlah Pertemuan | HPP | Harga Kelas | Satuan | Min. Tingkat Pendidikan | Maks. Tingkat Pendidikan | Desc | link visual |
|------------|-------------|---------------------|---------|---------|-------|------------------|-----|-------------|--------|--------------------------|--------------------------|------|------------|
| 4 | P1-ENG-SD-8 | SD/Sederajat | Privat | Bahasa Inggris | Rumah siswa | 8 | 280000 | 550000 | 1 Bulan | Kelas 3 SD | Kelas 6 SD | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 8 | R1-ENG-SD-12 | SD/Sederajat | Regular | Bahasa Inggris | Kelas Sibali | 12 | 420000 | 350000 | 1 Bulan | Kelas 3 SD | Kelas 6 SD | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 12 | R2-ENG-SD-8 | SD/Sederajat | Rumah Belajar | Bahasa Inggris | Rumah siswa (≥2) | 8 | 280000 | 250000 | 1 Bulan | Kelas 3 SD | Kelas 6 SD | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 17 | P1-ENG-SMP-8 | SMP/Sederajat | Privat | Bahasa Inggris | Rumah siswa | 8 | 280000 | 550000 | 1 Bulan | Kelas 6 SD | Kelas 9 SMP | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 20 | R1-ENG-SMP-8 | SMP/Sederajat | Regular | Bahasa Inggris | Kelas Sibali | 8 | 280000 | 350000 | 1 Bulan | Kelas 6 SD | Kelas 9 SMP | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 24 | R2-ENG-SMP-8 | SMP/Sederajat | Rumah Belajar | Bahasa Inggris | Rumah siswa (≥2) | 8 | 280000 | 250000 | 1 Bulan | Kelas 6 SD | Kelas 9 SMP | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 28 | P1-ENG-SMA-8 | SMA/Sederajat | Privat | Bahasa Inggris | Rumah siswa | 8 | 280000 | 600000 | 1 Bulan | Kelas 9 SMP | Kelas 12 SMA | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 29 | R1-ENG-SMA-8 | SMA/Sederajat | Regular | Bahasa Inggris | Kelas Sibali | 8 | 280000 | 350000 | 1 Bulan | Kelas 9 SMP | Kelas 12 SMA | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 37 | R1-INT-SMA-48 | SMA/Sederajat | Regular | Preparation for IELTS/TOEFL | Kelas Sibali | 48 | 420000 | 650000 | 4 Bulan | Kelas 9 SMP | Kelas 12 SMA | Latihan intensif 4 bulan: Listening, Reading, Writing, Speaking, plus Grammar & Vocabulary support. Termasuk simulasi full test. |  |
| 39 | R2-ENG-SMA-8 | SMA/Sederajat | Rumah Belajar | Bahasa Inggris | Rumah siswa (≥2) | 8 | 280000 | 300000 | 1 Bulan | Kelas 9 SMP | Kelas 12 SMA | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 40 | P1-ENG-MHS-8 | Mahasiswa | Privat | Bahasa Inggris | Rumah siswa | 8 | 280000 | 600000 | 1 Bulan | Semester 1 | Semester Akhir | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 41 | R1-ENG-MHS-8 | Mahasiswa | Regular | Bahasa Inggris | Kelas Sibali | 8 | 280000 | 350000 | 1 Bulan | Semester 1 | Semester Akhir | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 45 | R1-INT-MHS-48 | Mahasiswa | Regular | Preparation for IELTS/TOEFL | Kelas Sibali | 48 | 420000 | 650000 | 4 Bulan | Semester 1 | Semester Akhir | Latihan intensif 4 bulan: Listening, Reading, Writing, Speaking, plus Grammar & Vocabulary support. Termasuk simulasi full test. |  |
| 46 | R2-ENG-MHS-8 | Mahasiswa | Rumah Belajar | Bahasa Inggris | Rumah siswa (≥2) | 8 | 280000 | 300000 | 1 Bulan | Semester 1 | Semester Akhir | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 47 | P1-ENG-UM-8 | Umum | Privat | Bahasa Inggris | Rumah siswa | 8 | 280000 | 650000 | 1 Bulan | — | — | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 48 | R1-ENG-UM-8 | Umum | Regular | Bahasa Inggris | Kelas Sibali | 8 | 280000 | 350000 | 1 Bulan | — | — | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 51 | R1-INT-UM-48 | Umum | Regular | Preparation for IELTS/TOEFL | Kelas Sibali | 48 | 420000 | 650000 | 4 Bulan | — | — | Latihan intensif 4 bulan: Listening, Reading, Writing, Speaking, plus Grammar & Vocabulary support. Termasuk simulasi full test. |  |
| 52 | R2-ENG-UM-8 | Umum | Rumah Belajar | Bahasa Inggris | Rumah siswa (≥2) | 8 | 280000 | 350000 | 1 Bulan | — | — | Belajar sesuai level CEFR, fokus pada grammar, kosakata, komunikasi, dan keterampilan 4 skills (listening, speaking, reading, writing). |  |
| 1 | SP-ECL-SMHS-8 | Mahasiswa | Special Program | Program ECLAIR - A0 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 2 | SP-ECL-SMHS-8 | Mahasiswa | Special Program | Program ECLAIR - A1 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 3 | SP-ECL-SMHS-8 | Mahasiswa | Special Program | Program ECLAIR - A2 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 4 | SP-ECL-SMHS-8 | Mahasiswa | Special Program | Program ECLAIR - B1 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 5 | SP-ECL-SMHS-8 | Mahasiswa | Special Program | Program ECLAIR - B2 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 6 | SP-ECL-SMHS-8 | Mahasiswa | Special Program | Program ECLAIR - C1 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 7 | SP-ECL-SMHS-8 | Mahasiswa | Special Program | Program ECLAIR - C2 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 8 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - A0 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 9 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - A0 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 10 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - A1 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 11 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - A2 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 12 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - B1 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 13 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - B2 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 14 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - C1 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |
| 15 | SP-ECL-SMHS-8 | SMA/Sederajat | Special Program | Program ECLAIR - C2 | Kelas Sibali | 8 | 280000 | 90000 | 1 Bulan | Kelas 11 SMA | Mahasiswa | Program pengembangan soft skills untuk SMA & mahasiswa: public speaking, kepemimpinan, & komunikasi efektif. |  |

## Complete File List and Descriptions

Below is a comprehensive list of all files and directories in the workspace, organized by directory. Each entry includes a description, purpose, function, and interconnections with other files.

### bootstrap/ Directory (Application Bootstrap)
- **bootstrap/app.php**: ✅ CREATED - Laravel bootstrapper: inisialisasi aplikasi dan service container. Purpose: Menyiapkan environment awal aplikasi, register core service providers, memuat konfigurasi env, dan mengembalikan instance Application untuk dipakai kernel/console. Function: bootstrap/ — entry point untuk runtime; dipanggil oleh public/index.php (HTTP) dan artisan (CLI). Specification: Membaca autoload Composer dan memanggil Dotenv untuk memuat variabel lingkungan (.env), membuat instance Illuminate\Foundation\Application (container), mendaftarkan base bindings (Exception handler, Console kernel contracts), mendaftarkan (merge) konfigurasi minimal, binding alias, dan provider core yang wajib (routing, events, config), menyiapkan environment spesifik (APP_ENV, APP_DEBUG) dan konfigurasi error/reporting sesuai env, men-return aplikasi yang siap dipass ke Http\Kernel atau Console\Kernel. Catatan operasional: keep bootstrap/app.php lean — expensive work (config loading, service registration) harus di-cache di produksi (php artisan config:cache, route:cache) agar boot cepat. Interconnected with: public/index.php, artisan, app/Http/Kernel.php, app/Console/Kernel.php.

- **bootstrap/cache/packages.php**: ✅ CREATED - Manifest cache paket (package discovery) yang dihasilkan otomatis oleh framework/composer. Purpose: Mempercepat proses discovery dan registrasi package pihak-ketiga saat bootstrap, mengurangi I/O dan refleksi runtime. Function: bootstrap/cache/ — dihasilkan oleh proses package discovery (composer scripts / artisan). Specification: Auto-generated: mencantumkan paket yang melakukan package discovery dan provider/alias yang harus didaftarkan. Invalidasi: otomatis regenerasi saat composer install/composer update atau saat menjalankan perintah discovery. Jangan edit manual (ubah via composer.json / package discovery config). Prod best-practice: termasuk dalam build artifact agar runtime tidak melakukan discovery ulang. Interconnected with: composer.json, composer.lock, vendor packages.

- **bootstrap/cache/services.php**: ✅ CREATED - Cache konfigurasi service/service providers yang ter-compile untuk mempercepat resolusi service. Purpose: Mengoptimalkan proses boot container dengan menghindari pemrosesan ulang service registration pada setiap request. Function: bootstrap/cache/ — dihasilkan oleh artisan (mis. saat melakukan optimizations). Specification: Berisi mapping provider → provided services dan compiled arrays untuk resolusi cepat. Dihasilkan oleh perintah optimisasi (framework tooling). Invalidate/regen pada perubahan konfigurasi/service provider (php artisan config:cache / deploy pipeline step). Production note: harus menjadi bagian dari proses build/deploy, bukan di-generate di runtime. Interconnected with: config files, service providers, optimization commands.

### app/Console/ Directory (Console Commands)
- **app/Console/Kernel.php**: ✅ UPDATED - Console kernel: registrasi commands & scheduler. Purpose: Menjadwalkan tugas berkala (cron) dan mendaftarkan custom Artisan commands; menjadi central point untuk background/maintenance tasks. Function: app/Console — dipanggil oleh artisan CLI dan schedule:run cron entry. Specification: commands() atau $commands untuk mendaftarkan class command custom di app/Console/Commands. schedule(Schedule $schedule) untuk mendefinisikan job terjadwal: frequency, timezone, chaining, withoutOverlapping, onOneServer, runInBackground. Integrasi: schedule sebaiknya dijalankan lewat cron * * * * * php /path/artisan schedule:run atau via orchestrator; gunakan ->appendOutputTo()/->emailOutputTo() untuk reporting. Failure handling: set retry/catch logic di command, gunakan notifikasi (Slack/email) on failure; metrics untuk schedule run success/fail. Deployment notes: pastikan job yang memodifikasi state dijalankan hanya pada leader/instance (use onOneServer) dan batasi concurrency untuk menghindari race. Interconnected with: artisan, cron jobs, app/Console/Commands, storage/logs.

### app/Http/ Directory (HTTP Layer)
- **app/Http/Kernel.php**: ✅ UPDATED - HTTP kernel: stack middleware global & route; mengorkestrasi lifecycle request → response. Purpose: Menyusun middleware global, middleware groups (web, api), dan route middleware sehingga setiap request melewati lapisan keamanan, throttle, dan transformasi yang tepat. Function: app/Http — core file yang dipanggil oleh public/index.php untuk setiap HTTP request. Specification: Struktur: $middleware (global middleware), $middlewareGroups (web, api), $routeMiddleware (named middleware untuk routes). Register middleware penting: TrustProxies, HandleCors, ValidatePostSize, TrimStrings, ConvertEmptyStringsToNull, EncryptCookies, StartSession, ShareErrorsFromSession, VerifyCsrfToken (web group), ThrottleRequests (api). Priority: kernel mengatur $middlewarePriority untuk memastikan middleware dengan dependensi dijalankan dalam urutan aman (mis. session before auth). Terminate middleware: beberapa middleware punya terminate() untuk tugas pasca-response (logging, queue flush) — pastikan kernel memanggilnya. Custom middleware: cara register (file class → $routeMiddleware → panggil di route/controller), best-practice untuk testing and idempotency. Security notes: tempat tepat untuk central rate-limiting, IP allow/deny, input sanitization, dan binding global exception handling hooks. Interconnected with: routes, middleware classes, public/index.php, security systems.

- **app/Http/Middleware/DataEncryptionMiddleware.php**: ✅ CREATED - Middleware untuk enkripsi data sensitif saat in-flight di layer aplikasi dan untuk runtime field-level protection. Purpose: Melindungi payload PII/financial sebelum ditulis ke logs, cache, atau diteruskan ke subsystems yang tidak berwenang; tangani field-level encryption/decryption sesuai konteks (per-tenant, per-field). Function: app/Middleware/Security/DataEncryptionMiddleware.php → services/encryption/EncryptionService (lib wrapper KMS), config config/encryption.php, key metadata di KMS (AWS KMS / GCP KMS / Vault). Interconnected with: DataEncryptionService, HTTP requests/responses, sensitive data fields.

- **app/Http/Middleware/FirewallManager.php**: ✅ CREATED - Orkestrator firewall berlapis untuk aplikasi (single entry-point orchestration). Purpose: Menyatukan dan mengatur semua lapisan proteksi jaringan & aplikasi: IP filtering, rate-limiting global/per-endpoint, geo-blocking, bot-detection orchestration, WAF ruleset toggles, and emergency blocklists. Function: app/Middleware/Security/FirewallManager.php → memanggil services/security/FirewallService, konfigurasi di config/firewall.php, data store rules di infra/redis/firewall:* & db.security.blocklists. Interconnected with: FirewallService, all 20 firewall layers, security logging.

### app/Http/Controllers/Admin/ Directory (Admin Controllers)
- **app/Http/Controllers/Admin/AcademicController.php**: ✅ CREATED - Manage academic entities: courses, classes, curricula, schedules, teacher assignments. Purpose: CRUD courses & classes; manage timetables; assign & revoke teachers; expose endpoints for enrollment eligibility. Function: Calls Services\AcademicService → Repositories\AcademicRepository. Endpoints: GET /admin/academics, POST /admin/academics, PUT /admin/academics/{id}, DELETE /admin/academics/{id}, POST /admin/academics/{id}/assign-teacher. Interconnected with: AcademicService, AcademicRepository, validation rules.

- **app/Http/Controllers/Admin/ContentManagementController.php**: ✅ CREATED - CMS for landing pages, blog posts, LMS content, media management. Purpose: Create/edit/publish content; versioning; media uploads; manage publishing workflow & drafts. Function: Calls Services\CmsService → Repositories\ContentRepository & MediaService. Endpoints: content CRUD, publish/unpublish, schedule publish, media upload with signed URLs, revisions, rollback. Interconnected with: CmsService, ContentRepository, MediaService.

- **app/Http/Controllers/Admin/FinanceController.php**: ✅ CREATED - Finance dashboard and actions: invoices, refunds, reconciliation, financial reports. Purpose: Process invoices, manage refunds, generate financial reports, trigger reconciliation jobs. Function: Calls Services\FinanceService → Repositories\PaymentsRepository & Reporting. Endpoints: list invoices, generate invoice, mark paid, initiate refund, manual adjustments, reconciliation, reports. Interconnected with: FinanceService, PaymentsRepository, payment gateways.

- **app/Http/Controllers/Admin/HRController.php**: ✅ CREATED - HR administrative operations: employee records, leave management, payroll triggers. Purpose: Manage employee data lifecycle; handle leave approvals; trigger payroll processes. Function: Calls Services\HRService → Repositories\EmployeeRepository. Endpoints: CRUD employees, approve leave, generate payroll run, assign roles. Interconnected with: HRService, EmployeeRepository, payroll microservice.

- **app/Http/Controllers/Admin/DigitalMarketingController.php**: ✅ CREATED - Marketing campaigns & asset orchestration: campaign scheduling, push channels. Purpose: Schedule campaigns, manage creatives, integrate with social & analytics APIs. Function: Calls Services\MarketingService → Integrations/{Facebook,Google,Email}. Endpoints: create campaign, schedule, preview, push, pause/resume. Interconnected with: MarketingService, external APIs (Facebook, Google, Email).

- **app/Http/Controllers/Admin/OperationalController.php**: ✅ CREATED - Operations tasks: rooms, schedules, facility bookings, operational checklists. Purpose: Manage rooms/resources, operational tasks, auto-detect conflicts and assign staff. Function: Calls Services\OpsService → Repositories/RoomsRepository. Endpoints: manage rooms, book resources, assign operational tasks. Interconnected with: OpsService, RoomsRepository.

- **app/Http/Controllers/Admin/BusinessDevelopmentController.php**: ✅ CREATED - B2B & partnership flows: corporate offers, bulk enroll, contract lifecycle. Purpose: Handle partner onboarding, bulk operations, contract document management. Function: Calls Services\B2BService → Repositories/PartnerRepository. Endpoints: partner CRUD, bulk-enroll API, contract upload & e-sign status. Interconnected with: B2BService, PartnerRepository.

- **app/Http/Controllers/Admin/StudentRetentionController.php**: ✅ CREATED - Retention & churn workflows: detect at-risk students and trigger interventions. Purpose: Score students, trigger interventions (emails, calls), monitor uplift. Function: Calls Services\RetentionService → Engagement/Retention Engine. Endpoints: at-risk students, student score, trigger intervention, intervention history, A/B test. Interconnected with: RetentionService, engagement scoring.

- **app/Http/Controllers/Admin/UserManagementController.php**: ✅ CREATED - User admin & role assignment: create/manage users, reset passwords, impersonation. Purpose: Manage user lifecycle, enforce RBAC, support impersonation for troubleshooting. Function: Calls Services\UserService → Repositories\UserRepository & AuthService. Endpoints: list/search users, update roles, impersonate, force-logout, reset password. Interconnected with: UserService, UserRepository, AuthService.

- **app/Http/Controllers/Admin/PaymentVerificationController.php**: ✅ CREATED - Manual & automated payment verification: validate proofs, adjust payment statuses. Purpose: Validate payment receipts, mark transactions settled, flag fraud. Function: Calls Services\PaymentService → Repositories/PaymentsRepository. Endpoints: list unverified payments, upload proof, verify, dispute, force-settle. Interconnected with: PaymentService, PaymentsRepository, fraud detection.

- **app/Http/Controllers/Admin/ReportController.php**: ✅ CREATED - Generate reports & exports: financial, academic, engagement. Purpose: Produce scheduled & ad-hoc exports (CSV/PDF), visualize KPIs. Function: Calls Services\ReportingService → Reporting/ETL. Endpoints: trigger report generation, list scheduled reports, download artifact, schedule reports. Interconnected with: ReportingService, ETL processes.

- **app/Http/Controllers/Admin/SettingController.php**: ✅ CREATED - Global application settings UI: company data, feature toggles. Purpose: Update global config, propagate changes, invalidate caches. Function: Calls Services\ConfigService → Repositories\ConfigRepository. Endpoints: get/set settings, preview feature-flag changes, toggle flags with rollout rules. Interconnected with: ConfigService, ConfigRepository, cache invalidation.

- **app/Http/Controllers/Admin/DashboardWidgetController.php**: ✅ CREATED - Dashboard widget configuration: add/remove/customize admin dashboard panels. Purpose: Customize widgets, apply permission scoping, manage data sources. Function: Calls Services\DashboardService. Endpoints: CRUD widgets, set data sources, permission-based visibility, manual refresh. Interconnected with: DashboardService, widget data sources.

- **app/Http/Controllers/Admin/SystemLogController.php**: ✅ CREATED - Access system logs & audit trails: search/filter logs by severity/user. Purpose: Provide searchable log UI for ops & audits; support export for forensics. Function: Calls Services\AuditService → LogStore (ELK/Cloud). Endpoints: paginated search with filters, export logs. Interconnected with: AuditService, LogStore, PII redaction.

- **app/Http/Controllers/Admin/PerformanceMonitorController.php**: ✅ CREATED - App performance monitoring UI: traces, perf metrics. Purpose: Surface perf metrics, traces, baseline comparisons, historical charts. Function: Integrates with APM provider (internal or third-party). Endpoints: dashboard, traces, baseline comparison, alerts, create incident. Interconnected with: APM provider, monitoring systems.

- **app/Http/Controllers/Admin/ErrorTrackerController.php**: ✅ CREATED - Error tracking & triage: list, assign, annotate errors. Purpose: Triage runtime errors; assign to owners; track status & annotate remediation steps. Function: Calls Services\ErrorService → external Sentry-like integration. Endpoints: list errors, show error, assign, resolve, add note, attach commit. Interconnected with: ErrorService, Sentry integration, git blame.

- **app/Http/Controllers/Admin/SecurityAuditController.php**: ✅ CREATED - Security audit orchestration: trigger scans, review findings. Purpose: Run SAST/DAST, review findings, create remediation tickets. Function: Calls Services\SecurityService → security scanners. Endpoints: list scans, trigger scan, view results, acknowledge findings, escalate, retest. Interconnected with: SecurityService, SAST/DAST scanners.

- **app/Http/Controllers/Admin/DatabaseHealthController.php**: ✅ CREATED - Database health & maintenance UI: replication, slow queries, index hints. Purpose: Show DB replication status, slow-query lists, index suggestions and partition hints. Function: Calls Services\DBAdminService → DB admin tools. Endpoints: status, slow queries, index suggestions, partition hints, schedule maintenance. Interconnected with: DBAdminService, database monitoring.

- **app/Http/Controllers/Admin/ITMonitoringController.php**: ✅ CREATED - IT infra monitoring actions: manage alerts, perform service checks. Purpose: Manage infra alerts, run health checks, trigger restart/incident. Function: Calls Services\InfraService. Endpoints: alerts, acknowledge, mute/unmute, health check, restart service, create incident. Interconnected with: InfraService, orchestrator, incident management.

- **app/Http/Controllers/Admin/SystemHealthController.php**: ✅ CREATED - Aggregated system health dashboard: uptime, queue lengths, error rates. Purpose: Provide single-pane-of-glass for system health and quick triage. Function: Calls Services\MonitoringService. Endpoints: dashboard, services, oncall contacts, runbooks. Interconnected with: MonitoringService, APM, DB health, backup status.

- **app/Http/Controllers/Admin/BackupManagementController.php**: ✅ CREATED - Backup orchestration UI: trigger backup/restore jobs, view status. Purpose: Allow admins to trigger backups/restores, view retention, and manage policies. Function: Calls Services\BackupService → scripts in deploy/disaster-recovery. Endpoints: list backups, trigger backup, restore, view manifest, manage policies. Interconnected with: BackupService, disaster recovery scripts.

- **app/Http/Controllers/Admin/PerformanceController.php**: ✅ CREATED - Performance tuning UI: execute recommended optimizations. Purpose: Surface perf recommendations (cache warming, index rebuild) and allow controlled execution. Function: Calls Services\OptimizationService. Endpoints: recommendations, cache warming, rebuild indexes, profiling, metrics. Interconnected with: OptimizationService, maintenance windows.

## Complete File List and Descriptions

Below is a comprehensive list of all files and directories in the workspace, organized by directory. Each entry includes a description, purpose, function, and interconnections with other files.

### .scripts/ Directory (Backup and Monitoring Scripts)
- **.scripts/backup/incremental-backup.sh**: Incremental backup script for Sibali.id. Purpose: Jobs incremental untuk efisiensi storage dan menurunkan RTO pada restore. Function: Mengurangi storage & network usage; memungkinkan point-in-time recovery lebih granular. Modes: DB incremental via WAL shipping / binlog capture; file deltas via rsync --link-dest or snapshot diffs. Consistency: ensure base full-backup exists; maintain sequence numbers and manifest for applying increments in order.- Safety: automatic verification of continuity (no missing segments), alert on gap. Interconnected with: Backup system, database, and file storage.
- **.scripts/backup/restore.sh**: Restore script for Sibali.id. Purpose: Helper restore non-emergency untuk dev/QA atau partial restores. Function: Memudahkan pemulihan data untuk testing dan selektif restore tanpa proses DR penuh. Features: select backup by date/tag → validate checksum → restore DB to target instance (non-prod or isolated restore cluster) → restore filestore partial paths. Post-restore: run migrations in compatibility mode, reindex if necessary, run sanity queries. Safeguards: never restore into production primary unless --force with explicit approval. Interconnected with: Backup system, database, and file storage.
- **.scripts/backup/verify-backup.sh**: Backup verification script for Sibali.id. Purpose: Verifikasi integritas backup rutin untuk memastikan restorability. Function: Memastikan backup yang disimpan dapat direstore dan valid sehingga mengurangi false confidence. Checks: verify checksums, test-mount archives, optionally perform dry-run restore to ephemeral instance and run quick-row-count checks. Reporting: publish results to dashboard and open ticket on anomalies. Interconnected with: Backup system and monitoring dashboard.
- **.scripts/monitoring/system-health.sh**: System health monitoring script for Sibali.id. Purpose: Pengumpulan ringkasan kesehatan sistem untuk laporan harian. Function: Kirim metrik ringkasan harian ke tim operasi & leadership; early-warning untuk trend negatif. Metrics: disk usage, memory, CPU, service up/down, last restart times. Output: JSON & human-friendly email/Slack summary; archived daily snapshot for capacity planning. Interconnected with: System monitoring, email services, and Slack notifications.
- **.scripts/monitoring/performance-check.sh**: Performance check script for Sibali.id. Purpose: Snapshots metrik performa periodik untuk tracking regresi. Function: Menjaga baseline performa; detect regresi performa cepat. Capture: response time percentiles, DB slow queries list, throughput, queue latencies. Baseline compare: compare with historical baseline and raise alert if thresholds breached. Artifact: store perf snapshot and flamegraph link (if available). Interconnected with: Performance monitoring, database, and application metrics.
- **.scripts/monitoring/security-audit.sh**: Security audit runner for Sibali.id. Purpose: Runner audit keamanan berkala: vuln scans & config drift checks. Function: Otomatisasi security checks dan deteksi drift/regresi konfigurasi. Scans: container image vuln scan, dependency scan, infra-as-code drift against policy, search for stale credentials in configs. Reports: structured SARIF/CSV output, create tickets for critical findings, integrate with SSO for ownership. Interconnected with: Security scanning tools, ticketing system, SSO integration.
- **.scripts/utilities/optimize-images.sh**: Image optimization pipeline for Sibali.id. Purpose: Converts, resizes, and optimizes images for web delivery. Function: Processes images in storage/app/public/images → optimized versions with WebP/AVIF, multiple sizes, quality presets. Features: batch processing, manifest generation, CDN invalidation, logging. Interconnected with: Image storage, CDN, performance optimization.
- **.scripts/utilities/clear-cache.sh**: Cache clearing utility for Sibali.id. Purpose: Invalidates Redis cache, purges CDN, resets PHP opcache. Function: Clears Laravel caches, Redis keys by pattern, PHP opcache, CDN cache. Features: dry-run mode, confirmation prompts, logging. Interconnected with: Cache systems, CDN, PHP runtime.
- **.scripts/utilities/generate-sitemap.sh**: Sitemap generation script for Sibali.id. Purpose: Creates XML sitemaps for SEO. Function: Scans routes/models to generate sitemap.xml with priorities, change frequencies. Features: multi-file support, submit to search engines, incremental updates. Interconnected with: Routes, SEO, search engines.
- **.scripts/setup/setup-roles.sh**: Role setup script for Sibali.id. Purpose: Creates RBAC roles, permissions, seeds admin user. Function: Sets up employee/customer roles, assigns permissions, creates admin user. Features: idempotent, exports RBAC policy, logging. Interconnected with: Authentication, authorization, user management.
- **.scripts/setup/backup-bookings.sh**: Booking data backup script for Sibali.id. Purpose: Exports reservation/booking data for reporting/archival. Function: Exports bookings to CSV/JSON, incremental backups, encryption, retention. Features: date range filtering, checksums, cleanup old backups. Interconnected with: Database, backup system, reporting.

### Root Directory Files
- **.editorconfig**: Configuration file for code editors to maintain consistent coding styles (indentation, line endings). Purpose: Ensures uniform code formatting across different editors. Function: Defines rules for spaces, tabs, and encoding. Interconnected with: All source code files for consistent formatting.
- **.env**: File konfigurasi environment runtime aplikasi (sensitif). Purpose: Menyimpan konfigurasi dinamis & sensitif untuk runtime: koneksi DB, cache, mailer, queue, API keys, feature flags. Function: Dibaca oleh Laravel Config loader (config/*.php) pada bootstrap; tidak di-commit ke VCS. Specification: Contoh variabel yang wajib disediakan: APP_ENV, APP_KEY, APP_DEBUG, APP_URL, DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, REDIS_HOST, REDIS_PASSWORD, MAIL_DRIVER, GOOGLE_RECAPTCHA_SECRET, PAYMENT_GATEWAY_*. Keamanan: file hanya accessible server side, permissions 600, rotasi kunci prosedur. Interconnected with: Laravel Config loader and sensitive runtime configurations.
- **.env.example**: Template non-sensitif untuk .env. Purpose: Referensi standar untuk setup environment baru (dev/staging/prod). Function: Disertakan di repository sebagai contoh saat cloning. Specification: Harus mencantumkan semua variabel yang diperlukan tanpa nilai sensitif: APP_ENV=local, placeholders untuk DB, REDIS, MAIL, PAYMENT. Developer harus copy ke .env dan mengisi nilai. Sinkronisasi periodik jika variabel baru ditambahkan. Interconnected with: .env file and environment setup.
- **.gitattributes**: Git configuration for handling file types and line endings. Purpose: Controls how Git treats files during commits and merges. Function: Specifies text/binary attributes for files. Interconnected with: Git repository management.
- **.gitignore**: Specifies files and directories Git should ignore. Purpose: Prevents unnecessary files from being tracked. Function: Excludes build artifacts, logs, and sensitive data. Interconnected with: All project files to maintain clean repository.
- **.htaccess**: Apache configuration file for URL rewriting and security. Purpose: Enables clean URLs and protects sensitive files. Function: Routes requests to index.php, sets security headers, blocks access to sensitive files. Interconnected with: Laravel routing, web server configuration, and security middleware.
- **.eslintrc.js**: ESLint configuration for JavaScript/TypeScript linting. Purpose: Enforces code quality and consistency in frontend code. Function: Defines linting rules for Vue 3, TypeScript, and Prettier integration. Interconnected with: resources/js files, package.json scripts, and code quality tools.
- **.prettierrc**: Prettier configuration for code formatting. Purpose: Ensures consistent code formatting across the project. Function: Defines formatting rules for JavaScript, Vue, and other supported files. Interconnected with: .eslintrc.js, package.json, and frontend assets.
- **.styleci.yml**: StyleCI configuration for PHP code style checking. Purpose: Automates PHP code style fixes and checks. Function: Defines PSR-12 standards and custom fixers for Laravel projects. Interconnected with: PHP files, GitHub Actions, and code quality CI/CD.
- **artisan**: Laravel's command-line interface script. Purpose: Execute Laravel commands for development tasks. Function: Runs migrations, generates code, clears cache, etc. Interconnected with: Laravel framework components, database, and application logic.
- **composer.json**: PHP dependency management file. Purpose: Defines project dependencies and scripts. Function: Manages PHP packages and autoloading. Interconnected with: composer.lock, vendor directory, and PHP classes.
- **composer.lock**: Locks exact versions of dependencies. Purpose: Ensures consistent installations across environments. Function: Records resolved dependency versions. Interconnected with: composer.json and vendor directory.
- **package.json**: Node.js dependency and script management. Purpose: Defines frontend dependencies and build scripts. Function: Manages JavaScript packages and npm scripts. Interconnected with: package-lock.json, node_modules, and frontend assets.
- **phpunit.xml**: PHPUnit configuration for testing. Purpose: Defines test suites and settings. Function: Configures test execution environment. Interconnected with: Tests directory and testing framework.
- **README.md**: Project documentation file. Purpose: Provides project overview and setup instructions. Function: Contains information about the project. Interconnected with: Project structure and development guidelines.
- **vite.config.js**: Vite build tool configuration. Purpose: Configures frontend build process. Function: Defines build settings and plugins. Interconnected with: package.json, resources directory, and public assets.
- **docker-compose.yml**: Orkestrasi container utama (base compose file) — definisi layanan bersama yang digunakan oleh semua environment. Purpose: Menyediakan single-source-of-truth untuk layanan inti: web (Nginx), app (PHP-FPM), db (MySQL), cache (Redis), queue worker, job scheduler, admin tools. Digunakan sebagai basis untuk docker-compose.override.yml / env-specific compose files. Function: Docker Compose (version 3.8 atau 3.9) — file di root repo; referensi ke Dockerfile di docker/php/ atau official images. Interconnected with: docker-compose.prod.yml, docker-compose.dev.yml, and Docker environment configurations.
- **docker-compose.prod.yml**: Konfigurasi compose untuk production (override/extends base). Purpose: Deploy production-ready stack: hardened images, no bind mounts, optimized caching, secrets handling, logging & monitoring integrations. Function: Docker Compose (prod override file) — used together with docker-compose.yml in CI/CD or docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d. Interconnected with: docker-compose.yml and production Docker environment.
- **docker-compose.dev.yml**: Konfigurasi compose untuk development / local staging — overrides enabling hot-reload, debugging, tooling. Purpose: Developer convenience: bind mounts for live edit, Xdebug, node dev server (Vite), maildev, seeders auto-run, easier logs & port mappings. Function: Docker Compose dev override used with base: docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build. Interconnected with: docker-compose.yml and development Docker environment.

### deploy/ Directory (Deployment Configurations)
- **deploy/scripts/b2b-data-backup.sh**: B2B data backup script. Purpose: Handles backup of B2B specific data. Function: Automated backup for business development data. Interconnected with: Backup system and B2B modules.
- **deploy/scripts/backup.sh**: General backup script. Purpose: Comprehensive backup of database and files. Function: Creates encrypted backups with rotation. Interconnected with: Database and file storage.
- **deploy/scripts/optimize.sh**: System optimization script. Purpose: Performance optimization tasks. Function: Database optimization, cache clearing, system tuning. Interconnected with: Performance monitoring and maintenance.
- **deploy/scripts/restore.sh**: Restore script. Purpose: Restores from backups. Function: Automated restore process with verification. Interconnected with: Backup system and disaster recovery.
- **deploy/disaster-recovery/recovery-plan.md**: Dokumen kebijakan dan panduan langkah demi langkah untuk pemulihan bencana (DRP): skenario yang dicakup, diagram arsitektur DR, urutan tindakan, checklists per-severity, dan lampiran runbook singkat. Purpose: Memberikan prosedur darurat lengkap bagi tim SRE/Security/On-call untuk memulihkan layanan sesuai target RTO/RPO; menyamakan ekspektasi stakeholder; compliance evidence. Function: deploy/disaster-recovery (policy docs); ops/runbooks/dr (step-by-step playbooks); oncall/contacts (contact matrix). Specification: - RTO / RPO target per-service (contoh: auth svc RTO 15m / RPO 1h; analytics RTO 4h / RPO 24h).- Failover hierarchy: service-level (active-standby) → region-level → multi-region failover; per-layer ownership & run order.- Activation criteria (health-score thresholds, outage detection rules) dan siapa berwenang meng-declare DR (SRE lead / Incident Commander).- Roles & Responsibilities: Incident Commander, SRE Operator, DB Lead, Network Lead, Comms, Legal; tugas tiap role di fase Declared / Recovery / Post-mortem.- Contact matrix: pager, phone, escalation times, backup contacts, external vendor contacts (cloud, payment gateways).- Communication plan: internal timeline, status cadence, public status page templates, customer comms playbook, compliance notifications.- Verification & acceptance criteria untuk "service recovered" (smoke tests, synthetic transactions, SLA checks).- Dependencies & prerequisites: access keys, standby infra credentials, DR runbook locations, approved rollback plans.- Post-incident: post-mortem template, RCA owner, remediation ticketing and timeline, policy update cadence. Interconnected with: Emergency scripts and disaster recovery procedures.
- **deploy/disaster-recovery/emergency-scripts/restore-database.sh**: Script otomatisasi untuk restore database dari snapshot/backup terenkripsi, termasuk verifikasi checksum, integritas data, dan langkah post-restore (migrate, reindex, sanity checks). Purpose: Mempercepat dan mengurangi kesalahan manual saat perlu mengembalikan state DB ke titik aman; menyediakan langkah verifikasi untuk mencegah corrupt restores. Function: deploy/disaster-recovery/emergency-scripts ; infra/db/backups (backup registry); infra/bin (helper libs). Specification: - Prasyarat: izin (kms decrypt role), backup manifest path, minimal maintenance window, connection string ke standby/restore cluster.- Restore flow: stop incoming writes (if applicable) → provision restore instance → fetch encrypted backup → decrypt via KMS → stream restore → apply WAL/journal up to target timestamp (if point-in-time) or latest snapshot.- Integritas: calculate & verify checksums (pre/post), validate row counts against manifest, run schema checksum tool.- Migration: if backup versi berbeda, run compatible DB migrations in "dry-run" mode then apply; include backward-compatible migration strategy notes.- Post-restore sanity: run smoke queries, run health metrics (replication lag, index health), run application-level test suite (sanity ops tests).- Safety: dry-run flag, dry-run-only mode, confirmation prompts for destructive ops; idempotency—safe re-run behavior.- Observability & logging: emit structured logs to central logging, upload artifacts (restore-report.json) to object storage, expose progress metric for monitoring.- Rollback & cleanup: how to revert restore attempt, retention of temporary restore instances, security of restored data (rotate credentials). Interconnected with: Database backup systems and disaster recovery procedures.
- **deploy/disaster-recovery/emergency-scripts/failover-activation.sh**: Script otomatisasi untuk mengalihkan traffic ke DR/standby site: DNS cutover, load-balancer reconfiguration, promote standby DB to read-write (with safety checks), dan orchestration runbook hooks. Purpose: Memungkinkan failover yang cepat, terukur, dan dapat diulang untuk mengurangi downtime dan memenuhi RTO; mengautomasi langkah-rentan-mistake. Function: deploy/disaster-recovery/emergency-scripts ; infra provisioning repos infra/terraform ; LB config in infra/lb. Specification: - Pre-checks: confirm declared DR state, confirm standby infra readiness, confirm data sync status (replication lag < threshold), validate certificates and secrets present.- DNS & LB steps: lower DNS TTL pre-approved; perform DNS switch (change A/CNAME or update Geo-DNS), update LB target pools, wait for health-check pass; include atomic vs staged strategies.- DB promotion: verify replication state, promote replica to primary using orchestrator (PG: pg_ctl/repmgr, MySQL: gtid workflow) with explicit safety gates; ensure read-write endpoints published only after promotion success.- Traffic shaping: gradually shift traffic (canary percentages), monitor error rates and latency; automatic rollback triggers if error thresholds exceeded.- Verification: run synthetic transactions across critical flows (auth, checkout, webhook delivery); check logs for error spikes; validate downstream integrations (payments, third-party APIs).- Audit & approvals: require dual-ack (SRE lead + Product/Business) for region-level failover; log all actions for post-incident review.- Idempotency & dry-run: support --dry-run to preview changes; safe re-run semantics.- Security & access: restrict script execution to bastion with MFA; use short-lived tokens; do not embed secrets. Interconnected with: Load balancers, DNS systems, and database failover procedures.
- **deploy/disaster-recovery/emergency-scripts/emergency-maintenance.sh**: Script untuk menjalankan maintenance darurat: mengalihkan layanan ke mode maintenance, mem-preserve log & traces, melindungi data sensitif saat isolasi, dan men-trigger customer-facing maintenance page. Purpose: Mengisolasi layanan yang bermasalah dengan cepat untuk mencegah propagasi kegagalan dan memberi waktu pemulihan; mempertahankan bukti forensics. Function: deploy/disaster-recovery/emergency-scripts ; platform/maintenance ; statuspage/templates. Specification: - Activation: trigger maintenance mode via feature-flag toggle or LB rule; set 503 maintenance page with Retry-After header; coordinate with comms template for status page.- Log & trace preservation: snapshot current log streams, export traces/span data for window of incident, seal those artifacts to immutable storage for forensic analysis.- State protection: disable background jobs that may mutate critical state (batch processors, cron jobs) and pause external integrations (webhooks) with retry metadata.- Data handling: ensure in-flight transactions are drained or marked; preserve incoming requests queue; ensure backups run before risky ops.- Notifications: alert on-call, create incident in tracker with pre-filled diagnostics; update status page with standardized severity language.- Recovery exit: stepwise exit maintenance (enable read-only, smoke tests, enable writes), validate telemetry, then remove maintenance flag; include rollback steps if anomalies found.- Safety & audit: require approval chain for disabling maintenance, log operator identity; include timestamped artifact creation for compliance. Interconnected with: Maintenance systems, logging infrastructure, and status page services.
- **deploy/configs/nginx/sibali.conf**: Production Nginx vhost for sibali.id. Purpose: Main site configuration with strict security headers, rate limiting, TLS termination, and PHP-FPM upstream. Function: Nginx conf - Strict security headers (HSTS, CSP, X-Frame-Options), upstream to PHP-FPM pool, rate limiting (limit_req), TLS termination, HTTP->HTTPS redirect, gzip/brotli, strict file serving rules (deny .env). Interconnected with: Web server, PHP-FPM, SSL certificates.
- **deploy/configs/nginx/mobile-optimized.conf**: Mobile-optimized vhost. Purpose: Mobile-specific caching & headers. Function: Nginx conf - Vary User-Agent, separate cache TTLs, serve different image sizes via rewrite rules, enable prerender or AMP endpoints if used. Interconnected with: Mobile frontend and caching layers.
- **deploy/configs/nginx/performance-optimized.conf**: Perf tuned vhost. Purpose: Edge caching & fast static serving. Function: Nginx conf - Long cache headers for fingerprinted assets, sendfile on, tcp_nopush, gzip & brotli, upstream keepalive, header for CDN. Interconnected with: CDN, static assets, performance optimization.
- **deploy/configs/supervisor/laravel-worker.conf**: Supervisor config – workers. Purpose: Manage Laravel queue workers. Function: Supervisor conf - Program config: command php artisan queue:work redis --sleep=3 --tries=3, numprocs per queue (high/default/low), autostart true, autorestart true, stdout/stderr logs, user www-data. Use Horizon if installed with supervisor for process management. Interconnected with: Queue system, Redis, Laravel workers.
- **deploy/configs/supervisor/optimization-worker.conf**: Supervisor config – optimization. Purpose: Background optimization tasks. Function: Supervisor conf - Command for optimization workers (sitemap generation, cache warmers), low concurrency, schedule via queue scheduler, restart policies. Interconnected with: Optimization services, cache management.
- **deploy/configs/supervisor/digital-marketing-worker.conf**: Supervisor config – marketing. Purpose: Campaign & social automation workers. Function: Supervisor conf - Rate-limited connectors to external APIs, per-worker rate limit environment variables, retriable behavior with exponential backoff. Interconnected with: Marketing automation, external APIs.
- **deploy/configs/supervisor/b2b-worker.conf**: Supervisor config – B2B. Purpose: B2B specific long-running tasks. Function: Supervisor conf - Configure long-running batch jobs with higher memory limits, careful retry count, logging to dedicated files. Interconnected with: B2B modules, batch processing.
- **deploy/configs/supervisor/engagement-worker.conf**: Supervisor config – engagement. Purpose: Engagement scoring & notifications. Function: Supervisor conf - Worker tuned for near-real-time scoring, priority queue engagement-priority, metrics for queue lengths. Interconnected with: Engagement tracking, notification system.
- **deploy/configs/cron-jobs/daily-backup.cron**: Daily backup cron job. Purpose: Scheduled daily backups. Function: Cron entries - nightly archival, daily backup. Interconnected with: Backup scripts, cron system.
- **deploy/configs/cron-jobs/hourly-analytics.cron**: Hourly analytics cron job. Purpose: Scheduled hourly analytics. Function: Cron entries - hourly analytics. Interconnected with: Analytics processing, reporting.
- **deploy/configs/cron-jobs/every-15-min-queue-monitor.cron**: Every 15 minutes queue monitor. Purpose: Queue health monitoring. Function: Cron entries - every-15-min queue monitor. Interconnected with: Queue monitoring, health checks.
- **deploy/configs/cron-jobs/weekly-security-scan.cron**: Weekly security scan. Purpose: Scheduled security scans. Function: Cron entries - weekly security scan. Interconnected with: Security scanning tools, compliance.
- **deploy/configs/php.ini**: Production php.ini for deploy. Purpose: Fine-tune runtime for prod. Function: PHP runtime config - Settings: memory_limit=512M, max_execution_time=60, opcache settings tuned (opcache.memory_consumption, opcache.validate_timestamps=0), disable display_errors, enable log_errors, timezone set. Interconnected with: PHP runtime, performance tuning.
- **deploy/configs/mysql.cnf**: MySQL tuning config. Purpose: Performance & replication settings. Function: MySQL config - Key settings: innodb_buffer_pool_size, innodb_log_file_size, max_connections, slow_query_log enabled, binlog_format ROW for replication, sync_binlog, tmp_table_size. Use different tuned profiles for OLTP/OLAP. Interconnected with: Database performance, replication.
- **deploy/configs/redis.conf**: Prod Redis conf. Purpose: Persistence and security tuning. Function: Redis config - Settings: requirepass or ACL, maxmemory, maxmemory-policy (allkeys-lru or volatile-lru), snapshot / AOF config per durability requirements, protected-mode, tcp-backlog tuning. Interconnected with: Caching, session storage, queue backend.
- **deploy/configs/cache-config/redis.conf**: Cache-specific redis config. Purpose: Separate cache pool config. Function: Redis config - Use separate DB index for cache vs session; lower persistence for cache DB; optimized maxmemory and eviction policy for cache tier. Interconnected with: Cache layer, Redis clustering.
- **deploy/configs/cache-config/memcached.conf**: Memcached config (if used). Purpose: Alternative cache layer. Function: Memcached config - Pool sizing, slab allocations, eviction policy, consistent hashing config for memcached clusters. Interconnected with: Alternative caching, distributed cache.

### app/ Directory (Application Logic)
- **app/Console/Kernel.php**: Console command scheduler. Purpose: Defines scheduled tasks and commands. Function: Registers Artisan commands and schedules. Interconnected with: Artisan commands and cron jobs.
- **app/Exceptions/Handler.php**: Global exception handler. Purpose: Manages application errors and exceptions. Function: Renders error pages and logs exceptions. Interconnected with: All application code for error handling.
- **app/Http/Controllers/Controller.php**: Base controller class. Purpose: Provides common functionality for controllers. Function: Defines shared methods for HTTP controllers. Interconnected with: All specific controllers.
- **app/Http/Kernel.php**: HTTP middleware stack. Purpose: Defines global and route-specific middleware. Function: Processes HTTP requests through middleware. Interconnected with: Routes and middleware classes.
- **app/Http/Middleware/**: Directory containing middleware classes.
  - **Authenticate.php**: Authentication middleware. Purpose: Ensures user authentication. Function: Redirects unauthenticated users. Interconnected with: Auth system and protected routes.
  - **EncryptCookies.php**: Cookie encryption middleware. Purpose: Encrypts cookies for security. Function: Handles cookie encryption/decryption. Interconnected with: Session management.
  - **PreventRequestsDuringMaintenance.php**: Maintenance mode middleware. Purpose: Blocks requests during maintenance. Function: Returns maintenance page. Interconnected with: Maintenance mode configuration.
  - **RedirectIfAuthenticated.php**: Redirect authenticated users. Purpose: Prevents authenticated users from accessing login. Function: Redirects to dashboard. Interconnected with: Auth routes.
  - **TrimStrings.php**: Input trimming middleware. Purpose: Trims whitespace from input. Function: Sanitizes user input. Interconnected with: Form requests.
  - **TrustHosts.php**: Host trust middleware. Purpose: Validates trusted hosts. Function: Prevents host header attacks. Interconnected with: Server configuration.
  - **TrustProxies.php**: Proxy trust middleware. Purpose: Handles proxy headers. Function: Corrects IP detection behind proxies. Interconnected with: Load balancers.
  - **ValidateSignature.php**: URL signature validation. Purpose: Validates signed URLs. Function: Prevents URL tampering. Interconnected with: Signed routes.
  - **VerifyCsrfToken.php**: CSRF protection middleware. Purpose: Prevents cross-site request forgery. Function: Validates CSRF tokens. Interconnected with: Forms and AJAX requests.
- **app/Models/User.php**: User model. Purpose: Represents user entity. Function: Defines user attributes and relationships. Interconnected with: Database migrations, authentication, and user-related logic.
- **app/Providers/**: Service providers directory.
  - **AppServiceProvider.php**: Main application service provider. Purpose: Registers application services. Function: Binds services to container. Interconnected with: Laravel service container.
  - **AuthServiceProvider.php**: Authentication service provider. Purpose: Defines auth policies and gates. Function: Registers authorization logic. Interconnected with: Models and auth system.
  - **BroadcastServiceProvider.php**: Broadcasting service provider. Purpose: Enables real-time broadcasting. Function: Registers broadcast routes. Interconnected with: WebSockets and real-time features.
  - **EventServiceProvider.php**: Event service provider. Purpose: Registers event listeners. Function: Maps events to listeners. Interconnected with: Event classes and listeners.
  - **RouteServiceProvider.php**: Route service provider. Purpose: Defines route model bindings. Function: Loads routes and configures routing. Interconnected with: Routes directory.

### bootstrap/ Directory (Application Bootstrap)
- **bootstrap/app.php**: Application bootstrap file. Purpose: Creates and configures the application. Function: Sets up service container and middleware. Interconnected with: All application components.
- **bootstrap/cache/**: Cached bootstrap files. Purpose: Improves performance by caching. Function: Stores compiled configuration. Interconnected with: Configuration files.

### config/ Directory (Configuration Files)
- **config/app.php**: Main application configuration. Purpose: Defines app settings. Function: Configures name, env, debug, etc. Interconnected with: .env file and application behavior.
- **config/auth.php**: Authentication configuration. Purpose: Defines auth guards and providers. Function: Configures login/logout behavior. Interconnected with: User model and auth middleware.
- **config/broadcasting.php**: Broadcasting configuration. Purpose: Defines broadcast connections. Function: Configures real-time messaging. Interconnected with: WebSocket drivers.
- **config/cache.php**: Cache configuration. Purpose: Defines cache stores. Function: Configures caching drivers. Interconnected with: Cache usage in application.
- **config/cors.php**: CORS configuration. Purpose: Defines cross-origin policies. Function: Handles cross-origin requests. Interconnected with: API endpoints.
- **config/database.php**: Database configuration. Purpose: Defines database connections. Function: Configures DB drivers and settings. Interconnected with: Models and migrations.
- **config/filesystems.php**: Filesystem configuration. Purpose: Defines file storage disks. Function: Configures local/cloud storage. Interconnected with: File uploads and storage.
- **config/hashing.php**: Hashing configuration. Purpose: Defines password hashing. Function: Configures bcrypt settings. Interconnected with: User authentication.
- **config/logging.php**: Logging configuration. Purpose: Defines log channels. Function: Configures error logging. Interconnected with: Exception handler.
- **config/mail.php**: Mail configuration. Purpose: Defines mail drivers. Function: Configures email sending. Interconnected with: Notification classes.
- **config/queue.php**: Queue configuration. Purpose: Defines queue connections. Function: Configures job processing. Interconnected with: Job classes.
- **config/sanctum.php**: Sanctum configuration. Purpose: Defines API authentication. Function: Configures token settings. Interconnected with: API routes.
- **config/services.php**: Third-party services config. Purpose: Defines API keys. Function: Configures external services. Interconnected with: Service integrations.
- **config/session.php**: Session configuration. Purpose: Defines session drivers. Function: Configures user sessions. Interconnected with: Auth and user state.
- **config/view.php**: View configuration. Purpose: Defines view settings. Function: Configures template engine. Interconnected with: Blade templates.
- **config/firewall.php**: 20-Layer firewall configuration (app-level policy). Purpose: Enforce layered security rules (IP filter, rate-limiting, bot detection, input sanitization). Function: Security Layer / Middleware (app/Security/FirewallLayers, FirewallManager). Specification: Keys: layers (array of 20 layers with enabled flag); ip_whitelist/ip_blacklist (CIDR list); rate_limits (route patterns → req/min, burst); ua_policy (allowed/blocked regex); geo_blocking (enabled + allowed_countries); bot_detection (behavior thresholds, captcha_threshold); sql_injection rules (enabled, sanitizers); xss_filters (enabled, exceptions); csrf_protection (enabled, token_header); audit_logging (level, log_channel). Health: metrics per layer. Security: rules loaded from secure source, supports hot-reload, policy versioning, fallback deny. Interconnected with: Security middleware and firewall services.
- **config/database-auto.php**: Auto table engine for dynamic schema creation. Purpose: Drive AutoTableCreator for JSON-driven dynamic table creation. Function: DB Automation service (App\Services\AutoTableCreator). Specification: Keys: enabled (bool), schemas_path (dir), default_engine (InnoDB), index_strategy (auto). Interconnected with: Database migrations and auto table creation services.
- **config/email-templates.php**: Email template registry. Purpose: Standardize transactional & marketing emails. Function: Notification System / Mailables (App\Notifications, MailTemplateService). Specification: Keys: default_locale, templates (map key→file/db_id), versioning (enable), storage (file). Interconnected with: Mail services and notification templates.
- **config/notification.php**: Notification rules & routing. Purpose: Configure smart alerts (real-time + predictive). Function: Notification Engine (App\Services\NotificationService, Listeners). Specification: Keys: channels (in_app,email,sms,push,whatsapp), default_channel_priority, channel_config (per-channel credentials/timeouts), throttling per-user, predictive_rules (engagement scoring thresholds → triggers), snooze/mute options, retry_policy, audit_channel (log). Support templating engine and user preference override. Interconnected with: Notification services and user preferences.
- **config/backup.php**: Backup policy & targets. Purpose: Automated, encrypted backups for DB & assets. Function: Backup Service / cron jobs (App\Console\Commands\BackupDatabaseCommand). Specification: Keys: enabled, schedule (cron), targets (db,uploads,s3,configs), retention (hot/warm/cold days), encryption (true, method=AES-256-GCM), destination (s3/bucket/endpoint), verify_after_backup (checksum), notifications_on_failure, parallelism. Include encrypted key rotation and restore test cadence. Interconnected with: Backup commands and storage services.
- **config/security.php**: Centralized security policy. Purpose: Single source for encryption, audit, and security controls. Function: Security Core (App\Services\SecurityAuditService, DataEncryptionService). Specification: Keys: encryption_method (AES-256-GCM), kms_provider (local/hashicorp/aws), key_rotation_days, audit_log_retention, pii_masking_rules, session_hardening options, password_policy (min_length, complexity), 2fa (enabled, providers), sso config. Enforce strict access to config via secrets manager. Interconnected with: Security services and encryption modules.
- **config/lms.php**: LMS business rules & thresholds. Purpose: Configure academic logic: class sizes, grading schema, certificate rules. Function: LMS Module (App\Modules\LMS, controllers, schedulers). Specification: Keys: max_class_size, grading_scale (A-F or numeric thresholds), passing_threshold, attendance_required_pct, certificate_template_id, late_submission_policy (penalty_per_day), auto_grade_rules (for MCQ), language_lab config (recording retention), practice_limits. Include feature flags for adaptive learning and gamification hooks. Interconnected with: LMS modules and academic services.
- **config/business_development.php**: B2B logic & contract rules. Purpose: Configure business development strategies and contract management. Function: Business Development Module (App\Modules\BusinessDevelopment). Specification: Keys: b2b_logic (corporate_accounts, partnerships, sales_pipeline), contract_rules (templates, auto_renewal, termination_clauses, compliance), expansion_strategies (market_penetration, product_diversification, strategic_alliances). Interconnected with: CRM and contract management services.
- **config/captcha.php**: CAPTCHA & bot protection config. Purpose: Configure CAPTCHA and bot detection mechanisms. Function: Security Module (App\Services\CaptchaService). Specification: Keys: enabled, provider (recaptcha, hcaptcha), forms (login, registration), bot_protection (honeypot, time_based, behavioral), logging. Interconnected with: Security middleware and form validation.
- **config/company.php**: Company metadata & branding. Purpose: Define company information and branding elements. Function: Branding Service (App\Services\BrandingService). Specification: Keys: name, address, phone, email, tax_id, timezone, logo_paths, brand_colors, terms_url, privacy_url. Interconnected with: Views, emails, and branding elements.
- **config/content_management.php**: CMS rules & moderation. Purpose: Configure content management and moderation policies. Function: CMS Module (App\Modules\CMS). Specification: Keys: cms_rules (content_types, workflows, versioning), moderation (auto_moderation, manual_review, reporting), seo_optimization, multimedia_handling. Interconnected with: Content controllers and moderation services.
- **config/crm.php**: CRM pipeline & retention config. Purpose: Configure CRM processes and customer retention strategies. Function: CRM Module (App\Modules\CRM). Specification: Keys: lead_sources, scoring_rules, auto_assign, sla_response_hours, follow_up_sequences, b2b_workflow, integration, duplication_rules, log_decisions. Interconnected with: CRM controllers and customer management services.
- **config/digital_marketing.php**: Marketing automation & content rules. Purpose: Configure digital marketing campaigns and content strategies. Function: Marketing Module (App\Modules\Marketing). Specification: Keys: automation (email_campaigns, social_media, sms_campaigns), content_rules (seo_optimization, content_types, publishing_workflow), analytics, ad_campaigns, lead_generation. Interconnected with: Marketing services and analytics integrations.
- **config/engagement.php**: Engagement tracking & retention thresholds. Purpose: Configure user engagement metrics and retention strategies. Function: Engagement Module (App\Services\EngagementService). Specification: Keys: tracking (events, user_journey, session_tracking), retention_thresholds, personalization, feedback_loops. Interconnected with: Analytics and user management services.
- **config/gamification.php**: Gamification rules & catalogs. Purpose: Configure gamification elements and reward systems. Function: Gamification Module (App\Modules\Gamification). Specification: Keys: rules (points_system, badges, levels, leaderboards), catalogs (rewards, achievements, challenges), engagement_boosters, notifications. Interconnected with: User progress and reward systems.
- **config/mobile.php**: Mobile & PWA settings. Purpose: Configure mobile application and PWA features. Function: Mobile Module (App\Modules\Mobile). Specification: Keys: pwa_settings (manifest, service_worker), responsive_design, app_features (push_notifications, offline_mode, biometric_auth), performance. Interconnected with: Frontend assets and mobile services.
- **config/optimization.php**: Performance tuning & optimization flags. Purpose: Configure performance optimization settings. Function: Optimization Service (App\Services\OptimizationService). Specification: Keys: caching, compression, minification, image_optimization, database, cdn, performance_flags. Interconnected with: Caching and performance modules.
- **config/qris.php**: QRIS payment integration parameters. Purpose: Configure QRIS payment gateway integration. Function: Payment Module (App\Modules\Payment). Specification: Keys: integration_parameters, payment_methods, transaction_settings, validation_rules, logging. Interconnected with: Payment services and QRIS gateway.
- **config/social_media.php**: Social media connectors & policies. Purpose: Configure social media integrations and policies. Function: Social Media Module (App\Modules\SocialMedia). Specification: Keys: connectors (facebook, twitter, instagram, linkedin), policies (auto_posting, engagement_tracking, content_moderation, privacy_settings), analytics. Interconnected with: Social media APIs and content services.
- **config/storage.php**: Storage strategy & lifecycle rules. Purpose: Configure file storage and lifecycle management. Function: Storage Service (App\Services\StorageService). Specification: Keys: default_disk, disks (local, public, s3), lifecycle_rules, compression, cdn_integration. Interconnected with: Filesystem and storage modules.
- **config/cache-optimization.php**: Konfigurasi strategi caching lanjutan. Purpose: Mengatur policy cache per-module (LMS, CRM, Dashboard). Function: Dipanggil oleh cache layer & middleware (App\Services\CacheManager, middlewares). Specification: Keys: modules (lms/crm/dashboard/marketing → tier mapping), tiers (hot/warm/cold → redis-connections), ttl_defaults per module/key, cache_warming_endpoints, stale_while_revalidate settings, invalidation_events, max_cached_items, cache_key_prefix per env. Implementasi: event-driven invalidation + scheduled warmers. Interconnected with: Cache layer and middleware.

### .github/ Directory (GitHub Integration)
- **.github/workflows/ci-cd.yml**: CI/CD pipeline untuk otomatisasi deployment. Purpose: Mengatur workflow GitHub Actions untuk testing, building, dan deployment. Function: Jalankan test pada multiple PHP versions, security scan, deployment staging/production dengan approval manual. Specification: Jobs: test (phpunit, eslint, build), security-scan (composer audit, npm audit, trivy), deploy-staging (auto), deploy-production (manual approval). Triggers: push ke main/develop, PR. Interconnected with: GitHub Actions, secrets, dan environment deployment.
- **.github/workflows/security-scan.yml**: Security scanning workflow. Purpose: Jalankan security scans secara terjadwal dan on-demand. Function: Scan filesystem, config, dan DAST dengan OWASP ZAP, upload SARIF ke GitHub Security tab. Specification: Tools: Trivy (fs/config), SonarQube, Snyk (PHP/JS), OWASP ZAP. Triggers: push/PR, weekly cron. Interconnected with: GitHub Security tab dan security monitoring.
- **.github/workflows/performance-test.yml**: Performance testing workflow. Purpose: Jalankan load testing dan performance monitoring. Function: Setup k6 untuk load testing, monitor response times, throughput. Specification: Tools: k6, Artillery. Triggers: PR ke main/develop. Interconnected with: Performance monitoring dan CI/CD pipeline.
- **.github/workflows/backup-automation.yml**: Automated backup workflow. Purpose: Jalankan backup database dan storage secara terjadwal. Function: Backup DB ke encrypted files, upload ke S3, rotate old backups. Specification: Schedule: daily 2 AM, SSH ke backup server, GPG encryption, S3 storage, checksum verification. Interconnected with: Backup infrastructure dan storage services.
- **.github/workflows/role-based-deploy.yml**: Role-based deployment controls. Purpose: Kontrol deployment berdasarkan team membership. Function: Check GitHub team membership untuk staging/production access. Specification: Jobs: check-permissions (ops team untuk staging, infra team untuk production), deploy dengan role validation. Interconnected with: GitHub Teams dan deployment pipeline.
- **.github/PULL_REQUEST_TEMPLATE.md**: Template untuk pull request. Purpose: Standardisasi format PR dengan checklist lengkap. Function: Panduan untuk reviewer dengan sections: description, related issues, type of change, checklist, testing, security, performance, migration notes. Interconnected with: GitHub PR process dan code review standards.

### .vscode/ Directory (VS Code Configuration)
- **.vscode/extensions.json**: Recommended VS Code extensions. Purpose: Sarankan extensions untuk development environment yang konsisten. Function: Daftar extensions seperti PHP Intelephense, Prettier, ESLint. Interconnected with: VS Code workspace dan development tools.
- **.vscode/settings.json**: VS Code workspace settings. Purpose: Konfigurasi editor behavior untuk project ini. Function: Settings untuk PHP validation, formatting, file associations, search excludes, testing configuration. Interconnected with: VS Code editor dan project files.
- **.vscode/launch.json**: Debug configuration untuk VS Code. Purpose: Setup debugging untuk PHP dan JavaScript. Function: Launch configurations untuk Xdebug, Node.js debugging. Interconnected with: VS Code debugger dan development environment.

### .husky/ Directory (Git Hooks)
- **.husky/pre-commit**: Pre-commit hook untuk code quality. Purpose: Jalankan checks sebelum commit. Function: PHP CS Fixer, ESLint fix, unit tests, syntax check. Interconnected with: Git hooks dan code quality tools.
- **.husky/commit-msg**: Commit message validation hook. Purpose: Validasi format commit message. Function: Check conventional commit format. Interconnected with: Git hooks dan commit standards.
- **.husky/pre-push**: Pre-push hook untuk comprehensive checks. Purpose: Jalankan tests dan analysis sebelum push. Function: Unit tests, static analysis, linting, migration checks, environment file validation. Interconnected with: Git hooks dan CI/CD pipeline.

### database/ Directory (Database Related)
- **database/.gitignore**: Ignores database files. Purpose: Prevents DB files from version control. Function: Excludes SQLite databases. Interconnected with: .gitignore.
- **database/factories/UserFactory.php**: User model factory. Purpose: Generates fake user data. Function: Creates test data. Interconnected with: User model and seeders.
- **database/migrations/**: Database migrations.
  - **2014_10_12_000000_create_users_table.php**: Creates users table. Purpose: Defines user schema. Function: Sets up user fields. Interconnected with: User model.
  - **2014_10_12_100000_create_password_reset_tokens_table.php**: Creates password reset table. Purpose: Handles password resets. Function: Stores reset tokens. Interconnected with: Auth system.
  - **2019_08_19_000000_create_failed_jobs_table.php**: Creates failed jobs table. Purpose: Tracks failed queue jobs. Function: Stores failed job data. Interconnected with: Queue system.
  - **2019_12_14_000001_create_personal_access_tokens_table.php**: Creates personal access tokens table. Purpose: Stores API tokens. Function: Manages Sanctum tokens. Interconnected with: Sanctum authentication.
- **database/seeders/DatabaseSeeder.php**: Main database seeder. Purpose: Runs all seeders. Function: Populates database with initial data. Interconnected with: Other seeders and factories.

### public/ Directory (Web-Accessible Files)
- **public/.htaccess**: Apache configuration file. Purpose: Enables URL rewriting. Function: Routes requests to index.php. Interconnected with: Laravel routing and web server.
- **public/favicon.ico**: Website favicon. Purpose: Displays site icon in browser. Function: Browser tab icon. Interconnected with: HTML head section.
- **public/index.php**: Application entry point. Purpose: Bootstraps Laravel application. Function: Loads framework and handles requests. Interconnected with: bootstrap/app.php and all application logic.
- **public/robots.txt**: Search engine crawling instructions. Purpose: Controls web crawler access. Function: Defines crawl permissions. Interconnected with: SEO and search engines.

### resources/ Directory (Frontend Assets)
- **resources/css/app.css**: Main stylesheet. Purpose: Defines application styles. Function: Contains CSS rules. Interconnected with: Views and Vite build process.
- **resources/js/app.js**: Main JavaScript file. Purpose: Defines frontend logic. Function: Contains application JavaScript. Interconnected with: Views and Vite build process.
- **resources/js/bootstrap.js**: JavaScript initialization. Purpose: Sets up frontend framework. Function: Initializes Axios and other libraries. Interconnected with: app.js and package.json.
- **resources/views/welcome.blade.php**: Welcome page template. Purpose: Displays home page. Function: Blade template for landing page. Interconnected with: routes/web.php and controllers.

### routes/ Directory (Route Definitions)
- **routes/api.php**: API route definitions. Purpose: Defines REST API endpoints. Function: Maps URLs to API controllers. Interconnected with: Controllers and Sanctum middleware.
- **routes/channels.php**: Broadcast channel definitions. Purpose: Defines real-time channels. Function: Authorizes broadcast channels. Interconnected with: Broadcasting system.
- **routes/console.php**: Console command definitions. Purpose: Defines Artisan commands. Function: Maps commands to handlers. Interconnected with: Console/Kernel.php.
- **routes/web.php**: Web route definitions. Purpose: Defines web page routes. Function: Maps URLs to web controllers. Interconnected with: Controllers and views.

### storage/ Directory (File Storage)
- **storage/app/.gitignore**: Ignores user-uploaded files. Purpose: Prevents tracking uploads. Function: Excludes user files from Git. Interconnected with: Filesystem configuration.
- **storage/framework/.gitignore**: Ignores framework cache. Purpose: Prevents tracking cache files. Function: Excludes cache from Git. Interconnected with: Cache configuration.
- **storage/framework/cache/.gitignore**: Ignores cache files. Purpose: Prevents tracking compiled cache. Function: Excludes cache data. Interconnected with: Cache system.
- **storage/framework/sessions/.gitignore**: Ignores session files. Purpose: Prevents tracking user sessions. Function: Excludes session data. Interconnected with: Session configuration.
- **storage/framework/testing/.gitignore**: Ignores testing cache. Purpose: Prevents tracking test cache. Function: Excludes test artifacts. Interconnected with: Testing framework.
- **storage/framework/views/.gitignore**: Ignores compiled views. Purpose: Prevents tracking compiled templates. Function: Excludes Blade cache. Interconnected with: View configuration.
- **storage/logs/.gitignore**: Ignores log files. Purpose: Prevents tracking application logs. Function: Excludes log data. Interconnected with: Logging configuration.

### tests/ Directory (Test Files)
- **tests/CreatesApplication.php**: Test application trait. Purpose: Creates test application instance. Function: Provides testing utilities. Interconnected with: TestCase.php and PHPUnit.
- **tests/TestCase.php**: Base test class. Purpose: Defines test setup and helpers. Function: Base class for all tests. Interconnected with: PHPUnit configuration.
- **tests/Feature/ExampleTest.php**: Example feature test. Purpose: Demonstrates feature testing. Function: Tests application features. Interconnected with: Routes and controllers.
- **tests/Unit/ExampleTest.php**: Example unit test. Purpose: Demonstrates unit testing. Function: Tests individual components. Interconnected with: Classes and methods.

### app/Http/Controllers/Auth/ Directory (Authentication Controllers)
- **app/Http/Controllers/Auth/EnhancedLoginController.php**: Login lanjutan (role-aware). Purpose: Multi-guard login, device recognition, conditional challenges (step-up). Function: Integrates device fingerprinting, risk assessment, CAPTCHA, 2FA. Interconnected with: DeviceFingerprintingService, RiskAssessmentService, UserDevice model.
- **app/Http/Controllers/Auth/ForgotPasswordController.php**: Validasi reset password. Purpose: Ensure contact exists and rate-limit. Function: Issue reset tokens & email links. Interconnected with: Password Broker, email templates.
- **app/Http/Controllers/Auth/ResetPasswordController.php**: Validasi reset password. Purpose: Validate token, set new password, revoke sessions. Function: Process password reset with security measures. Interconnected with: Password Broker, session management.
- **app/Http/Controllers/Auth/CaptchaController.php**: Validasi CAPTCHA. Purpose: For API or submission flows. Function: Verify captcha tokens with adaptive difficulty. Interconnected with: CaptchaService, rate limiting.
- **app/Http/Controllers/Auth/TwoFactorAuthController.php**: Validasi token 2FA. Purpose: 2FA verification. Function: Enroll/verify TOTP, SMS, Push, backup codes. Interconnected with: TwoFactorAuthService, user devices.
- **app/Http/Controllers/Auth/SocialLoginController.php**: OAuth social login. Purpose: Login/register via Google/Facebook/Apple. Function: Uses Socialite-like adapters. Interconnected with: SocialLoginService, UserSocialAccount model.
- **app/Http/Controllers/Auth/RoleRedirectController.php**: Post-login role redirect. Purpose: Centralize redirect logic by role/department. Function: Uses config/roles.php mapping and staffLevel. Interconnected with: User roles, department config.

### app/Services/Auth/ Directory (Authentication Services)
- **app/Services/Auth/DeviceFingerprintingService.php**: Device fingerprinting. Purpose: Generate device fingerprints, detect platform/browser. Function: Manage trusted devices, fingerprint storage. Interconnected with: UserDevice model, risk assessment.
- **app/Services/Auth/RiskAssessmentService.php**: Risk assessment. Purpose: IP risk assessment, device trust evaluation. Function: Login pattern analysis, risk scoring. Interconnected with: DeviceFingerprintingService, firewall.
- **app/Services/Auth/SocialLoginService.php**: Social login service. Purpose: Manage social account linking and user creation. Function: Find or create users from social providers. Interconnected with: UserSocialAccount model, Socialite.

### app/Models/User/ Directory (User Models)
- **app/Models/User/UserDevice.php**: Device model. Purpose: Store user device information. Function: Relationships with users, device trust flags. Interconnected with: DeviceFingerprintingService, authentication.
- **app/Models/User/UserSocialAccount.php**: Social account model. Purpose: Store social login accounts. Function: Link users to social providers. Interconnected with: SocialLoginService, authentication.

### database/migrations/ Directory (Additional Migrations)
- **database/migrations/2024_01_01_000028_create_user_devices_table.php**: Create user devices table. Purpose: Store device fingerprints and trust data. Function: Migration for user_devices table. Interconnected with: UserDevice model.
- **database/migrations/2024_01_01_000029_create_user_social_accounts_table.php**: Create user social accounts table. Purpose: Store social login provider data. Function: Migration for user_social_accounts table. Interconnected with: UserSocialAccount model.
