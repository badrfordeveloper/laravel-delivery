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
            $table->string('nom_client');
            $table->string('tel_client');
            $table->string('tarif');
            $table->string('destination');
            $table->string('adresse');
            $table->string('produit');
            $table->string('montant');
            $table->string('commentaire_vendeur');
            $table->string('commentaire_livreur');
            $table->string('essayage');
            $table->string('ouvrir');
            $table->string('echange');
            $table->string('frais_livreur');
            $table->foreignId('tarif_id')->constrained();
          /*   $table->foreignId('vendeur_id')->constrained();
            $table->foreignId('livereu_id')->nullable()->constrained();
            $table->foreignId('created_by')->nullable()->constrained(); */
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
            $table->dropForeign(['tarif_id']);
		});

        Schema::dropIfExists('colis');
    }
};
