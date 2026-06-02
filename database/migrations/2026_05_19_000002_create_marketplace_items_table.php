<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->index();
            $table->string('transaction_type')->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('rent_rate', 12, 2)->nullable();
            $table->string('unit', 40);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('condition')->nullable();
            $table->string('location')->index();
            $table->date('harvest_date')->nullable();
            $table->text('description');
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_available')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_items');
    }
};
