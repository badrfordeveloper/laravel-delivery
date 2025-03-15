<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Colis;
use App\Models\Tarif;
use App\Models\History;
use App\Models\Ramassage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class RamassageController extends Controller
{
    public function index(Request $request)
    {
        $textFilters = ['code','statut','nom_vendeur','tel_vendeur'];
        $query = Ramassage::query()->select('ramassages.*','ramasseurs.lastName AS ramasseur','ramasseurs.phone AS tel_ramasseur' ,'vendeurs.lastName AS vendeur' )

            ->leftJoin('users as ramasseurs', 'ramassages.ramasseur_id', '=', 'ramasseurs.id')
            ->leftJoin('users as vendeurs', 'ramassages.vendeur_id', '=', 'vendeurs.id');
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

        $query->orderBy('ramassages.id','desc');
        $result = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $result->items(),
            'total' => $result->total(),
        ]);
    }


    public function colisForRamassage(Request $request)
    {
        $query = Colis::query();
        logger($request->all());
        if($request->has('ramassage_id') && !empty($request->ramassage_id)){
            $query->where(function ($query) use($request) {
                $query->where('ramassage_id',$request->ramassage_id)->orWhere('ramassage_id',null);
            });
        }else{
            $query->where('ramassage_id',null);
        }

        if($request->has('statut') && !empty($request->statut)){
            $query->where('statut','like',$request->statut);
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
        Log::info('new ramassage : '.json_encode($request->all()));

        $request->validate([
            'nom_vendeur' => 'required',
            'tel_vendeur' => 'required',
            'tarif_id' => 'required',
            'adresse' => 'required',
            'colis_ids' => 'required|array|min:1'
        ]);

        $item = new Ramassage();
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
                $item->code = $this->generateCode("RAM");
                $item->save();

                $history = new History();
                $history->statut = 'EN_ATTENTE';
                $item->histories()->save($history);

                $tries = $maxTries;
            } catch (QueryException $e) {
                logger('ramassage query exception'.$e->getMessage());
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
        // updates ramassage of colis
        Colis::whereIn('id', $request->colis_ids)->update(['ramassage_id' => $item->id]);

        return 'Ramassage bien ajoutée';
    }

    public function generateCode($prefix)
    {

        // Find the latest code with the same prefix
        $latestRamassage = Ramassage::withTrashed()->where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        // Determine the next number in the sequence
        if ($latestRamassage) {
            $lastNumber = (int) Str::substr($latestRamassage->code, strlen($prefix)); // Extract the numeric part
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
       /*
        $item = Ramassage::with('colis')->findOrFail($id);
        $item->makeHidden(['frais_ramasseur']);
        $filteredData = $item->toArray();
        $filteredData['colis'] = $item->colis->pluck('id')->toArray();
        return $filteredData;
        */


        $item = Ramassage::query()->select('ramassages.*','ramasseurs.lastName AS ramasseur','ramasseurs.phone AS tel_ramasseur' ,'vendeurs.lastName AS vendeur' )
            ->leftJoin('users as ramasseurs', 'ramassages.ramasseur_id', '=', 'ramasseurs.id')
            ->leftJoin('users as vendeurs', 'ramassages.vendeur_id', '=', 'vendeurs.id')
            ->where('ramassages.id', $id)
            ->with('histories')
            ->with('colis')
            ->first();
        $filteredData = $item->toArray();
        $filteredData['colis_ids'] = $item->colis->pluck('id')->toArray();
        return $filteredData;
    }

    public function update(Request $request, string $id)
    {
        Log::info('update ramassage : '.$id.' => '.json_encode($request->all()));

        $request->validate([
            'nom_vendeur' => 'required',
            'tel_vendeur' => 'required',
            'tarif_id' => 'required',
            'adresse' => 'required'
        ]);

        $item = Ramassage::findOrFail($id);

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


        //sync ramassage_id in colis
         // remove all ramassage_id of colis
         Colis::where('ramassage_id',$item->id)->update(['ramassage_id' => null]);
         // updates ramassage of colis
         Colis::whereIn('id', $request->colis_ids)->update(['ramassage_id' => $item->id]);

        return 'Ramassage bien modifiée';

    }

    public function destroy($id)
    {
        Log::info('delete ramassage : '.$id);

        // Find the user by ID
        $item = Ramassage::findOrFail($id);
        Colis::where('ramassage_id',$item->id)->update(['ramassage_id' => null,'statut' => "EN_ATTENTE"]);
        $item->delete();
        return  'Ramassage bien supprimée' ;
    }

    public function parametrerRamassage(Request $request)
    {
       Log::info('updateRamasseur  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Ramassage::findOrFail($request->id);
        if(in_array($item->statut,["EN_ATTENTE","EN_COURS_RAMASSAGE","REPORTE"])){
            $item->ramasseur_id = $request->ramasseur_id;
            $item->frais_ramasseur = $request->frais_ramasseur;
            $item->save();
            return  'Ramasseur bien modifiée' ;
        }
        else{
            return response()->json(['message' => 'Statut invalide'], 422);
        }
    }

    public function updateStatutRamassage(Request $request)
    {
        Log::info('updateStatutRamassage  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Ramassage::findOrFail($request->id);


        if( $request->statut != "COMMENTAIRE" && is_null($item->ramasseur_id) ){
            return response()->json(['message' => 'Merci d\'assigner ramasseur'], 422);
        }

        if($request->statut == "COMMENTAIRE"){
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "EN_COURS_RAMASSAGE"  &&  in_array($item->statut,["EN_ATTENTE","REPORTE"])){
            $item->statut = $request->statut;
            $item->save();
            //update statut colis
            Colis::where('ramassage_id',$item->id)->update(['statut' => $request->statut]);
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "RAMASSE"  &&  in_array($item->statut,["EN_COURS_RAMASSAGE","REPORTE"])){
            $item->statut = $request->statut;
            $item->nombre_colis_ramasseur = $request->nombre_colis_ramasseur;
            $item->save();
            //update statut colis
            Colis::where('ramassage_id',$item->id)->update(['statut' => $request->statut]);
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->nombre_colis_ramasseur = $request->nombre_colis_ramasseur;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "REPORTE"  &&  in_array($item->statut,["EN_COURS_RAMASSAGE","REPORTE"])){
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
        else if($request->statut == "ANNULE"  &&  in_array($item->statut,["EN_COURS_RAMASSAGE","REPORTE","RAMASSE"])){
            $item->statut = $request->statut;
            $item->save();
            //update statut colis
            Colis::where('ramassage_id',$item->id)->update(['statut' => "EN_ATTENTE",'ramassage_id' => null]);
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else{
            return response()->json(['message' => 'Statut invalide'], 422);
        }

        return  'Statut bien modifiée' ;
    }

    public function scannerEntrepot(Request $request)
    {
        Log::info('scannerEntrepot :  => '.json_encode($request->all()));

        $result=["success"=>[],"errors"=>[],"colisError"=>[]];
        $ramassage = Ramassage::findOrFail($request->ramassage_id);


        if(in_array($ramassage->statut,["RAMASSE"])){
            //commonColis
            if(count($request->commonColis) > 0){
                $commonColis = $request->commonColis;
                $queryCommonColis = Colis::whereIn('code', $commonColis)
                                        ->whereIn('statut', ['RAMASSE',"EN_COURS_RAMASSAGE","EN_ATTENTE"])
                                        ->where([
                                            ['ramassage_id', '=', $request->ramassage_id],
                                            ['vendeur_id', $ramassage->vendeur_id]
                                        ]);
                $countCommon = $queryCommonColis->count();
                if(count($commonColis) == $countCommon){
                    $result['success'][]="les colis trouver de remassage est bien modifiée";
                    logger('success commonColis');
                    //
                }else{
                    logger('error commonColis');
                    $resutlCommon = $queryCommonColis->get('code')->pluck('code')->toArray();
                    $result['colisError'] = array_merge($result['colisError'], array_diff($commonColis, $resutlCommon));
                    $result['errors'][]="les codes des colis erroné";
                }
            }
            //externeColis
            if(count($request->externeColis) > 0){

                $externeColis = $request->externeColis;
                $queryExterneColis = Colis::whereIn('code', $externeColis)
                                        ->whereIn('statut', ['RAMASSE',"EN_COURS_RAMASSAGE","EN_ATTENTE"])
                                        ->where([
                                            ['vendeur_id', $ramassage->vendeur_id]
                                        ]);
                $countExterne = $queryExterneColis->count();
                if(count($externeColis) == $countExterne){
                    $result['success'][]="les colis externe est bien modifiée";
                    logger('success externeColis');
                }else{
                    logger('error externeColis');
                    $resutlExterne = $queryExterneColis->get('code')->pluck('code')->toArray();
                    $result['colisError'] = array_merge($result['colisError'], array_diff($externeColis, $resutlExterne));
                    $result['errors'][]="les codes externe erroné";
                }
            }
            //missingColis
            if(count($request->missingColis) > 0){
                $missingColis = $request->missingColis;
                $queryMissingColis = Colis::whereIn('code', $missingColis)
                                        ->whereIn('statut', ['RAMASSE',"EN_COURS_RAMASSAGE","EN_ATTENTE"])
                                        ->where([
                                            ['vendeur_id', $ramassage->vendeur_id]
                                        ]);
                $countMissing = $queryMissingColis->count();
                if(count($missingColis) == $countMissing){
                    $result['success'][]="les colis manquant est bien modifiée";
                    logger('success missingColis');
                }else{
                    logger('error missingColis');
                    $resutlMissing = $queryMissingColis->get('code')->pluck('code')->toArray();
                    $result['colisError'] = array_merge($result['colisError'], array_diff($missingColis, $resutlMissing));
                    $result['errors'][]="les codes manquant erroné";
                }
            }
            // all is valid with no errors start the update
            if(empty($result['errors'])){
                if(count($request->commonColis) > 0)
                    Colis::whereIn('code', $commonColis)->update(['statut' => "ENTREPOT"]);
                if(count($request->externeColis) > 0)
                    Colis::whereIn('code', $externeColis)->update(['statut' => "ENTREPOT",'ramassage_id' => $ramassage->id]);
                if(count($request->missingColis) > 0)
                    Colis::whereIn('code', $missingColis)->update(['statut' => "EN_ATTENTE",'ramassage_id' => null]);
                //start the update
                $ramassage->statut ="ENTREPOT";
                $ramassage->save();
                //add history
                $history = new History();
                $history->statut = "ENTREPOT";
                $ramassage->histories()->save($history);
            } else {
                $result['success'] = [];
            }
        }else{
            $result['errors'][]="Statut de ramassage invalide";
        }

        return  $result ;
    }
}
