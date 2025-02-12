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
        Schema::create('ramassages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom_vendeur');
            $table->string('tel_vendeur');
            $table->string('adresse');
            $table->integer('nombre_colis');
            $table->integer('nombre_colis_ramasseur')->nullable();
            $table->decimal('frais_ramasseur')->nullable();
            $table->string('statut');
            $table->foreignId('tarif_id')->constrained();
            $table->string('destination');
            $table->foreignId('vendeur_id')->constrained(  table: 'users', indexName: 'ramassage_vendeur_id' );
            $table->foreignId('ramasseur_id')->nullable()->constrained(  table: 'users', indexName: 'ramassage_ramasseur_id' );
            $table->foreignId('created_by')->constrained(  table: 'users', indexName: 'ramassage_creator_id' );
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ramassages', function(Blueprint $table)
		{
            $table->dropForeign(['tarif_id']);
            $table->dropForeign(['vendeur_id']);
            $table->dropForeign(['ramasseur_id']);
            $table->dropForeign(['created_by']);
		});

        Schema::dropIfExists('colis');
    }
};
