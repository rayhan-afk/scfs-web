<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom usulan_fee_merchant.
     *
     * Beda dengan `persentase_fee_merchant` (kolom AKTIF setelah LKBB approve).
     * Kolom `usulan_fee_merchant` adalah PROPOSAL dari merchant saat onboarding
     * yang akan direview oleh LKBB. Saat approve, nilai usulan di-copy
     * ke `persentase_fee_merchant`. Saat reject, nilai usulan tetap tersimpan
     * sehingga merchant tahu nominal sebelumnya saat revise.
     */
    public function up(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->decimal('usulan_fee_merchant', 5, 2)->nullable()->after('persentase_fee_merchant');
        });
    }

    public function down(): void
    {
        Schema::table('merchant_profiles', function (Blueprint $table) {
            $table->dropColumn('usulan_fee_merchant');
        });
    }
};
