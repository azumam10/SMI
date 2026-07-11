<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {

            $table->id();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('start_date');

            $table->date('end_date');

            $table->decimal('total_days', 5, 1);

            $table->decimal('approved_days', 5, 1)
                ->nullable();

            $table->text('reason');

            $table->enum('status', [
                'pending',
                'supervisor_approved',
                'supervisor_rejected',
                'hrd_approved',
                'hrd_rejected',
                'cancelled',
            ])->default('pending');

            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamp('supervisor_approved_at')
                ->nullable();

            $table->text('supervisor_note')
                ->nullable();

            $table->foreignId('hrd_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamp('hrd_approved_at')
                ->nullable();

            $table->text('hrd_note')
                ->nullable();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            // Index
            $table->index(['employee_id', 'status']);
            $table->index(['status', 'supervisor_id']);
            $table->index(['status', 'hrd_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_requests');
    }
};
