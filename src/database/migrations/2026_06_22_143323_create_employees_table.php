<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // ── Identitas ─────────────────────────────────────────────
            $table->string('id_number', 20)->unique(); // NIK karyawan
            $table->string('name');
            $table->string('nickname')->nullable();

            // ── Organisasi ────────────────────────────────────────────
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();

            // ── Status & Gender ───────────────────────────────────────
            $table->enum('status_karyawan', ['PKWTT', 'PKWT', 'HARIAN', 'DIREKTUR'])->default('PKWT');
            $table->enum('gender', ['L', 'P']);

            // ── Data Pribadi ──────────────────────────────────────────
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir');

            // Kolom virtual — dihitung otomatis MySQL, tidak disimpan ke disk
            // Laravel 11+: virtualAs() dihapus, diganti ->as()->virtuallyStored(false)
            $table->integer('usia')
                ->nullable()
                ->as('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE())')
                ->virtuallyStored(false);

            $table->enum('generation', ['Gen Z', 'Milenial', 'Gen X', 'Baby Boomers'])->nullable();

            // ── Kepegawaian ───────────────────────────────────────────
            $table->date('hire_date');
            $table->date('contract_end_date')->nullable();
            $table->string('pendidikan')->nullable()->comment('SD,SLTP,SMA,SMK,D3,S1,S2,S3');
            $table->string('jurusan')->nullable();

            // ── Alamat ────────────────────────────────────────────────
            $table->text('alamat_ktp')->nullable();
            $table->text('alamat_domisili')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('no_telepon', 20)->nullable();

            // ── Penilaian ─────────────────────────────────────────────
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->enum('performance_category', ['Low', 'Med', 'High'])->nullable();

            // ── Relasi ────────────────────────────────────────────────
            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ── Status aktif ──────────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->date('resign_date')->nullable();
            $table->string('resign_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // ── Index ─────────────────────────────────────────────────
            $table->index(['department_id', 'is_active']);
            $table->index(['position_id', 'is_active']);
            $table->index(['supervisor_id']);
            $table->index(['hire_date']);
            $table->index(['status_karyawan', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
