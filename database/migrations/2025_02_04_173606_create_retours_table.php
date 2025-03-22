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
        Schema::create('retours', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('statut');
            $table->foreignId('vendeur_id')->constrained(  table: 'users', indexName: 'retour_vendeur_id' );
            $table->foreignId('ramasseur_id')->nullable()->constrained(  table: 'users', indexName: 'retour_ramasseur_id' );
            $table->foreignId('facture_livreur_id')->nullable()->constrained(  table: 'factures', indexName: 'retour_facture_livreur_id' );
            $table->foreignId('facture_vendeur_id')->nullable()->constrained(  table: 'factures', indexName: 'retour_facture_vendeur_id' );
            $table->decimal('frais_ramasseur')->nullable();
            $table->integer('nombre_colis');
            $table->integer('nombre_colis_ramasseur')->nullable();
            $table->dateTime('date_reporte')->nullable();
            $table->string('nom_vendeur');
            $table->foreignId('tarif_id')->constrained();
            $table->string('destination');
            $table->string('tel_vendeur');
            $table->string('adresse');
            $table->foreignId('created_by')->constrained(  table: 'users', indexName: 'retour_creator_id' );
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retours', function(Blueprint $table)
		{
            $table->dropForeign(['tarif_id']);
            $table->dropForeign(['vendeur_id']);
            $table->dropForeign(['ramasseur_id']);
            $table->dropForeign(['facture_livreur_id']);
            $table->dropForeign(['facture_vendeur_id']);
            $table->dropForeign(['created_by']);
		});

        Schema::dropIfExists('retours');
    }
};
