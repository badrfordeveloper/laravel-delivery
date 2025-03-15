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
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('statut');
            $table->integer('nombre_livre')->nullable();
            $table->integer('nombre_livre_partiellement')->nullable();
            $table->integer('nombre_annule')->nullable();
            $table->integer('nombre_refuse')->nullable();
            $table->integer('nombre_ramassage')->nullable();
            $table->integer('nombre_retour')->nullable();
            $table->integer('nombre_total')->nullable();
            $table->decimal('frais_colis')->nullable();
            $table->decimal('frais_ramassage')->nullable();
            $table->decimal('frais_retour')->nullable();
            $table->decimal('frais_total')->nullable();
            $table->decimal('montant_encaisse')->nullable();
            $table->decimal('montant_facture')->nullable();
            $table->decimal('montant_gestionnaire')->nullable();
            $table->string('recu_path')->nullable();
            $table->foreignId('livreur_id')->nullable()->constrained(  table: 'users', indexName: 'facture_livreur_id' );
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
