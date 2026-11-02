<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanMasukTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_masuk_menampilkan_form_asli_dan_tombol_lewati(): void
    {
        $response = $this->get(route('masuk'));

        $response->assertOk();
        $response->assertSee('Lewati animasi');
        $response->assertSee('Racik Pakan Lebih Cerdas. ', false);
        $response->assertSee('action="'.url('/masuk').'"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('name="ingat"', false);
        $response->assertSee('id="toggle-password"', false);
        $response->assertDontSee('demo@ransumku.test');
    }

    public function test_login_salah_menampilkan_pesan_galat_pada_halaman_masuk(): void
    {
        $response = $this->from(route('masuk'))->post(route('masuk'), [
            'email' => 'tidak-ada@ransumku.test',
            'password' => 'salah-total',
        ]);

        $response->assertRedirect(route('masuk'));
        $this->assertGuest();

        $this->followRedirects($response)->assertSee('Email atau kata sandi salah.');
    }
}

