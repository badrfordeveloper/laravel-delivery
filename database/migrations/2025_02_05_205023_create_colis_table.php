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
        Schema::create('colis', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('statut');
            $table->string('statut_retour')->nullable();
            $table->decimal('frais_livraison');
            $table->decimal('frais_livreur')->nullable();
            $table->decimal('montant');
            $table->foreignId('vendeur_id')->constrained(  table: 'users', indexName: 'colis_vendeur_id' );
            $table->foreignId('livreur_id')->nullable()->constrained(  table: 'users', indexName: 'colis_livreur_id' );
            $table->foreignId('facture_livreur_id')->nullable()->constrained(  table: 'factures', indexName: 'colis_facture_livreur_id' );
            $table->foreignId('facture_vendeur_id')->nullable()->constrained(  table: 'factures', indexName: 'colis_facture_vendeur_id' );
            $table->foreignId('ramassage_id')->nullable()->constrained();
            $table->foreignId('retour_id')->nullable()->constrained();
            $table->string('nom_client');
            $table->string('tel_client');
            $table->string('destination');
            $table->string('adresse');
            $table->string('produit');
            $table->string('commentaire_vendeur')->nullable();
            $table->boolean('essayage');
            $table->boolean('ouvrir');
            $table->boolean('echange');
            $table->foreignId('tarif_id')->constrained();
            $table->foreignId('created_by')->constrained(  table: 'users', indexName: 'colis_creator_id' );
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colis', function(Blueprint $table)
		{
            $table->dropForeign(['ramassage_id']);
            $table->dropForeign(['tarif_id']);
            $table->dropForeign(['vendeur_id']);
            $table->dropForeign(['livreur_id']);
            $table->dropForeign(['facture_livreur_id']);
            $table->dropForeign(['facture_vendeur_id']);
            $table->dropForeign(['created_by']);
		});

        Schema::dropIfExists('colis');
    }
};
