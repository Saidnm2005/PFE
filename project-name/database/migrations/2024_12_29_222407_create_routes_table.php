<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('routes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('driver_id')->constrained('users');
    $table->decimal('total_distance')->nullable();
    $table->decimal('total_time')->nullable();
    $table->json('route_data')->nullable(); // Store the optimized route details
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
