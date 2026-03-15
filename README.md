# Email Migration to S3 - Laravel Application

Laravel application that migrates historical email data from a PostgreSQL database to Amazon S3 (or compatible services like MinIO).

## Overview

This application solves the problem of managing large email tables that have grown to millions of rows. By offloading email HTML bodies and attachments to S3.

### Key Features

- **Scalable Architecture**: Batch processing with configurable chunk sizes to handle large dat sets.
- **Progress Tracking**: Real-time progress reporting during migration
- **Data Verification**: Built-in verification tools to ensure migration integrity
- **Docker Ready**: Complete Docker/Docker Compose setup for local development and testing
- **Testing**: Unit and feature tests using PHPUnit

## System Architecture

```
┌──────────────────────────────────────────────────────────┐
│                    Email Migration Flow                  │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  Artisan Command (EmailsMigrateToS3)                     │
│         ↓                                                │
│  EmailMigrationService (Orchestration)                   │
│         ↓                                                │
│  S3UploadService (AWS S3 Interaction)                    │
│         ↓                                                │
│  MinIO / Amazon S3                                       │
│         ↓                                                │
│  Database Updates (PostgreSQL)                           │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

## Technology Stack

- **Framework**: Laravel 8.x
- **Database**: PostgreSQL 15
- **Container**: Docker & Docker Compose
- **Object Storage**: MinIO (local) / Amazon S3 (production)
- **Testing**: PHPUnit
- **Web Server**: Nginx
- **PHP**: 8.0 FPM

## Installation & Setup

### Clone the Repository

```bash
git clone email-migration.bundle email-migration-cli 
cd email-migration-cli
```

### Configure Environment Variables

The `.env.example` file is already configured with default values. Copy it to `.env` and review the settings.

### Build and Start Docker Containers

```bash
docker-compose up -d
```

This command will:
- Build the PHP FPM container
- Start Nginx web server (port 80)
- Initialize PostgreSQL database (port 5432)
- Launch MinIO S3-compatible storage (ports 9000, 9001)
- Launch pgAdmin database management tool (port 5050)

### Install dependencies

```bash
docker-compose exec app composer install
```

### Generate Application Key

```bash
docker-compose exec app php artisan key:generate
```

### 6. Run Database Migrations

```bash
docker-compose exec app php artisan migrate
```

### Seed the Database with Sample Data

```bash
docker-compose exec app php artisan db:seed
```

This will create:
- 10,000 fake file records
- 100,000 fake email records (with 10KB+ HTML bodies)

**Note**: The seeding process may take 10-30 minutes depending on your system. You can modify the count in `database/seeders/EmailSeeder.php`.

### Access Services

- **Laravel Application**: http://localhost
- **MinIO Console**: http://localhost:9001 (username: `minioadmin`, password: `minioadmin`)
- **pgAdmin**: http://localhost:5050 (email: `admin@example.com`, password: `admin123`)
- **PostgreSQL**: localhost:5432 (username: `postgres`, password: `postgres`, database: `email_migration`)

## Running the Migration

### Basic Migration

```bash
docker-compose exec app php artisan emails:migrate-to-s3
```

By default, this will prompt for confirmation before starting.

### Force Migration (Skip Confirmation)

```bash
docker-compose exec app php artisan emails:migrate-to-s3 --force
```

### Custom Batch Size

```bash
docker-compose exec app php artisan emails:migrate-to-s3 --force --batch-size=500
```

Batch size refers to how many emails are processed per iteration. Larger batches are faster but use more memory.

### Verify Migration After Completion

```bash
docker-compose exec app php artisan emails:migrate-to-s3 --force --verify
```

The `--verify` flag checks that all uploaded files exist in S3.

## Project Structure

```
email-migration-cli/
├── app/
│   ├── Console/Commands/
│   │   └── EmailsMigrateToS3.php         # Main Artisan command
│   ├── Models/
│   │   ├── Email.php                     # Email model
│   │   └── File.php                      # File model
│   ├── Repositories
│   │   ├── EmailRepository.php           # Email repository
│   │   └── FileRepository.php            # File respository
│   └── Services/
│       ├── EmailMigrationService.php      # Migration orchestration
│       ├── S3UploadService.php            # S3 operations
│       └── RetryableS3UploadService.php   # Retry logic
├── database/
│   ├── factories/
│   │   ├── EmailFactory.php               # Email factory for seeding
│   │   └── FileFactory.php                # File factory for seeding
│   ├── migrations/
│   │   ├── 2024_03_14_000001_create_files_table.php
│   │   ├── 2024_03_14_000002_create_emails_table.php
│   │   └── 2024_03_14_000003_add_s3_paths_to_emails_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── EmailSeeder.php
│       └── FileSeeder.php
├── tests/
│   ├── Feature/Console/
│   │   └── EmailsMigrateToS3Test.php      # Feature tests
│   └── Unit/Services/
│       ├── EmailMigrationServiceTest.php  # Service tests
│       └── S3UploadServiceTest.php        # S3 service tests
├── docker/
│   ├── nginx/conf.d/
│   │   └── app.conf                       # Nginx configuration
│   └── php/
│       └── local.ini                      # PHP configuration
├── Dockerfile                             # PHP FPM image
├── docker-compose.yml                     # Container orchestration
└── README.md                              # This file
```

## Testing

### Run All Tests

```bash
docker-compose exec app php artisan test
```
