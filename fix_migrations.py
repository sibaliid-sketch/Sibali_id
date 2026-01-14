import os
import re
import glob

# Pattern untuk mencari dan mengganti UUID dengan bigint
patterns = [
    # Pattern 1: uuid('id')->primary() menjadi id()
    (r"\$table->uuid\('id'\)->primary\(\);", r"$table->id();"),

    # Pattern 2: uuid('field') menjadi foreignId('field')->constrained('users')->onDelete('cascade')
    # Untuk user_id, author_id, creator_id, dll yang merujuk ke users
    (r"\$table->uuid\('(user_id|author_id|creator_id|uploader_id|uploaded_by|created_by|grader_id|graded_by|verified_by|actor_id|sender_id|recipient_id|organizer_id|host_id|requester_id|owner_id|assignee_id|assigned_to|assigned_by)'\);",
     r"$table->foreignId('\1')->constrained('users')->onDelete('cascade');"),

    # Pattern 3: Hapus baris foreign key yang sudah tidak diperlukan
    (r"\s*\$table->foreign\('(user_id|author_id|creator_id|uploader_id|uploaded_by|created_by|grader_id|graded_by|verified_by|actor_id|sender_id|recipient_id|organizer_id|host_id|requester_id|owner_id|assignee_id|assigned_to|assigned_by)'\)->references\('id'\)->on\('users'\)->onDelete\('cascade'\);", ""),

    # Pattern 4: uuid untuk foreign key lainnya (nullable)
    (r"\$table->uuid\('([^']+)'\)->nullable\(\);", r"$table->foreignId('\1')->nullable()->constrained()->onDelete('set null');"),

    # Pattern 5: uuid untuk foreign key lainnya (not nullable)
    (r"\$table->uuid\('([^']+)'\);(?!\s*\$table->foreign)", r"$table->foreignId('\1')->constrained()->onDelete('cascade');"),
]

# Cari semua file migration dari 000047 sampai 000077
migration_files = []
for i in range(47, 78):
    pattern = f"database/migrations/2024_01_01_0000{i:02d}_*.php"
    files = glob.glob(pattern)
    migration_files.extend(files)

print(f"Found {len(migration_files)} migration files to fix")

for filepath in migration_files:
    print(f"Processing: {filepath}")

    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        original_content = content

        # Apply all patterns
        for pattern, replacement in patterns:
            content = re.sub(pattern, replacement, content)

        # Only write if content changed
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"  ✓ Fixed: {filepath}")
        else:
            print(f"  - No changes needed: {filepath}")

    except Exception as e:
        print(f"  ✗ Error processing {filepath}: {e}")

print("\nDone!")
