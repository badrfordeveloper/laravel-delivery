<?php

namespace App\Http\Controllers;

use App\Models\Pricing;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ZoneController extends Controller
{
    public function index(Request $request)
    {
        $textFilters = ['zone','prefix'];
        $query = Zone::query()
        ->select('zones.*','vl.name as ville')
        ->leftJoin('villes as vl', 'zones.ville_id', '=', 'vl.id');
        foreach ($textFilters  as $filter) {

            if($request->has($filter) && !empty($request->{$filter})){
             $query->where("zones.".$filter,'like',$request->{$filter}."%");
            }
        }
        $query->with('pricings');
        $query->orderBy('id','desc');
        if($request->has('itemsPerPage')){
            $result = $query->paginate($request->itemsPerPage);

            return response()->json([
                'items' => $result->items(),
                'total' => $result->total(),
            ]);
        }else{
            $result = $query->get();
            return response()->json([
                'items' => $result,
                'total' => $result->count(),
            ]);
        }

    }

    public function store(Request $request)
    {
        Log::info('new zone : '.json_encode($request->all()));

        $request->validate([
            'zone' => [
                'required',
                'string',
                Rule::unique('zones')->where(function ($query) use ($request) {
                    return $query->where('ville_id', $request->ville_id);
                })
            ],
            'prefix' => 'required|string|unique:zones',
            'delai_livraison' => 'required|string',
            'horaires' => 'nullable|array',
            'pricings.*.poids' => 'required',
            'pricings.*.frais_livraison' => 'required',
            'pricings.*.frais_livreur' => 'required',
        ]);

        $item = new Zone();
        $item->zone = $request->zone;
        $item->ville_id = $request->ville_id;
        $item->prefix = $request->prefix;
        $item->delai_livraison = $request->delai_livraison;
        $item->horaires = $request->horaires;
        $item->save();
        foreach ($request->pricings as $pricing) {
            $newPrincing = new Pricing();
            Log::info('new pricing : '.json_encode( $pricing));
            $newPrincing->zone_id = $item->id;
            $newPrincing->poids = $pricing['poids'];
            $newPrincing->frais_livraison = $pricing['frais_livraison'];
            $newPrincing->frais_livreur = $pricing['frais_livreur'];
            $newPrincing->save();
        }


        return 'Zone bien ajoutée';
    }

    public function show($id)
    {
        $item = Zone::with('pricings')->findOrFail($id);

        return $item;
    }

    public function update(Request $request, $id)
    {
        Log::info('updating zone '.$id.' : '.json_encode($request->all()));

        $request->validate([
            'zone' => [
                'required',
                'string',
                Rule::unique('zones')
                    ->where(function ($query) use ($request) {
                        return $query->where('ville_id', $request->ville_id);
                    })
                    ->ignore($id) // Ignore the current record
            ],
            'prefix' => 'required|string|unique:zones,prefix,'.$id,
            'delai_livraison' => 'required|string',
            'horaires' => 'nullable|array',
            'pricings.*.poids' => 'required',
            'pricings.*.frais_livraison' => 'required',
            'pricings.*.frais_livreur' => 'required',
        ]);

        // Find the existing zone
        $item = Zone::findOrFail($id);

        // Update zone attributes
        $item->zone = $request->zone;
        $item->ville_id = $request->ville_id;
        $item->prefix = $request->prefix;
        $item->delai_livraison = $request->delai_livraison;
        $item->horaires = $request->horaires;
        $item->save();

        // Handle pricings - sync existing with new data
        $existingPricings = $item->pricings->keyBy('poids'); // Key by poids or another unique field
        $processedIds = [];

        foreach ($request->pricings as $pricingData) {
            // Find existing pricing by poids (or another unique field)
            $pricing = $existingPricings->get($pricingData['poids']) ?? new Pricing();

            $pricing->zone_id = $item->id;
            $pricing->poids = $pricingData['poids'];
            $pricing->frais_livraison = $pricingData['frais_livraison'];
            $pricing->frais_livreur = $pricingData['frais_livreur'];
            $pricing->save();

            $processedIds[] = $pricing->id;
            Log::info('updated pricing : '.json_encode($pricing));
        }

        // Delete any pricings that weren't in the request
        $item->pricings()->whereNotIn('id', $processedIds)->delete();

        return 'Zone bien modifiée';
    }

    /* public function destroy($id)
    {
        // Find the user by ID
        $item = Zone::findOrFail($id);
        $item->delete();
        return  'Zone bien supprimée' ;
    } */
}
