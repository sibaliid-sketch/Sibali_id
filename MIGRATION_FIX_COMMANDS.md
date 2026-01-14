# Migration Fix Commands

## Problem Summary
The `corporate_partners` table already exists in the database, but there was a circular dependency issue between `corporate_partners` and `contracts` tables.

## Solution Applied
1. ✅ Modified `corporate_partners` migration to remove the foreign key constraint
2. ✅ Created a new migration (000078) to add the foreign key AFTER both tables exist

## Commands to Execute

### Option 1: Fresh Migration (Recommended for Development)
**⚠️ WARNING: This will DROP ALL TABLES and data!**

```bash
# Drop all tables and re-run all migrations from scratch
php artisan migrate:fresh --force
```

### Option 2: Rollback and Re-migrate (If you need to preserve some data)

```bash
# Step 1: Rollback to before the problematic migration
php artisan migrate:rollback --step=30 --force

# Step 2: Re-run migrations
php artisan migrate --force
```

### Option 3: Manual Fix (If you want to keep existing data)

```bash
# Step 1: Drop only the corporate_partners table manually
php artisan tinker
```

Then in tinker, run:
```php
DB::statement('DROP TABLE IF EXISTS corporate_partners');
exit
```

```bash
# Step 2: Delete the migration record for corporate_partners
php artisan tinker
```

Then in tinker, run:
```php
DB::table('migrations')->where('migration', '2024_01_01_000050_create_corporate_partners_table')->delete();
exit
```

```bash
# Step 3: Re-run migrations
php artisan migrate --force
```

## Verification

After running migrations, verify the tables were created:

```bash
php artisan tinker
```

Then run:
```php
// Check if corporate_partners table exists
Schema::hasTable('corporate_partners');

// Check if contracts table exists
Schema::hasTable('contracts');

// Check if foreign key was added
DB::select("SHOW CREATE TABLE corporate_partners");

exit
```

## What Changed

### Before (Circular Dependency):
- Migration 000050: `corporate_partners` → references `contracts` (FK)
- Migration 000052: `contracts` → references `corporate_partners` (FK)
- ❌ Neither could be created first!

### After (Fixed):
- Migration 000050: `corporate_partners` → NO foreign key (just column)
- Migration 000052: `contracts` → references `corporate_partners` (FK) ✅
- Migration 000078: Add FK from `corporate_partners` to `contracts` ✅

Now the order is:
1. Create `corporate_partners` (no FK)
2. Create `contracts` (with FK to corporate_partners)
3. Add FK from `corporate_partners` to `contracts`
