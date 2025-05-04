<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Colis;
use App\Models\Facture;
use App\Models\History;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FactureVendeurController extends Controller
{
    public function index(Request $request)
    {
        $textFilters = ['code'];
        $selectsFilters = ["statut" =>"factures.statut" ,"vendeur_id" => "factures.vendeur_id"];

        $query = Facture::query()->select('factures.*','vendeurs.store AS vendeur' )
            ->leftJoin('users as vendeurs', 'factures.vendeur_id', '=', 'vendeurs.id')
            ->whereNotNull('vendeur_id');

        foreach ($selectsFilters  as $filter => $filterValue) {
            if( $request->has($filter) && $request->{$filter}!="" ){
                $query->where($filterValue,$request->{$filter});
            }
        }
        foreach ($textFilters  as $filter) {
            if($request->has($filter) && !empty($request->{$filter})){
                $query->where($filter,'like',$request->{$filter}."%");
            }
        }
        $user = auth()->user();
        if ($user->isVendeur()){
            $query->where('vendeur_id',$user->id);
        }

        $from = Carbon::parse($request->begin_date)->startOfDay()->toDateTimeString();
        $to = Carbon::parse($request->end_date)->endOfDay()->toDateTimeString();
        $query->whereBetween('factures.updated_at', [$from, $to]);

        $query->orderBy('id','desc');

        $result = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $result->items(),
            'total' => $result->total(),
        ]);
    }


    public function show($id)
    {

        $item = Facture::query()->select('factures.*','vendeurs.lastName AS vendeur','vendeurs.phone AS tel_vendeur'   )
            ->leftJoin('users as vendeurs', 'factures.vendeur_id', '=', 'vendeurs.id')
            ->where('factures.id', $id)
            ->with('histories','colisVendeur','ramassagesVendeur','retoursVendeur')
            ->first();

        return $item;
    }


    public function updateStatutFactureVendeur(Request $request)
    {
        Log::info('updateStatutFactureVendeur  : '.json_encode($request->all()));

        // Find the user by ID
        $item = Facture::findOrFail($request->id);
        $oldStatut = $item->statut;
        Log::info('oldStatut  : '.$oldStatut);

        if($request->statut == "COMMENTAIRE"){
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
            $item->histories()->save($history);
        }
        else if($request->statut == "EN_COURS"  &&  in_array($oldStatut,["EN_ATTENTE"])){
        /*     $request->validate([
                'file' => 'required|file|image|max:2048',
            ]);
            $filePath = $request->file('file')->store('histories/factures/vendeurs/'.$item->code, 'public'); */
            $item->statut = $request->statut;
           /*  $item->montant_gestionnaire = $request->montant; */
        /*     $item->recu_path = $filePath; */
            $item->save();
            //add to history
            $history = new History();
            $history->statut = $request->statut;
            $history->commentaire = $request->commentaire;
           /*  $history->montant = $request->montant; */
            /* $history->file_path = $filePath; */
            $item->histories()->save($history);
        }
        else if($request->statut == "PAYE"  &&  in_array($oldStatut,["EN_COURS"])){
            $item->statut = $request->statut;
            $item->save();
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


    public function generateVendeurFactures(Request $request)
    {

        $vendeurs = User::role('vendeur')->get();

        // Iterate through each vendeur
        foreach ($vendeurs as $vendeur) {
            $result = [
                'nombre_livre' => 0,
                'nombre_livre_partiellement' => 0,
                'nombre_annule' => 0,
                'nombre_refuse' => 0,
                'nombre_ramassage' => 0,
                'nombre_retour' => 0,
                'nombre_total' => 0,
                'frais_colis' => 0,
                'frais_ramassage' => 0,
                'frais_retour' => 0,
                'frais_total' => 0,
                'montant_encaisse' => 0,
                'montant_facture' => 0,
            ];
            // Get the colis for this vendeur, grouped by statut
            $queryColis =  $vendeur->colisVendeur()
                                    ->where('facture_vendeur_id',null)
                                    ->whereIn('statut', ['LIVRE', 'LIVRE_PARTIELLEMENT', 'ANNULE', 'REFUSE']);

            $colisGrouped = $queryColis
                        ->selectRaw('statut, count(*) as colis_count, sum(frais_livraison) as total_frais_livraison, sum(montant) as total_montant')
                        ->groupBy('statut')
                        ->get();

            foreach ($colisGrouped as $group) {
                $result['nombre_'.strtolower($group->statut)] = $group->colis_count;
                $result['nombre_total'] += $group->colis_count;
                if(in_array($group->statut,['LIVRE', 'LIVRE_PARTIELLEMENT'])){
                    $result['frais_colis'] += $group->total_frais_livraison;
                    $result['montant_encaisse'] += $group->total_montant;
                }
            }
            // Get the retours for this vendeur, grouped by statut
            $queryRetour =  $vendeur->retoursVendeur()
                                    ->where('facture_vendeur_id',null)
                                    ->where('statut','RETOURNER');
            $retoursGrouped = $queryRetour
                            ->selectRaw('statut, count(*) as retour_count, sum(frais_ramasseur) as total_frais_ramasseur')
                            ->groupBy('statut')
                            ->get();

            foreach ($retoursGrouped as $retour) {
                $result['nombre_total'] += $retour->retour_count;
                $result['nombre_retour'] = $retour->retour_count;
              /*   $result['frais_retour'] += $retour->total_frais_ramasseur; */
            }

            // Get the ramassages for this vendeur, grouped by statut
            $queryRamassage =  $vendeur->ramassagesVendeur()
                                        ->where('facture_vendeur_id',null)
                                        ->where('statut','ENTREPOT');
            $ramassageGroup = $queryRamassage
                            ->selectRaw('statut, count(*) as ramassage_count, sum(frais_ramasseur) as total_frais_ramasseur')
                            ->groupBy('statut')
                            ->get();

            foreach ($ramassageGroup as $ramassage) {
                $result['nombre_total'] += $ramassage->ramassage_count;
                $result['nombre_ramassage'] = $ramassage->ramassage_count;
               // $result['frais_ramassage'] +=$ramassage->total_frais_ramasseur;
            }

            // calcul frais and montant
            $result['frais_total'] = $result['frais_colis'] + $result['frais_ramassage'] + $result['frais_retour'];
            $result['montant_facture'] = $result['montant_encaisse'] - $result['frais_total'] ;
            //create facture
            if($result['nombre_total'] > 0 ){
                //add facture
                $item = new Facture();
                $item->code = $this->generateCode("FVD");
                $item->statut = "EN_ATTENTE" ;
                $item->vendeur_id =  $vendeur->id ;
                $item->nombre_livre = $result['nombre_livre'] ;
                $item->nombre_livre_partiellement = $result['nombre_livre_partiellement'] ;
                $item->nombre_refuse = $result['nombre_refuse'] ;
                $item->nombre_annule = $result['nombre_annule'] ;
                $item->nombre_ramassage = $result['nombre_ramassage'] ;
                $item->nombre_retour = $result['nombre_retour'] ;
                $item->nombre_total = $result['nombre_total'] ;
                $item->frais_colis = $result['frais_colis'] ;
                $item->frais_ramassage = $result['frais_ramassage'] ;
                $item->frais_retour = $result['frais_retour'] ;
                $item->frais_total = $result['frais_total'] ;
                $item->montant_encaisse = $result['montant_encaisse'] ;
                $item->montant_facture = $result['montant_facture'] ;
                $item->save();
                // add history
                $history = new History();
                $history->statut = "EN_ATTENTE";
                $item->histories()->save($history);

                //assign colis factures
                $queryColis->update(['facture_vendeur_id' => $item->id]);
                //assign retours factures
                $queryRetour->update(['facture_vendeur_id' => $item->id]);
                //assign ramassages factures
                $queryRamassage->update(['facture_vendeur_id' => $item->id]);
            }
        }
        return  "Les factures bien générer";
    }

    public function generateCode($prefix)
    {
        // Find the latest code with the same prefix
        $latestRamassage = Facture::withTrashed()->where('code', 'like', $prefix . '%')
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

}

