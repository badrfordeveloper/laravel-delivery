<?php

namespace App\Imports;

use App\Models\Zone;
use App\Models\Colis;
use App\Models\Pricing;
use App\Services\ColisService;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // If your Excel has headers
use Maatwebsite\Excel\Concerns\WithValidation;

class ColisImport implements ToCollection,SkipsEmptyRows, WithHeadingRow,WithValidation
{
    protected $maxRows = 2;

    public function prepareForValidation($row, $index)
    {
       // Transform the row data to match your store request format
        $zone_id = $this->getZoneId($row['zone'] ?? null);
        $data = [
            'nom_client' => $row['nom_client'] ?? null,
            'tel_client' => $row['tel_client'] ?? null,
            'zone_id' => $zone_id,
            'pricing_id' => $this->getPricingId($zone_id,($row['poids'] ?? null)),
            'poids' => $row['poids'] ?? null,
            'horaire' => "normale",
            'adresse' => $row['adresse'] ?? null,
            'produit' => $row['produit'] ?? null,
            'montant' => $row['montant'] ?? null,
            'essayage' => ($row['essayage'] ?? null) == 'oui',
            'ouvrir' => ($row['ouvrir'] ?? null) == 'oui',
            'echange' => ($row['echange'] ?? null) == 'oui',
            'commentaire_vendeur' => $row['commentaire_vendeur'] ?? null,
        ];
        return $data;
    }

    public function rules(): array
    {
        return [
                    'nom_client' => 'required',
                    'tel_client' => 'required',
                    'zone_id' => 'required',
                    'pricing_id' => 'required',
                    'poids' => 'required',
                    'horaire' => 'required',
                    'adresse' => 'required',
                    'produit' => 'required',
                    'montant' => 'required|numeric',
                    'essayage' => 'required|boolean',
                    'ouvrir' => 'required|boolean',
                    'echange' => 'required|boolean',
                ];
    }


    public function collection(Collection $rows)
    {

        $colisService = resolve(ColisService::class);
        // If all rows are valid, process them
        foreach ($rows as $validatedData) {
            $colisService->createColis($validatedData);
        }
    }

    public function customValidationMessages()
    {
        return [
            'zone_id.required' => 'Zone est introuvable',
            'pricing_id.required' => 'Poids sur ce zone est introuvable',
        ];
    }

    public function customValidationAttributes()
    {
        return [
            'nom_client' => 'nom client',
            'tel_client' => 'tel client',
            'zone_id' => 'zone',
            'pricing_id' => 'zone et poids'
        ];
    }

    protected function getZoneId($zoneName)
    {
        $zone = Zone::where('zone', $zoneName)->first();
        return $zone ? $zone->id : null;
    }

    protected function getPricingId($zone_id,$poids)
    {
        $pricing = Pricing::where('zone_id', $zone_id)->where('poids',$poids)->first();
        return $pricing ? $pricing->id : null;
    }








    /* // Optional: Set chunk size for better performance with large files
    public function chunkSize(): int
    {
        return 100;
    } */
}
