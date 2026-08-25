<?php

namespace Tests\Feature;

use App\Models\DonXinPhep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonXinPhepTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sinh_vien_co_the_nop_don_xin_phep()
    {
        $sinhVien = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $this->actingAs($sinhVien);

        $response = $this->postJson('/api/don-xin-phep', [
            'ma_lop_hoc' => 1,
            'ngay_nghi' => '2026-08-25',
            'ly_do' => 'Bị ốm',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('don_xin_phep', [
            'ma_sinh_vien' => $sinhVien->id,
            'ma_lop_hoc' => 1,
            'ngay_nghi' => '2026-08-25',
            'ly_do' => 'Bị ốm',
            'trang_thai' => 'cho_duyet',
        ]);
    }

    /** @test */
    public function giang_vien_co_the_duyet_hoac_tu_choi_don_xin_phep()
    {
        $giangVien = User::factory()->create(['vai_tro' => 'giang_vien']);
        $this->actingAs($giangVien);

        $don = DonXinPhep::factory()->create(['trang_thai' => 'cho_duyet']);

        $response = $this->patchJson("/api/don-xin-phep/{$don->id}", [
            'trang_thai' => 'duoc_duyet',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('don_xin_phep', [
            'id' => $don->id,
            'trang_thai' => 'duoc_duyet',
            'nguoi_duyet' => $giangVien->id,
        ]);
    }
}
