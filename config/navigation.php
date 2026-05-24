<?php

return [
    'groups' => [
        'ceo' => [
            ['label' => 'MAIN', 'items' => [
                ['label' => 'Executive Monitor', 'route' => 'executive.index', 'icon' => 'squares-2x2'],
                ['label' => 'Project Master',    'route' => 'projects.index',  'icon' => 'rectangle-stack'],
                ['label' => 'Client Directory',  'route' => 'clients.index',   'icon' => 'building-office'],
            ]],
            ['label' => 'SYSTEM CONTROL', 'items' => [
                ['label' => 'Team Management',   'route' => 'team.index',      'icon' => 'users'],
                ['label' => 'Audit Trail',       'route' => 'audit.index',     'icon' => 'clock'],
            ]],
        ],

        'operational' => [
            ['label' => 'MAIN', 'items' => [
                ['label' => 'Dashboard',         'route' => 'dashboard.index', 'icon' => 'home'],
                ['label' => 'Projects',          'route' => 'projects.index',  'icon' => 'rectangle-stack'],
                ['label' => 'Activity Log',      'route' => 'audit.index',     'icon' => 'clock'],
            ]],
        ],
    ],

    'bottom' => [],

    'role_to_group' => [
        'ceo_pm'        => 'ceo',
        'sa_qa'         => 'operational',
        'fullstack_dev' => 'operational',
        'uiux_designer' => 'operational',
    ],
];
