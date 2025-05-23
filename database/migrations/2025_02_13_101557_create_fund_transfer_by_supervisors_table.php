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
        Schema::create('fund_transfer_by_supervisors', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('transaction_id')->nullable()->default(null)->unique();
            $table->foreignId('admin_id')->nullable()->default(null)->constrained('users')->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->default(null)->constrained('teams')->onDelete('cascade');
            $table->foreignId('supervisor_id')->nullable()->default(null)->constrained('users')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_transfer_by_supervisors');
    }
};
