<?php

namespace Tests\Feature;

use App\Models\DiemSinhVien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function giang_vien_co_the_nhap_diem_thanh_phan()
    {
        $giangVien = User::factory()->create(['vai_tro' => 'giang_vien']);
        $this->actingAs($giangVien);

        $response = $this->postJson('/api/diem', [
            'ma_sinh_vien' => 1,
            'ma_thanh_phan' => 1,
            'diem' => 8.5,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('diem_sinh_vien', [
            'ma_sinh_vien' => 1,
            'ma_thanh_phan' => 1,
            'diem' => 8.5,
        ]);
    }

    /** @test */
    public function sinh_vien_co_the_xem_diem_cua_minh()
    {
        $sinhVien = User::factory()->create(['vai_tro' => 'sinh_vien']);
        $this->actingAs($sinhVien);

        DiemSinhVien::factory()->create([
            'ma_sinh_vien' => $sinhVien->id,
            'ma_thanh_phan' => 1,
            'diem' => 9.0,
        ]);

        $response = $this->getJson('/api/diem/toi');

        $response->assertStatus(200);
        $response->assertJsonFragment(['diem' => 9.0]);
    }
}