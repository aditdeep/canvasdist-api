<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * PENTING: file ini adalah referensi untuk setup awal project Laravel fresh.
 *
 * Saat menjalankan `composer create-project laravel/laravel`, file bootstrap/app.php
 * bawaan TIDAK otomatis memuat routes/api.php meskipun Sanctum sudah di-install.
 * Baris `api:` dan `apiPrefix:` di bawah ini WAJIB ditambahkan manual, atau semua
 * endpoint /api/* akan mengembalikan 404 meskipun routes/api.php sudah lengkap.
 *
 * Lihat DEPLOYMENT.md untuk konteks lengkap langkah setup dari awal.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
