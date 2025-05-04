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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone');
            $table->string('prefix')->unique();
            $table->string('delai_livraison');//24h 48h
            $table->json('horaires')->nullable();
            $table->foreignId('ville_id')->constrained();

            $table->softDeletes();

            $table->unique(['ville_id', 'zone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
