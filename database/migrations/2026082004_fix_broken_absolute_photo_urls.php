<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data fix satu kali: photo_path yang sempat tersimpan sebagai URL absolut
 * salah (mis. http://localhost:8000/storage/...) karena APP_URL belum benar,
 * diperbaiki jadi path relatif ("/storage/...") saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'users'] as $table) {
            $column = $table === 'users' ? 'avatar_path' : 'photo_path';

            DB::table($table)
                ->whereNotNull($column)
                ->where($column, 'like', 'http%/storage/%')
                ->get()
                ->each(function ($row) use ($table, $column) {
                    $fixed = '/storage/' . preg_replace('#^https?://[^/]+/storage/#', '', $row->{$column});
                    DB::table($table)->where('id', $row->id)->update([$column => $fixed]);
                });
        }

        foreach (['pod_photo_path', 'pod_signature_path'] as $column) {
            DB::table('delivery_orders')
                ->whereNotNull($column)
                ->where($column, 'like', 'http%/storage/%')
                ->get()
                ->each(function ($row) use ($column) {
                    $fixed = '/storage/' . preg_replace('#^https?://[^/]+/storage/#', '', $row->{$column});
                    DB::table('delivery_orders')->where('id', $row->id)->update([$column => $fixed]);
                });
        }

        DB::table('visits')
            ->whereNotNull('photo_path')
            ->where('photo_path', 'like', 'http%/storage/%')
            ->get()
            ->each(function ($row) {
                $fixed = '/storage/' . preg_replace('#^https?://[^/]+/storage/#', '', $row->photo_path);
                DB::table('visits')->where('id', $row->id)->update(['photo_path' => $fixed]);
            });

        DB::table('buyback')
            ->whereNotNull('photo_path')
            ->where('photo_path', 'like', 'http%/storage/%')
            ->get()
            ->each(function ($row) {
                $fixed = '/storage/' . preg_replace('#^https?://[^/]+/storage/#', '', $row->photo_path);
                DB::table('buyback')->where('id', $row->id)->update(['photo_path' => $fixed]);
            });
    }

    public function down(): void
    {
        // Data fix satu arah, tidak perlu rollback.
    }
};
