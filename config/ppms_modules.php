<?php

/**
 * Operational modules for per-module maintenance and (future) nav/RBAC alignment.
 * Keys must be stable; labels are for admin UI.
 */
return [

    'modules' => [
        'dashboard' => ['label' => 'Dashboard'],
        'projects' => ['label' => 'Projects'],
        'teams' => ['label' => 'Teams'],
        'vendors' => ['label' => 'Vendors'],
        'contracts' => ['label' => 'Contracts'],
        'kaizens' => ['label' => 'Kaizen'],
        'innovation' => ['label' => 'Innovation'],
        'evaluations' => ['label' => 'Evaluations'],
        'notifications' => ['label' => 'Notifications'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles that may use the API when a module is in maintenance mode
    |--------------------------------------------------------------------------
    */
    'maintenance_bypass_roles' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PPMS_MAINTENANCE_BYPASS_ROLES', 'admin'))
    ))),

];
