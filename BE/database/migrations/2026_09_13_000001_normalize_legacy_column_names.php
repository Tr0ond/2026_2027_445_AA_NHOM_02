<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chuẩn hóa tên cột cũ bằng thao tác RENAME để giữ nguyên dữ liệu,
     * kiểu dữ liệu, chỉ mục và giá trị hiện có.
     */
    public function up(): void
    {
        $schema = Schema::getConnection()->getSchemaBuilder();
        $doiTen = [
            'update_at' => 'updated_at',
            'creat_at' => 'created_at',
            'ma_phong_truc_truyen' => 'ma_phong_truc_tuyen',
        ];

        foreach ($schema->getTableListing(schemaQualified: false) as $table) {
            foreach ($doiTen as $tenCu => $tenMoi) {
                if (! $schema->hasColumn($table, $tenCu)
                    || $schema->hasColumn($table, $tenMoi)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($tenCu, $tenMoi): void {
                    $blueprint->renameColumn($tenCu, $tenMoi);
                });
            }
        }
    }

    /**
     * Không khôi phục các tên cột sai khi rollback.
     * Việc đổi ngược có thể phá mã nguồn mới hoặc gây xung đột nếu cả hai
     * phiên bản cột đã xuất hiện sau khi hệ thống được nâng cấp.
     */
    public function down(): void
    {
        // Cố ý để trống nhằm bảo toàn cấu trúc đã chuẩn hóa và dữ liệu.
    }
};
