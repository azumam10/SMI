<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Grup dokumen per kategori per karyawan ────────────────────
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('category', [
                'foto',
                'cv',
                'ktp',
                'personal_data',
                'tes_lapangan',
                'hasil_wawancara',
                'skill_assessment',
                'kontrak_kerja',
                'surat_putusan',
                'surat_lamaran',
                'surat_pengalaman',
                'training_program',
                'sertifikat',
                'dokumen_pendukung',
            ]);
            $table->string('label')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Satu karyawan hanya boleh satu record per kategori
            $table->unique(['employee_id', 'category']);
            $table->index(['employee_id', 'category']);
        });

        // ── File fisik per dokumen (max 5 file per kategori) ─────────
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_document_id')
                ->constrained('employee_documents')
                ->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->tinyInteger('sort_order')->default(0);
            $table->string('uploaded_by')->nullable();
            $table->timestamps();

            $table->index(['employee_document_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_files');
        Schema::dropIfExists('employee_documents');
    }
};