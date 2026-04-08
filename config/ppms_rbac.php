<?php

/**
 * RBAC matrix: roles → permission keys (module.action).
 * Wildcard: '*' => true grants all keys for that role.
 */
return [

    'permission_admin_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PPMS_RBAC_ADMIN_ROLES', 'admin'))
    ))),

    'modules' => [
        'users' => ['label' => 'Users'],
        'projects' => ['label' => 'Projects'],
        'orders' => ['label' => 'Orders'],
        'reports' => ['label' => 'Reports'],
        'settings' => ['label' => 'Settings'],
    ],

    'actions' => ['view', 'create', 'update', 'delete'],

    /*
    |--------------------------------------------------------------------------
    | Role → permissions (explicit keys; omit = denied unless wildcard)
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'admin' => [
            '*' => true,
        ],
        'pm' => [
            'users.view' => true,
            'projects.view' => true,
            'projects.create' => true,
            'projects.update' => true,
            'projects.delete' => true,
            'orders.view' => true,
            'orders.create' => true,
            'orders.update' => true,
            'reports.view' => true,
            'reports.create' => true,
            'reports.update' => true,
            'settings.view' => true,
        ],
        'tl' => [
            'users.view' => true,
            'projects.view' => true,
            'projects.update' => true,
            'orders.view' => true,
            'reports.view' => true,
            'reports.create' => true,
        ],
        'hr' => [
            'users.view' => true,
            'users.update' => true,
            'reports.view' => true,
        ],
        'developer' => [
            'projects.view' => true,
            'projects.update' => true,
            'orders.view' => true,
            'reports.view' => true,
        ],
    ],

];
