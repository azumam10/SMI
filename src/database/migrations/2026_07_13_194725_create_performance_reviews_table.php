<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();

            // Karyawan yang dinilai (tetap ke employees)
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            // Penilai (user, bukan employee) - Supervisor atau HRD
            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Disetujui oleh (user) - HRD atau Super Admin
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Periode
            $table->year('year');
            $table->enum('semester', [1, 2]);

            // Nilai indikator
            $table->decimal('discipline_score', 5, 2);
            $table->decimal('quality_score', 5, 2);
            $table->decimal('teamwork_score', 5, 2);
            $table->decimal('ethic_score', 5, 2);
            $table->decimal('initiative_score', 5, 2);

            // Hasil perhitungan
            $table->decimal('final_score', 5, 2);
            $table->enum('category', [
                'Outstanding', 'Excellent', 'Good', 'Fair', 'Poor'
            ]);

            // Status workflow
            $table->enum('status', [
                'draft',        // belum disubmit (jika supervisor belum final)
                'submitted',    // supervisor sudah submit, menunggu HRD
                'approved',     // HRD setujui
                'revised'       // HRD minta revisi / sudah direvisi
            ])->default('draft');

            $table->timestamp('approved_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Unique constraint: satu karyawan per semester hanya satu penilaian
            $table->unique(['employee_id', 'year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};