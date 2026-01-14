<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role Definitions and Permissions
    |--------------------------------------------------------------------------
    |
    | This file contains the core RBAC (Role-Based Access Control) mapping
    | for the application. It defines roles, their hierarchy, default permissions,
    | and seeding behavior for the role_hierarchies table.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Role Definitions
    |--------------------------------------------------------------------------
    |
    | Complete list of roles with their properties.
    | Level 0 = Highest authority (Executives), increasing numbers = lower authority
    |
    */
    'roles' => [
        [
            'code' => 'executives',
            'name' => 'Executives',
            'department' => 'Executive',
            'level' => 0,
            'description' => 'Top-level executives with full system access',
        ],
        [
            'code' => 'header',
            'name' => 'Header',
            'department' => 'Executive',
            'level' => 1,
            'description' => 'Department heads and senior leadership',
        ],
        [
            'code' => 'manager',
            'name' => 'Manager',
            'department' => 'Management',
            'level' => 2,
            'description' => 'Department managers with supervisory authority',
        ],
        [
            'code' => 'supervisor',
            'name' => 'Supervisor',
            'department' => 'Operations',
            'level' => 3,
            'description' => 'Team supervisors with oversight responsibilities',
        ],
        [
            'code' => 'leader',
            'name' => 'Leader',
            'department' => 'Operations',
            'level' => 4,
            'description' => 'Team leaders with coordination responsibilities',
        ],
        [
            'code' => 'senior_staff',
            'name' => 'Senior Staff',
            'department' => 'Operations',
            'level' => 5,
            'description' => 'Experienced staff members',
        ],
        [
            'code' => 'basic_staff',
            'name' => 'Basic Staff',
            'department' => 'Operations',
            'level' => 6,
            'description' => 'Entry-level staff members',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Permissions Mapping
    |--------------------------------------------------------------------------
    |
    | Default permissions assigned to each role.
    | These are base permissions that can be extended per user.
    |
    */
    'default_permissions' => [
        'executives' => [
            'system.admin',
            'users.manage',
            'reports.view_all',
            'settings.modify',
            'audit.view',
            'backup.manage',
            'departments.manage',
            'roles.assign',
            'financial.view',
            'strategic.decisions',
        ],
        'header' => [
            'department.manage',
            'users.manage_department',
            'reports.view_department',
            'budget.approve',
            'projects.approve',
            'audit.view_department',
            'settings.view',
        ],
        'manager' => [
            'team.manage',
            'users.view_team',
            'reports.view_team',
            'leave.approve',
            'attendance.view_team',
            'projects.manage_team',
            'budget.view_team',
        ],
        'supervisor' => [
            'team.supervise',
            'attendance.approve',
            'leave.view_team',
            'reports.view_team',
            'tasks.assign',
            'performance.review',
        ],
        'leader' => [
            'team.coordinate',
            'attendance.view_team',
            'tasks.manage',
            'reports.view_basic',
            'communication.team',
        ],
        'senior_staff' => [
            'tasks.execute',
            'attendance.self',
            'leave.request',
            'reports.view_self',
            'communication.basic',
        ],
        'basic_staff' => [
            'tasks.basic',
            'attendance.self',
            'leave.request',
            'communication.basic',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Inheritance
    |--------------------------------------------------------------------------
    |
    | Defines parent-child relationships between roles.
    | Child roles inherit permissions from parent roles.
    |
    */
    'inheritance' => [
        'executives' => [], // No parent
        'header' => ['executives'],
        'manager' => ['header'],
        'supervisor' => ['manager'],
        'leader' => ['supervisor'],
        'senior_staff' => ['leader'],
        'basic_staff' => ['senior_staff'],
    ],

    /*
    |--------------------------------------------------------------------------
    | System Roles
    |--------------------------------------------------------------------------
    |
    | Special system roles that are not part of the regular hierarchy.
    |
    */
    'system_roles' => [
        'super-admin' => [
            'description' => 'Super administrator with unlimited access',
            'permissions' => ['*'], // All permissions
            'auto_assign' => false,
        ],
        'service' => [
            'description' => 'Service account for automated processes',
            'permissions' => ['api.access', 'system.read'],
            'auto_assign' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeding Behavior
    |--------------------------------------------------------------------------
    |
    | Configuration for how roles are seeded into the database.
    |
    */
    'seeding' => [
        'auto_create_roles' => true,
        'auto_assign_permissions' => true,
        'create_system_roles' => true,
        'update_existing' => true,
        'preserve_custom_permissions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Hooks
    |--------------------------------------------------------------------------
    |
    | Hooks that run during role/permission migrations.
    |
    */
    'migration_hooks' => [
        'pre_migrate' => [
            // Commands to run before migration
            'cache:clear',
        ],
        'post_migrate' => [
            // Commands to run after migration
            'permission:cache-reset',
            'cache:clear',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Assignment Rules
    |--------------------------------------------------------------------------
    |
    | Rules governing role assignment and changes.
    |
    */
    'assignment_rules' => [
        'require_approval' => [
            'executives' => true,
            'header' => true,
            'manager' => true,
            'supervisor' => false,
            'leader' => false,
            'senior_staff' => false,
            'basic_staff' => false,
        ],
        'approval_roles' => ['executives', 'header'],
        'max_role_level_per_assigner' => 2, // Assigner must be at least 2 levels higher
        'prevent_demotion' => true,
        'log_assignments' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Department-Based Role Restrictions
    |--------------------------------------------------------------------------
    |
    | Restrictions on role assignments based on departments.
    |
    */
    'department_restrictions' => [
        'executives' => [], // Can be assigned to any department
        'header' => [],     // Can be assigned to any department
        'manager' => [],    // Can be assigned to any department
        'supervisor' => ['Executive'], // Cannot be assigned to Executive
        'leader' => ['Executive'],     // Cannot be assigned to Executive
        'senior_staff' => ['Executive'], // Cannot be assigned to Executive
        'basic_staff' => ['Executive'],   // Cannot be assigned to Executive
    ],
];
