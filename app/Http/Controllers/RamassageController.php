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
            ->with('histories')
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
            $query->where('statut','like',$request->statut."%");
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
            'colis' => 'required|array|min:1'
        ]);

        $item = new Ramassage();
        $item->nom_vendeur = $request->nom_vendeur;
        $item->tel_vendeur = $request->tel_vendeur;
        $tarif = Tarif::find($request->tarif_id);
        $item->tarif_id = $tarif->id;
        $item->destination = $tarif->destination;
        $item->adresse = $request->adresse;
        $item->nombre_colis = count($request->colis);

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
        Colis::whereIn('id', $request->colis)->update(['ramassage_id' => $item->id]);

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
        $item = Ramassage::with('colis')->findOrFail($id);
        $item->makeHidden(['frais_ramasseur']);
        $filteredData = $item->toArray();
        $filteredData['colis'] = $item->colis->pluck('id')->toArray();
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
        $item->nombre_colis = count($request->colis);
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
         Colis::whereIn('id', $request->colis)->update(['ramassage_id' => $item->id]);

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

    public function updateRamasseur(Request $request)
    {
       Log::info('updateRamasseur  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Ramassage::findOrFail($request->id);
        if(in_array($item->statut,["EN_ATTENTE","EN_COURS_RAMASSAGE","REPORTE"])){
            $item->ramasseur_id = $request->ramasseur_id;
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
        if($request->statut == "COMMENTAIRE"){
            $user = auth()->user();
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "EN_COURS_RAMASSAGE"  &&  in_array($item->statut,["EN_ATTENTE"])){
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
        else if($request->statut == "ANNULE"  &&  in_array($item->statut,["EN_COURS_RAMASSAGE","REPORTE"])){
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
}
