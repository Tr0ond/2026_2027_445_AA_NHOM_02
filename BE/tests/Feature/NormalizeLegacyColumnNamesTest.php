<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizeLegacyColumnNamesTest extends TestCase
{
    use RefreshDatabase;

    public function test_doi_ten_cot_cu_ma_khong_lam_mat_du_lieu(): void
    {
        Schema::create('legacy_column_names_test', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('creat_at')->nullable();
            $table->timestamp('update_at')->nullable();
            $table->unsignedBigInteger('ma_phong_truc_truyen')->nullable();
        });

        DB::table('legacy_column_names_test')->insert([
            'id' => 1,
            'creat_at' => '2026-09-13 08:00:00',
            'update_at' => '2026-09-13 09:00:00',
            'ma_phong_truc_truyen' => 25,
        ]);

        $migration = require database_path('migrations/2026_09_13_000001_normalize_legacy_column_names.php');
        $migration->up();

        $this->assertFalse(Schema::hasColumn('legacy_column_names_test', 'creat_at'));
        $this->assertFalse(Schema::hasColumn('legacy_column_names_test', 'update_at'));
        $this->assertFalse(Schema::hasColumn('legacy_column_names_test', 'ma_phong_truc_truyen'));
        $this->assertTrue(Schema::hasColumn('legacy_column_names_test', 'created_at'));
        $this->assertTrue(Schema::hasColumn('legacy_column_names_test', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('legacy_column_names_test', 'ma_phong_truc_tuyen'));

        $this->assertDatabaseHas('legacy_column_names_test', [
            'id' => 1,
            'created_at' => '2026-09-13 08:00:00',
            'updated_at' => '2026-09-13 09:00:00',
            'ma_phong_truc_tuyen' => 25,
        ]);
    }
}
