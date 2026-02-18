<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
           Schema::create('stations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('restrict');
    $table->string('name');
    $table->decimal('lat', 10, 8);
    $table->decimal('lng', 11, 8);
    $table->enum('status',['delivered','pending','assigned'])->default('pending');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
