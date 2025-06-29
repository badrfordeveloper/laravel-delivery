<?php

use App\Models\Colis;


if (!function_exists('checkColisAccess')) {

    function checkColisAccess($id){
        $item = Colis::findOrFail($id);
        $user = auth()->user();
        if(!$user->isManager()){
            if($user->isVendeur()){
                if($item->vendeur_id != $user->id){
                    abort(403, 'Unauthorized action.');
                }
            }else if ($user->isLivreur()){
                if($item->livreur_id != $user->id){
                    abort(403, 'Unauthorized action.');
                }
            }else{
                abort(403, 'Unauthorized action.');
            }
        }
        return true;
    }

    function checkAccessManager(){
        if(!auth()->user()->isManager()){
            abort(403, 'Unauthorized action.');
        }
        return true;
    }
}
