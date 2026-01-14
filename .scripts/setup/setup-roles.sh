#!/bin/bash

# Role Setup Script for Sibali.id
# Purpose: Script bootstrap untuk membuat RBAC roles & permission default
# Function: Create roles, assign permissions, seed admin user

set -e

# Configuration
APP_ENV="${APP_ENV:-production}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@sibali.id}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"
LOG_FILE="/var/log/sibali/role_setup.log"
EXPORT_FILE="/var/www/html/storage/app/rbac_policy.json"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOG_FILE"
}

# Check if running in production
check_environment() {
    if [ "$APP_ENV" = "production" ]; then
        log "WARNING: Running role setup in production environment"
        read -p "Are you sure you want to continue? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "Operation cancelled"
            exit 0
        fi
    fi
}

# Create permissions
create_permissions() {
    log "Creating permissions..."

    cd /var/www/html

    # Define permissions array
    local permissions=(
        # User management
        "users.view" "users.create" "users.edit" "users.delete"
        "users.impersonate" "users.reset-password"

        # Academic management
        "academic.view" "academic.create" "academic.edit" "academic.delete"
        "courses.manage" "classes.manage" "assignments.manage"
        "grades.view" "grades.edit" "grades.publish"

        # Content management
        "content.view" "content.create" "content.edit" "content.delete"
        "content.publish" "content.unpublish" "blog.manage"

        # Finance & payments
        "finance.view" "finance.approve" "finance.refund"
        "payments.view" "payments.verify" "payments.manual"
        "invoices.create" "invoices.view" "invoices.edit"

        # HR operations
        "hr.view" "hr.create" "hr.edit" "hr.delete"
        "employees.manage" "leave.approve" "payroll.view"

        # Marketing
        "marketing.view" "marketing.create" "marketing.edit" "marketing.delete"
        "campaigns.manage" "social-media.post" "analytics.view"

        # Operations
        "operations.view" "operations.manage" "bookings.view"
        "rooms.manage" "schedules.manage" "reports.view"

        # B2B
        "b2b.view" "b2b.create" "b2b.edit" "b2b.delete"
        "corporate.manage" "proposals.approve" "contracts.sign"

        # Student retention
        "retention.view" "retention.manage" "retention.intervene"
        "analytics.student" "reports.retention"

        # System administration
        "system.view" "system.config" "system.backup"
        "system.monitor" "system.logs" "system.security"

        # Internal tools
        "internal.view" "internal.tools" "internal.reports"
        "internal.audit" "internal.workflow"
    )

    # Create permissions via artisan
    for permission in "${permissions[@]}"; do
        php artisan tinker --execute="
            Spatie\Permission\Models\Permission::firstOrCreate(['name' => '$permission']);
        " 2>/dev/null || log "Failed to create permission: $permission"
    done

    log "Permissions created"
}

# Create roles and assign permissions
create_roles() {
    log "Creating roles and assigning permissions..."

    cd /var/www/html

    # Define roles and their permissions
    declare -A role_permissions

    # Super Admin - all permissions
    role_permissions[super-admin]="users.* academic.* content.* finance.* hr.* marketing.* operations.* b2b.* retention.* system.* internal.*"

    # Admin Operations
    role_permissions[admin-operations]="operations.* bookings.* schedules.* rooms.* reports.view users.view users.create users.edit"

    # Admin Finance
    role_permissions[admin-finance]="finance.* payments.* invoices.* reports.view"

    # Admin Academic
    role_permissions[admin-academic]="academic.* courses.* classes.* assignments.* grades.* reports.view"

    # Admin IT
    role_permissions[admin-it]="system.* internal.*"

    # Sales & Marketing
    role_permissions[sales-marketing]="marketing.* campaigns.* social-media.* analytics.view content.create content.edit content.publish reports.view"

    # HR
    role_permissions[hr]="hr.* employees.* leave.* payroll.view reports.view"

    # B2B
    role_permissions[b2b]="b2b.* corporate.* proposals.* contracts.* reports.view"

    # Student Retention
    role_permissions[retention]="retention.* analytics.student reports.retention"

    # Public Relation
    role_permissions[pr]="content.view content.create content.edit content.publish blog.manage"

    # Product Research & Development
    role_permissions[prd]="academic.view courses.view analytics.student reports.view"

    # Create roles and assign permissions
    for role in "${!role_permissions[@]}"; do
        log "Creating role: $role"

        # Create role
        php artisan tinker --execute="
            \$role = Spatie\Permission\Models\Role::firstOrCreate(['name' => '$role']);
        " 2>/dev/null || log "Failed to create role: $role"

        # Assign permissions
        local perms="${role_permissions[$role]}"
        php artisan tinker --execute="
            \$role = Spatie\Permission\Models\Role::where('name', '$role')->first();
            if (\$role) {
                \$permissions = explode(' ', '$perms');
                foreach (\$permissions as \$perm) {
                    \$role->givePermissionTo(\$perm);
                }
            }
        " 2>/dev/null || log "Failed to assign permissions to role: $role"
    done

    log "Roles created and permissions assigned"
}

# Create seed admin user
create_admin_user() {
    if [ -z "$ADMIN_PASSWORD" ]; then
        log "WARNING: ADMIN_PASSWORD not set, skipping admin user creation"
        return
    fi

    log "Creating seed admin user..."

    cd /var/www/html

    # Create admin user
    php artisan tinker --execute="
        \$user = App\Models\User::firstOrCreate([
            'email' => '$ADMIN_EMAIL'
        ], [
            'name' => 'System Administrator',
            'password' => bcrypt('$ADMIN_PASSWORD'),
            'email_verified_at' => now()
        ]);

        \$user->assignRole('super-admin');
        echo 'Admin user created/updated';
    " 2>/dev/null || log "Failed to create admin user"

    log "Admin user setup completed"
}

# Export RBAC policy
export_policy() {
    log "Exporting RBAC policy..."

    cd /var/www/html

    php artisan tinker --execute="
        \$roles = Spatie\Permission\Models\Role::with('permissions')->get();
        \$policy = [
            'exported_at' => now()->toISOString(),
            'roles' => \$roles->map(function(\$role) {
                return [
                    'name' => \$role->name,
                    'permissions' => \$role->permissions->pluck('name')->toArray()
                ];
            })->toArray()
        ];
        file_put_contents('$EXPORT_FILE', json_encode(\$policy, JSON_PRETTY_PRINT));
        echo 'Policy exported to $EXPORT_FILE';
    " 2>/dev/null || log "Failed to export policy"

    log "RBAC policy exported"
}

# Main execution
log "Starting RBAC role setup"

check_environment
create_permissions
create_roles
create_admin_user
export_policy

log "RBAC role setup completed successfully"

exit 0
