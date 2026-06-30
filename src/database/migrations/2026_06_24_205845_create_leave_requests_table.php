<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 1);
            $table->text('reason');
            $table->enum('status', [
                'pending',
                'supervisor_approved',
                'supervisor_rejected',
                'hrd_approved',
                'hrd_rejected',
                'cancelled'
            ])->default('pending');

            // Supervisor approval
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->text('supervisor_note')->nullable();

            // HRD approval
            $table->foreignId('hrd_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('hrd_approved_at')->nullable();
            $table->text('hrd_note')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_requests');
    }
};