<?php

declare(strict_types=1);

return [
    'scan_paths' => ['app'],
    'exclude_paths' => ['app/Http/Middleware', 'vendor'],
    'thresholds' => [
        'method_length' => 30,
        'class_length' => 500,
        'max_public_methods' => 20,
        'nesting_depth' => 4,
        'complexity_per_method' => 10,
    ],
    'cost' => [
        'hours_per_point' => 0.25,
        'hourly_rate' => null,
    ],
    'git' => [
        'enabled' => true,
        'blame_timeout' => 30,
    ],
    'export' => [
        'path' => base_path('DEBT_REPORT.md'),
        'json_path' => base_path('DEBT_REPORT.json'),
    ],
    'detectors' => [
        'todos'        => true,
        'complexity'   => true,
        'coverage'     => true,
        'dependencies' => true,
        'git_age'      => true,
        'n1_queries'   => true,
        'security'     => true,
        'dead_code'    => true,
    ],
    'n1_ignore_properties'     => ['id', 'uuid', 'created_at', 'updated_at', 'deleted_at'],
    'security_exclude_paths'   => ['tests', 'database/seeders'],
    'dead_code_ignore_methods' => [],
];
