<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanDaftarTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_daftar_menampilkan_form_dengan_komponen_bersama(): void
    {
        $response = $this->get(route('daftar'));

        $response->assertOk();
        $response->assertSee('Buat akun baru');
        $response->assertSee('action="'.url('/daftar').'"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('name="name"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('autocomplete="new-password"', false);
        $response->assertSee('id="btn-daftar"', false);
        $response->assertSee('Minimal 8 karakter.');
    }

    public function test_halaman_masuk_dan_daftar_memakai_token_desain_yang_sama(): void
    {
        $masuk = $this->get(route('masuk'));
        $daftar = $this->get(route('daftar'));

        foreach ([$masuk, $daftar] as $response) {
            $response->assertSee('--au-warm: #F7F8F3', false);
            $response->assertSee('data-auth-form', false);
            $response->assertSee('data-auth-submit', false);
        }
        $masuk->assertSee('Lupa kata sandi?');
    }

    public function test_daftar_dengan_kata_sandi_tidak_cocok_menampilkan_galat_di_kolom_konfirmasi(): void
    {
        $response = $this->from(route('daftar'))->post(route('daftar'), [
            'name' => 'Peternak Uji',
            'email' => 'uji@ransumku.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'berbeda123',
        ]);

        $response->assertRedirect(route('daftar'));
        $this->assertGuest();
        $this->followRedirects($response)->assertSee('Konfirmasi kata sandi tidak sama.');
    }

    public function test_daftar_berhasil_membuat_akun_dan_mengarahkan_ke_onboarding(): void
    {
        $response = $this->post(route('daftar'), [
            'name' => 'Peternak Baru',
            'email' => 'baru@ransumku.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('onboarding'));
        $this->assertAuthenticated();
        $this->assertTrue(User::where('email', 'baru@ransumku.test')->exists());
    }
}

