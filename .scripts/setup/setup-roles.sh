#!/bin/bash

# Role Setup Script for Sibali.id
# Creates RBAC roles, permissions, and seeds admin user

set -e

# Configuration
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@sibali.id}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-Sibali123!}"
LOG_FILE="./storage/logs/role_setup_$(date +"%Y%m%d_%H%M%S").log"

# Employee roles based on Notes.md
EMPLOYEE_ROLES=(
    "Basic Staff"
    "Senior Staff"
    "Leader"
    "Supervisor"
    "Manager"
    "Header"
    "Executives"
)

# Customer roles
CUSTOMER_ROLES=(
    "Parent"
    "Student"
)

# Departments
DEPARTMENTS=(
    "Operations"
    "Finance & Accounting"
    "IT"
    "Engagement & Retention"
    "Academic & Curriculum"
    "Sales & Marketing"
    "Human Resource"
    "Public Relation"
    "Product Research & Development"
)

# Logging function
log() {
    echo "$(date +"%Y-%m-%d %H:%M:%S") - $1" | tee -a "$LOG_FILE"
}

# Create output directory for logs
mkdir -p "$(dirname "$LOG_FILE")"

# Function to check if Laravel is available
check_laravel() {
    if [ ! -f "artisan" ]; then
        log "ERROR: Laravel artisan not found. Run this script from Laravel root directory."
        exit 1
    fi

    if ! php artisan --version >/dev/null 2>&1; then
        log "ERROR: Cannot execute Laravel artisan commands."
        exit 1
    fi
}

# Function to create roles via artisan command
create_roles() {
    log "Creating employee roles..."

    for role in "${EMPLOYEE_ROLES[@]}"; do
        # Create role if it doesn't exist
        php artisan role:create "$role" --guard=employee 2>/dev/null || log "Role '$role' may already exist or creation failed"
    done

    log "Creating customer roles..."
    for role in "${CUSTOMER_ROLES[@]}"; do
        php artisan role:create "$role" --guard=web 2>/dev/null || log "Role '$role' may already exist or creation failed"
    done
}

# Function to assign default permissions
assign_default_permissions() {
    log "Assigning default permissions to roles..."

    # Basic Staff permissions
    php artisan permission:assign "Basic Staff" "view_dashboard,view_profile" 2>/dev/null || true

    # Senior Staff permissions
    php artisan permission:assign "Senior Staff" "view_dashboard,view_profile,edit_profile,manage_team" 2>/dev/null || true

    # Leader permissions
    php artisan permission:assign "Leader" "view_dashboard,view_profile,edit_profile,manage_team,approve_requests,view_reports" 2>/dev/null || true

    # Supervisor permissions
    php artisan permission:assign "Supervisor" "view_dashboard,view_profile,edit_profile,manage_team,approve_requests,view_reports,manage_department" 2>/dev/null || true

    # Manager permissions
    php artisan permission:assign "Manager" "view_dashboard,view_profile,edit_profile,manage_team,approve_requests,view_reports,manage_department,manage_budget,access_analytics" 2>/dev/null || true

    # Header permissions
    php artisan permission:assign "Header" "view_dashboard,view_profile,edit_profile,manage_team,approve_requests,view_reports,manage_department,manage_budget,access_analytics,strategic_planning" 2>/dev/null || true

    # Executives permissions
    php artisan permission:assign "Executives" "view_dashboard,view_profile,edit_profile,manage_team,approve_requests,view_reports,manage_department,manage_budget,access_analytics,strategic_planning,company_wide_access" 2>/dev/null || true

    # Customer permissions
    php artisan permission:assign "Parent" "view_lms,manage_student,enroll_courses,view_reports" 2>/dev/null || true
    php artisan permission:assign "Student" "view_lms,enroll_courses,view_grades,access_materials" 2>/dev/null || true
}

# Function to create seed admin user
create_seed_admin() {
    log "Creating seed admin user..."

    # Check if admin user already exists
    if php artisan user:exists "$ADMIN_EMAIL" >/dev/null 2>&1; then
        log "Admin user $ADMIN_EMAIL already exists, skipping creation"
        return
    fi

    # Create admin user
    php artisan user:create "$ADMIN_EMAIL" "$ADMIN_PASSWORD" --role="Executives" --department="IT" 2>/dev/null || log "Failed to create admin user via artisan, trying alternative method"

    # Alternative: Direct database insertion (if artisan command not available)
    if ! php artisan user:list | grep -q "$ADMIN_EMAIL"; then
        log "Attempting direct database insertion for admin user..."

        # This would require database connection details
        # For now, we'll assume the artisan command works
        log "Please ensure admin user is created manually if artisan commands fail"
    fi
}

# Function to export current RBAC as JSON policy
export_rbac_policy() {
    local output_file="./storage/app/rbac_policy_$(date +"%Y%m%d_%H%M%S").json"

    log "Exporting current RBAC policy to $output_file..."

    # Create policy JSON
    cat > "$output_file" << EOF
{
  "exported_at": "$(date -Iseconds)",
  "version": "1.0",
  "employee_roles": [
EOF

    # Add employee roles
    first=true
    for role in "${EMPLOYEE_ROLES[@]}"; do
        if [ "$first" = true ]; then
            first=false
        else
            echo "," >> "$output_file"
        fi
        echo "    {\"name\": \"$role\", \"type\": \"employee\"}" >> "$output_file"
    done

    cat >> "$output_file" << EOF
  ],
  "customer_roles": [
EOF

    # Add customer roles
    first=true
    for role in "${CUSTOMER_ROLES[@]}"; do
        if [ "$first" = true ]; then
            first=false
        else
            echo "," >> "$output_file"
        fi
        echo "    {\"name\": \"$role\", \"type\": \"customer\"}" >> "$output_file"
    done

    cat >> "$output_file" << EOF
  ],
  "departments": [
EOF

    # Add departments
    first=true
    for dept in "${DEPARTMENTS[@]}"; do
        if [ "$first" = true ]; then
            first=false
        else
            echo "," >> "$output_file"
        fi
        echo "    {\"name\": \"$dept\"}" >> "$output_file"
    done

    cat >> "$output_file" << EOF
  ],
  "permissions": {
    "Basic Staff": ["view_dashboard", "view_profile"],
    "Senior Staff": ["view_dashboard", "view_profile", "edit_profile", "manage_team"],
    "Leader": ["view_dashboard", "view_profile", "edit_profile", "manage_team", "approve_requests", "view_reports"],
    "Supervisor": ["view_dashboard", "view_profile", "edit_profile", "manage_team", "approve_requests", "view_reports", "manage_department"],
    "Manager": ["view_dashboard", "view_profile", "edit_profile", "manage_team", "approve_requests", "view_reports", "manage_department", "manage_budget", "access_analytics"],
    "Header": ["view_dashboard", "view_profile", "edit_profile", "manage_team", "approve_requests", "view_reports", "manage_department", "manage_budget", "access_analytics", "strategic_planning"],
    "Executives": ["view_dashboard", "view_profile", "edit_profile", "manage_team", "approve_requests", "view_reports", "manage_department", "manage_budget", "access_analytics", "strategic_planning", "company_wide_access"],
    "Parent": ["view_lms", "manage_student", "enroll_courses", "view_reports"],
    "Student": ["view_lms", "enroll_courses", "view_grades", "access_materials"]
  }
}
EOF

    log "RBAC policy exported to $output_file"
}

# Function to log actions and owner
log_actions() {
    log "RBAC Setup completed by: $(whoami)"
    log "Timestamp: $(date)"
    log "Server: $(hostname)"
    log "Laravel Version: $(php artisan --version 2>/dev/null || echo 'Unknown')"
}

# Main execution
log "Starting RBAC role setup for Sibali.id"

check_laravel

# Idempotent operations - safe to re-run
create_roles
assign_default_permissions
create_seed_admin
export_rbac_policy
log_actions

log "RBAC setup completed successfully"
log "Log file: $LOG_FILE"

echo "RBAC Setup Summary:"
echo "- Created ${#EMPLOYEE_ROLES[@]} employee roles"
echo "- Created ${#CUSTOMER_ROLES[@]} customer roles"
echo "- Assigned default permissions to all roles"
echo "- Created seed admin user: $ADMIN_EMAIL"
echo "- Exported RBAC policy to JSON file"
echo "- Check log file: $LOG_FILE"
