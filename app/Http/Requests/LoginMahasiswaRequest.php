<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginMahasiswaRequest extends FormRequest
{
    /**
     * Tentukan apakah user diizinkan melakukan request ini.
     * Ubah menjadi TRUE agar semua orang yang mau login bisa lewat gerbang depan.
     */
    public function authorize()
    {
        return true; 
    }

    /**
     * Aturan validasi (Pengecekan KTP/Data).
     */
    public function rules()
    {
        return [
            'email'    => 'required|email',
            'password' => 'required',
        ];
    }

    /**
     * (Opsional) Kustomisasi pesan error agar lebih ramah dibaca di Flutter.
     * Jika tidak dibuat, Laravel akan membalas pakai bahasa Inggris bawaan.
     */
    public function messages()
    {
        return [
            'email.required'    => 'Email mahasiswa tidak boleh kosong.',
            'email.email'       => 'Format email tidak valid (harus menggunakan @).',
            'password.required' => 'Kata sandi tidak boleh kosong.',
        ];
    }
}