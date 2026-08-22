<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thanh_vien_phong_truc_tuyen', function (Blueprint $table) {
            $table->boolean('dang_chia_se')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('thanh_vien_phong_truc_tuyen', function (Blueprint $table) {
            $table->dropColumn('dang_chia_se');
        });
    }
};
