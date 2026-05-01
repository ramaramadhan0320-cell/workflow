<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => \App\Filters\Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // 🔥 AUTH FILTER
        'auth'          => \App\Filters\AuthFilter::class,
        'apiauth'       => \App\Filters\ApiAuthFilter::class,
    ];

    public array $required = [
        'before' => [
            'forcehttps',
            'pagecache',
        ],
        'after' => [
            'pagecache',
            'performance',
            'toolbar',
        ],
    ];

    /**
     * ❌ JANGAN TARUH AUTH DI GLOBAL (penyebab redirect loop)
     * ✅ AKTIFKAN CORS DI GLOBAL UNTUK MENANGANI PREFLIGHT
     */
    public array $globals = [
        'before' => [
            'cors',
        ],
        'after' => [
            // optional
        ],
    ];

    /**
     * 🔥 FILTER BERDASARKAN ROUTE (AMAN & CLEAN)
     */
    public array $filters = [
        'auth' => [
            'before' => [
                'dashboard',
                'detail',
                'task/*',
                'image/*',
            ],
            'except' => [
                'api/*',
            ],
        ],
        'csrf' => [
            'except' => [
                'api/absensi/submit',
                'api/iot/receive-data',
            ],
        ],
        'apiauth' => [
            'before' => [
                'api-android/*',
            ],
            'except' => [
                'api-android/auth/login',
                'api-android/auth/login/*',
                'api-android/profile-photo/*', 
                'api-android/task-photo/*',
            ],

        ],
    ];

    public array $methods = [];
}