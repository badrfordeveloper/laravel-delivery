<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tarif;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class TarifController extends Controller
{
    public function index(Request $request)
    {
        $textFilters = ['destination','prefix'];
        $query = Tarif::query();
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
        Log::info('new tarif : '.json_encode($request->all()));

        $request->validate([
            'destination' => 'required|unique:tarifs',
            'prefix' => 'required|unique:tarifs',
            'tarif' => 'required|numeric',
            'delai_livraison' => 'required',
        ]);

        $item = new Tarif();
        $item->destination = $request->destination;
        $item->prefix = $request->prefix;
        $item->tarif = $request->tarif;
        $item->delai_livraison = $request->delai_livraison;
        $item->save();
        return 'Tarif bien ajoutée';
    }

    public function show($id)
    {
        $item = Tarif::findOrFail($id);
        return $item;
    }

    public function update(Request $request, string $id)
    {
        Log::info('update tarif : '.$id.' => '.json_encode($request->all()));
        $request->validate([
            'destination' => [
                'required',
                Rule::unique('tarifs')->ignore($id), // Exclude current user ID
            ],
            'prefix' => [
                'required',
                Rule::unique('tarifs')->ignore($id), // Exclude current user ID
            ],
            'tarif' => 'required|numeric',
            'delai_livraison' => 'required',
        ]);

        $item = Tarif::findOrFail($id);
        $item->destination = $request->destination;
        $item->prefix = $request->prefix;
        $item->tarif = $request->tarif;
        $item->delai_livraison = $request->delai_livraison;
        $item->save();

        return 'Tarif bien modifiée';
    }

    /* public function destroy($id)
    {
        // Find the user by ID
        $item = Tarif::findOrFail($id);
        $item->delete();
        return  'Tarif bien supprimée' ;
    } */
}
