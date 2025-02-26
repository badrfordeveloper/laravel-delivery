<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Colis;
use App\Models\Tarif;
use App\Models\History;
use App\Models\Retour;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class RetourController extends Controller
{
    public function index(Request $request)
    {
        $textFilters = ['code','statut','nom_vendeur','tel_vendeur'];
        $query = Retour::query()->select('retours.*','ramasseurs.lastName AS ramasseur','ramasseurs.phone AS tel_ramasseur' ,'vendeurs.lastName AS vendeur' )

            ->leftJoin('users as ramasseurs', 'retours.ramasseur_id', '=', 'ramasseurs.id')
            ->leftJoin('users as vendeurs', 'retours.vendeur_id', '=', 'vendeurs.id');
        foreach ($textFilters  as $filter) {
            if( $filter == 'tel_vendeur' && $request->has($filter) && !empty($request->{$filter})){
                $query->where($filter,'like',"%".$request->{$filter}."%");
            }else if($request->has($filter) && !empty($request->{$filter})){
                $query->where($filter,'like',$request->{$filter}."%");
            }
        }
        $user = auth()->user();
        if($user->isVendeur()){
            $query->where('vendeur_id',$user->id);
        }else if ($user->isLivreur()){
            $query->where('ramasseur_id',$user->id);
        }

        $query->orderBy('retours.id','desc');
        $result = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $result->items(),
            'total' => $result->total(),
        ]);
    }


    public function colisCanRetour(Request $request)
    {
        $query = Colis::query();
        if($request->has('retour_id') && !empty($request->retour_id)){
            $query->where(function ($query) use($request) {
                $query->where('retour_id',$request->retour_id)->orWhere('retour_id',null);
            });
        }else{
            $query->where('retour_id',null);
        }

        if($request->has('statut_retour') && !empty($request->statut_retour)){
            $query->where('statut_retour','like',$request->statut_retour);
        }

        $user = auth()->user();
        if($user->isVendeur()){
            $query->where('vendeur_id',$user->id);
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
        Log::info('new retour : '.json_encode($request->all()));

        $request->validate([
            'nom_vendeur' => 'required',
            'tel_vendeur' => 'required',
            'tarif_id' => 'required',
            'adresse' => 'required',
            'colis_ids' => 'required|array|min:1'
        ]);

        $item = new Retour();
        $item->nom_vendeur = $request->nom_vendeur;
        $item->tel_vendeur = $request->tel_vendeur;
        $tarif = Tarif::find($request->tarif_id);
        $item->tarif_id = $tarif->id;
        $item->destination = $tarif->destination;
        $item->adresse = $request->adresse;
        $item->nombre_colis = count($request->colis_ids);

        $item->statut = 'EN_ATTENTE';
        $item->vendeur_id = $request->user()->id;
        $item->created_by = $request->user()->id;

        // retries if users generate the same code at the same time
        $tries= 0;
        $maxTries= 3;
        while($tries < $maxTries ){
            try {
                $item->code = $this->generateCode("RTR");
                $item->save();

                $history = new History();
                $history->statut = 'EN_ATTENTE';
                $item->histories()->save($history);

                $tries = $maxTries;
            } catch (QueryException $e) {
                logger('retour query exception'.$e->getMessage());
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
        // updates retour of colis
        Colis::whereIn('id', $request->colis_ids)->update(['retour_id' => $item->id]);

        return 'Retour bien ajoutée';
    }

    public function generateCode($prefix)
    {

        // Find the latest code with the same prefix
        $latestRetour = Retour::withTrashed()->where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        // Determine the next number in the sequence
        if ($latestRetour) {
            $lastNumber = (int) Str::substr($latestRetour->code, strlen($prefix)); // Extract the numeric part
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
        $item = Retour::query()->select('retours.*','ramasseurs.lastName AS ramasseur','ramasseurs.phone AS tel_ramasseur' ,'vendeurs.lastName AS vendeur' )
            ->leftJoin('users as ramasseurs', 'retours.ramasseur_id', '=', 'ramasseurs.id')
            ->leftJoin('users as vendeurs', 'retours.vendeur_id', '=', 'vendeurs.id')
            ->where('retours.id', $id)
            ->with('histories')
            ->with('colis')
            ->first();
        $filteredData = $item->toArray();
        $filteredData['colis_ids'] = $item->colis->pluck('id')->toArray();
        return $filteredData;
    }

    public function update(Request $request, string $id)
    {
        Log::info('update retour : '.$id.' => '.json_encode($request->all()));

        $request->validate([
            'nom_vendeur' => 'required',
            'tel_vendeur' => 'required',
            'tarif_id' => 'required',
            'adresse' => 'required'
        ]);

        $item = Retour::findOrFail($id);

        $item->nom_vendeur = $request->nom_vendeur;
        $item->tel_vendeur = $request->tel_vendeur;
        $item->adresse = $request->adresse;
        $item->nombre_colis = count($request->colis_ids);
        if($request->tarif_id != $item->tarif_id ){
            $tarif = Tarif::find($request->tarif_id);
            $item->tarif_id = $tarif->id;
            $item->destination = $tarif->destination;
        }
        $item->save();


        //sync retour_id in colis
         // remove all retour_id of colis
         Colis::where('retour_id',$item->id)->update(['retour_id' => null]);
         // updates retour of colis
         Colis::whereIn('id', $request->colis_ids)->update(['retour_id' => $item->id]);

        return 'Retour bien modifiée';

    }

    public function destroy($id)
    {
        Log::info('delete retour : '.$id);

        // Find the user by ID
        $item = Retour::findOrFail($id);
        Colis::where('retour_id',$item->id)->update(['retour_id' => null,'statut' => "EN_ATTENTE"]);
        $item->delete();
        return  'Retour bien supprimée' ;
    }

    public function parametrerRetour(Request $request)
    {
       Log::info('parametrerRetour  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Retour::findOrFail($request->id);
        if(in_array($item->statut,["EN_ATTENTE"])){
            $item->ramasseur_id = $request->ramasseur_id;
            $item->frais_ramasseur = $request->frais_ramasseur;
            $item->save();
            return  'Ramasseur bien modifiée' ;
        }
        else{
            return response()->json(['message' => 'Statut invalide'], 422);
        }
    }

    public function updateStatutRetour(Request $request)
    {
        Log::info('updateStatutRetour  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Retour::findOrFail($request->id);
        if($request->statut == "COMMENTAIRE"){
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "EN_COURS_RETOUR"  &&  in_array($item->statut,["PREPARER","REPORTE"])){
            $item->statut = $request->statut;
            $item->save();
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "RETOURNER"  &&  in_array($item->statut,["EN_COURS_RETOUR"])){
            $item->statut = $request->statut;
            $item->nombre_colis_ramasseur = $request->nombre_colis_ramasseur;
            $item->save();
            //update statut colis
            Colis::where('retour_id',$item->id)->update(['statut_retour' => "RETOURNE_AU_VENDEUR"]);
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->nombre_colis_ramasseur = $request->nombre_colis_ramasseur;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "REPORTE"  &&  in_array($item->statut,["EN_COURS_RETOUR"])){
            $item->statut = $request->statut;
            $item->date_reporte = $request->date;
            $item->save();
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $history->date = $request->date;
            $item->histories()->save($history);
        }
        else{
            return response()->json(['message' => 'Statut invalide'], 422);
        }

        return  'Statut bien modifiée' ;
    }

    public function scannerPreparer(Request $request)
    {
        Log::info('scannerEntrepot :  => '.json_encode($request->all()));

        $result=["success"=>[],"errors"=>[],"colisError"=>[]];

        $retour = Retour::findOrFail($request->retour_id);


        if(in_array($retour->statut,["EN_ATTENTE"])){
            //scannedColis
            if(count($request->scannedColis) > 0){
                $scannedColis = $request->scannedColis;
                $queryScannedColis = Colis::whereIn('code', $scannedColis)
                                        ->whereIn('statut_retour', ["RETOURNE_ENTREPOT"])
                                        ->where([
                                            ['retour_id', '=', $request->retour_id],
                                            ['vendeur_id', $retour->vendeur_id]
                                        ]);
                $countScannedColis = $queryScannedColis->count();
                if(count($scannedColis) == $countScannedColis){
                    $result['success'][]="les colis est bien modifiée";
                    logger('success commonColis');
                    //
                }else{
                    logger('error scannedColis');
                    $resutlScanned= $queryScannedColis->get('code')->pluck('code')->toArray();
                    $result['colisError'] = array_merge($result['colisError'], array_diff($scannedColis, $resutlScanned));
                    $result['errors'][]="les codes des colis erroné";
                }
            }

            // all is valid with no errors start the update
            if(empty($result['errors'])){
                //start the update
                $retour->statut ="PREPARER";
                $retour->save();
                //add history
                $history = new History();
                $history->statut = "PREPARER";
                $retour->histories()->save($history);
            } else {
                $result['success'] = [];
            }
        }else{
            $result['errors'][]="Statut de retour invalide";
        }

        return  $result ;
    }
}
