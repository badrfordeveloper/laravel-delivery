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
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            $table->string('statut');
            $table->string('commentaire')->nullable();
            $table->string('path')->nullable();
            $table->integer('nombre_colis_ramasseur')->nullable();
            $table->string('date')->nullable();
            $table->integer('historiable_id');
            $table->string('historiable_type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
