<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Không bao giờ cho RefreshDatabase chạy trên database phát triển/thật.
     * PHPUnit của dự án phải dùng SQLite :memory: như cấu hình trong phpunit.xml.
     */
    protected function beforeRefreshingDatabase()
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \RuntimeException(
                'Từ chối làm mới database không phải SQLite :memory:. Hãy xóa config cache trước khi chạy PHPUnit.'
            );
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
