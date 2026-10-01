<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assignee_id')->constrained('users');
            $table->foreignId('checker_id')->nullable()->constrained('users');
            $table->enum('status', ['WAITING', 'ON_PROCESS', 'ON_CHECK', 'DONE'])->default('WAITING');
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM');
            $table->dateTime('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('assignee_id');
            $table->index('checker_id');
            $table->index('created_by');
            $table->index('priority');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
