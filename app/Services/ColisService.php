<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\Colis;
use App\Models\Pricing;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ColisService
{

    public function createColis($data){

        $item = new Colis();
        $item->nom_client = $data['nom_client'];
        $item->tel_client = $data['tel_client'];
        $item->horaire = $data['horaire'];

        $zone = Zone::find($data['zone_id']);
        $item->zone_id = $zone->id;
        $item->destination = $zone->zone;

        $pricing = Pricing::find($data['pricing_id']);
        $item->pricing_id = $pricing->id;
        $item->poids = $pricing->poids;
        $item->frais_livraison = $pricing->frais_livraison;
        $item->frais_livreur = $pricing->frais_livreur;

        $item->adresse = $data['adresse'];
        $item->produit = $data['produit'];
        $item->montant = $data['montant'];
        $item->commentaire_vendeur = $data['commentaire_vendeur'] ?? null;
        $item->essayage = $data['essayage'];
        $item->ouvrir = $data['ouvrir'];
        $item->echange = $data['echange'];
        $item->statut = 'EN_ATTENTE';
        $item->vendeur_id = auth()->user()->id;
        $item->created_by = auth()->user()->id;

        // Generate unique code with retries
        $tries = 0;
        $maxTries = 3;

        while ($tries < $maxTries) {
            try {
                $item->code = $this->generateCode($zone->prefix);
                $item->save();
                $tries = $maxTries;
                break;
            } catch (QueryException $e) {
                Log::error('Colis import query exception: '.$e->getMessage());
                sleep(1);

                if ($e->errorInfo[1] == 1062) { // MySQL duplicate entry
                    if ($tries == $maxTries - 1) {
                        throw new \Exception("Failed to generate unique code after {$maxTries} attempts");
                    }
                    $tries++;
                } else {
                    throw $e;
                }
            }
        }
    }

    public function generateCode($prefix)
    {
        $code = $prefix.'0';
        // Find the latest code with the same prefix
        $latestColis = Colis::withTrashed()->where('code', 'like', $code . '%')
            ->orderBy('code', 'desc')
            ->first();

        // Determine the next number in the sequence
        if ($latestColis) {
            $lastNumber = (int) Str::substr($latestColis->code, strlen($prefix)); // Extract the numeric part
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // Format the number with leading zeros
        $formattedNumber = str_pad($nextNumber, 9, '0', STR_PAD_LEFT);

        // Combine prefix and number to create the code
        return $prefix . $formattedNumber;
    }

}
