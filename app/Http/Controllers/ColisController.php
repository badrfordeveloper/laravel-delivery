<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Colis;
use App\Models\Tarif;
use App\Models\History;
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
        $query = Colis::query()->select('colis.*','livreurs.lastName AS livreur' ,'vendeurs.lastName AS vendeur')
        ->leftJoin('users as livreurs', 'colis.livreur_id', '=', 'livreurs.id')
            ->leftJoin('users as vendeurs', 'colis.vendeur_id', '=', 'vendeurs.id');
        foreach ($textFilters  as $filter) {
            if( $filter == 'tel_client' && $request->has($filter) && !empty($request->{$filter})){
                $query->where($filter,'like',"%".$request->{$filter}."%");
            }else if($request->has($filter) && !empty($request->{$filter})){
                $query->where($filter,'like',$request->{$filter}."%");
            }
        }
        $user = auth()->user();
        if($user->isVendeur()){
            $query->where('vendeur_id',$user->id);
        }else if ($user->isLivreur()){
            $query->where('livreur_id',$user->id);
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
        $latestColis = Colis::withTrashed()->where('code', 'like', $prefix . '%')
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

        $item = Colis::query()->select('colis.*','livreurs.lastName AS livreur','livreurs.phone AS tel_livreur' ,'vendeurs.lastName AS vendeur','vendeurs.phone AS tel_vendeur','ramassages.code AS code_ramassage'   )
            ->leftJoin('users as livreurs', 'colis.livreur_id', '=', 'livreurs.id')
            ->leftJoin('users as vendeurs', 'colis.vendeur_id', '=', 'vendeurs.id')
            ->leftJoin('ramassages', 'colis.ramassage_id', '=', 'ramassages.id')
            ->where('colis.id', $id)
            ->first();
            $historiesRamassage = $item->ramassage ? $item->ramassage->histories->toArray() : [];
            $histories = $item->histories->toArray() ?? [];
            $item->colisHistories = array_merge($histories  , $historiesRamassage);
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
        Log::info('delete colis : '.$id);
        // Find the user by ID
        $item = Colis::findOrFail($id);
        $item->delete();
        return  'Colis bien supprimée' ;
    }


    public function parametrerColis(Request $request)
    {
       Log::info('parametrerColis  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Colis::findOrFail($request->id);
        if(in_array($item->statut,["ENTREPOT"])){
            $item->livreur_id = $request->livreur_id;
            $item->frais_livreur = $request->frais_livreur;
            $item->save();
            return  'Colis bien modifiée' ;
        }
        else{
            return response()->json(['message' => 'Statut invalide'], 422);
        }
    }

    public function updateStatutColis(Request $request)
    {
        Log::info('updateStatutColis  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Colis::findOrFail($request->id);
        $oldStatut = $item->statut;

        if( $request->statut != "COMMENTAIRE" && is_null($item->livreur_id) ){
            return response()->json(['message' => 'Merci d\'assigner livreur'], 422);
        }

        if($request->statut == "COMMENTAIRE"){
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "EN_COURS_LIVRAISON"  &&  in_array($oldStatut,["ENTREPOT","REPORTE","PAS_REPONSE"])){
            $item->statut = $request->statut;
            $item->save();
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "LIVRE"  &&  in_array($oldStatut,["EN_COURS_LIVRAISON"])){
            $item->statut = $request->statut;
            $item->save();
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if(in_array($request->statut,["LIVRE_PARTIELLEMENT","ANNULE","PAS_REPONSE","REPORTE","REFUSE"])  &&  in_array($oldStatut,["EN_COURS_LIVRAISON"])){
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:jpg,jpeg,png,gif,pdf',  // Accepts both images and PDFs
                    'max:4096'                      // 4MB limit
                ]
            ]);
            $filePath = $request->file('file')->store('histories/colis/'.$item->code, 'public');

            $item->statut = $request->statut;
            if(in_array($request->statut,["LIVRE_PARTIELLEMENT","ANNULE","REFUSE"])){
                $item->statut_retour = "EN_ATTENTE_RETOUR";
            }

            $item->save();
            //add to history


            $history = new History();

            if(in_array($request->statut,["REPORTE"])){
                $history->date = $request->date;
            }
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $history->file_path = $filePath ;
            $item->histories()->save($history);
        }
        else{
            return response()->json(['message' => 'Statut invalide'], 422);
        }

        return  'Statut bien modifiée' ;
    }

    public function scannerRetourEntrepot(Request $request)
    {
        Log::info('scannerRetourEntrepot :  => '.json_encode($request->all()));

        $result=["success"=>[],"errors"=>[],"colisError"=>[]];

            //scannedColis
            if(count($request->scannedColis) > 0){
                $scannedColis = $request->scannedColis;
                $queryScannedColis = Colis::whereIn('code', $scannedColis)
                                        ->where('statut_retour', "EN_ATTENTE_RETOUR");
                $countScanned = $queryScannedColis->count();
                if(count($scannedColis) == $countScanned){
                    $result['success'][]="les colis est bien modifiée";
                    logger('success scannedColis');
                    //
                }else{
                    logger('error scannedColis');
                    $resutlScanned = $queryScannedColis->get('code')->pluck('code')->toArray();
                    $result['colisError'] = array_diff($scannedColis, $resutlScanned);
                    $result['errors'][]="les codes des colis erroné";
                }
            }

            // all is valid with no errors start the update
            if(empty($result['errors'])){
                if(count($request->scannedColis) > 0)
                    Colis::whereIn('code', $scannedColis)->update(['statut_retour' => "RETOURNE_ENTREPOT"]);



                // Retrieve all colis items that match the query
                $colisItems = Colis::whereIn('code', $scannedColis)->get();

                // Loop through each colis and save the history
                foreach ($colisItems as $colis) {
                    $history = new History();
                    $history->statut = "RETOURNE_ENTREPOT";
                    $colis->histories()->save($history);
                }

            } else {
                $result['success'] = [];
            }

        return  $result ;
    }

}
