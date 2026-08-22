<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ma_qr_token', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ma_phien')->constrained('phien_diem_danh')->cascadeOnDelete();
            $table->string('token', 128)->unique();
            $table->timestamp('het_han_luc')->index();
            $table->timestamps();
            $table->index(['ma_phien', 'het_han_luc']);
        });

        // Không giữ lại mã QR tĩnh trong phiên điểm danh.
        if (Schema::hasColumn('phien_diem_danh', 'ma_qr')) {
            Schema::table('phien_diem_danh', function (Blueprint $table): void {
                $table->dropColumn('ma_qr');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('phien_diem_danh', 'ma_qr')) {
            Schema::table('phien_diem_danh', function (Blueprint $table): void {
                $table->string('ma_qr', 64)->nullable();
            });
        }

        Schema::dropIfExists('ma_qr_token');
    }
};
