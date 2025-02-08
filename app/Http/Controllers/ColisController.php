<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Colis;
use App\Models\Tarif;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class ColisController extends Controller
{
    public function index(Request $request)
    {
        $textFilters = ['code','statut','nom_client','tel_client'];
        $query = Colis::query();
        foreach ($textFilters  as $filter) {
            if($request->has($filter) && !empty($request->{$filter})){
             $query->where($filter,'like',$request->{$filter}."%");
            }
        }
        $query->orderBy('id','desc');
        $result = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $result->items(),
            'total' => $result->total(),
        ]);
    }

    public function store(Request $request)
    {
        Log::info('new colis : '.json_encode($request->all()));

        $request->validate([
            'nom_client' => 'required',
            'tel_client' => 'required',
            'tarif_id' => 'required',
            'frais_livraison' => 'required',
            'adresse' => 'required',
            'produit' => 'required',
            'montant' => 'required',
            'essayage' => 'required',
            'ouvrir' => 'required',
            'echange' => 'required'
        ]);

        $item = new Colis();
        $item->nom_client = $request->nom_client;
        $item->tel_client = $request->tel_client;
        $tarif = Tarif::find($request->tarif_id);
        $item->tarif_id = $tarif->id;
        $item->frais_livraison = $tarif->tarif;
        $item->destination = $tarif->destination;
        $item->adresse = $request->adresse;
        $item->produit = $request->produit;
        $item->montant = $request->montant;
        $item->commentaire_vendeur = $request->commentaire_vendeur;
        $item->essayage = $request->boolean('essayage');
        $item->ouvrir = $request->boolean('ouvrir');
        $item->echange = $request->boolean('echange');
        $item->statut = 'EN_ATTENTE';
        $item->vendeur_id = $request->user()->id;
        $item->created_by = $request->user()->id;

        // retries if users generate the same code at the same time
        $tries= 0;
        $maxTries= 3;
        while($tries < $maxTries ){
            try {
                $item->code = $this->generateCode($tarif->prefix);
                $item->save();
                $tries = $maxTries;
                return 'Colis bien ajoutée';
            } catch (QueryException $e) {
                logger('colis query exception'.$e->getMessage());
                sleep(1);
                // If a duplicate key error occurs, retry
                if ($e->errorInfo[1] == 1062) { // MySQL error code for duplicate entry
                    if($tries == $maxTries-1){
                        throw $e;
                    }
                    $tries++;
                }else{
                    throw $e;
                }
            }
        }


    }

    public function generateCode($prefix)
    {

        // Find the latest code with the same prefix
        $latestColis = Colis::where('code', 'like', $prefix . '%')
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
        $formattedNumber = str_pad($nextNumber, 7, '0', STR_PAD_LEFT);

        // Combine prefix and number to create the code
        return $prefix . $formattedNumber;
    }

    public function show($id)
    {
        $item = Colis::findOrFail($id);
        return $item;
    }

    public function update(Request $request, string $id)
    {
        Log::info('update colis : '.$id.' => '.json_encode($request->all()));

        $request->validate([
            'nom_client' => 'required',
            'tel_client' => 'required',
            'tarif_id' => 'required',
            'frais_livraison' => 'required',
            'adresse' => 'required',
            'produit' => 'required',
            'montant' => 'required',
            'essayage' => 'required',
            'ouvrir' => 'required',
            'echange' => 'required'
        ]);
        $item = Colis::findOrFail($id);

        $item->nom_client = $request->nom_client;
        $item->tel_client = $request->tel_client;
        $item->adresse = $request->adresse;
        $item->produit = $request->produit;
        $item->montant = $request->montant;
        $item->commentaire_vendeur = $request->commentaire_vendeur;
        $item->essayage = $request->boolean('essayage');
        $item->ouvrir = $request->boolean('ouvrir');
        $item->echange = $request->boolean('echange');
        $item->statut = 'EN_ATTENTE';
        $item->vendeur_id = $request->user()->id;
        $item->created_by = $request->user()->id;

        // retries if users generate the same code at the same time
        $tries= 0;
        $maxTries= 3;
        while($tries < $maxTries ){
            try {
                // check if updated destination
                if($request->tarif_id != $item->tarif_id ){
                    $tarif = Tarif::find($request->tarif_id);
                    $item->tarif_id = $tarif->id;
                    $item->frais_livraison = $tarif->tarif;
                    $item->destination = $tarif->destination;
                    $logCode = $item->code;
                    $item->code = $this->generateCode($tarif->prefix);
                    $item->save();
                    $tries = $maxTries;
                    Log::info('update code colis : '. $logCode.' => '.$item->code);

                    return 'Colis bien modifiée avec nouveau un code : '. $item->code;
                }
                $item->save();
                $tries = $maxTries;
                return 'Colis bien modifiée';
            } catch (QueryException $e) {
                logger('colis query exception'.$e->getMessage());
                sleep(1);
                // If a duplicate key error occurs, retry
                if ($e->errorInfo[1] == 1062) { // MySQL error code for duplicate entry
                    if($tries == $maxTries-1){
                        throw $e;
                    }
                    $tries++;
                }else{
                    throw $e;
                }
            }
        }


    }

    public function destroy($id)
    {
        // Find the user by ID
        $item = Colis::findOrFail($id);
        $item->delete();
        return  'Colis bien supprimée' ;
    }
}
