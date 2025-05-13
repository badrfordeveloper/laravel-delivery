<?php

namespace App\Imports;

use App\Models\Zone;
use App\Models\Colis;
use App\Models\Pricing;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // If your Excel has headers

class ColisImport implements ToCollection, WithHeadingRow
{
    protected $user;
    protected $errors = [];
    protected $validatedRows = [];


    public function collection(Collection $rows)
    {
        // First validate all rows
        $this->validateAllRows($rows);

        // If any errors, throw exception with all errors
        if (!empty($this->errors)) {
            throw new \Exception(implode("\n", $this->errors));
        }

        // If all rows are valid, process them
        foreach ($this->validatedRows as $validatedData) {
            $this->createColis($validatedData);
        }
    }

    protected function validateAllRows(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because of header row and 0-based index

            try {
                // Transform the row data to match your store request format
                $data = [
                    'nom_client' => $row['nom_client'] ?? null,
                    'tel_client' => $row['tel_client'] ?? null,
                    'zone_id' => $this->getZoneId($row['zone'] ?? null),
                    'pricing_id' => $this->getPricingId($row['pricing'] ?? null),
                    'frais_livraison' => $row['frais_livraison'] ?? null,
                    'poids' => $row['poids'] ?? null,
                    'horaire' => $row['horaire'] ?? null,
                    'adresse' => $row['adresse'] ?? null,
                    'produit' => $row['produit'] ?? null,
                    'montant' => $row['montant'] ?? null,
                    'essayage' => $this->parseBoolean($row['essayage'] ?? null),
                    'ouvrir' => $this->parseBoolean($row['ouvrir'] ?? null),
                    'echange' => $this->parseBoolean($row['echange'] ?? null),
                    'commentaire_vendeur' => $row['commentaire_vendeur'] ?? null,
                ];

                // Validate the row data
                $validator = Validator::make($data, [
                    'nom_client' => 'required',
                    'tel_client' => 'required',
                    'zone_id' => 'required|exists:zones,id',
                    'pricing_id' => 'required|exists:pricings,id',
                    'frais_livraison' => 'required|numeric',
                    'poids' => 'required',
                    'horaire' => 'required',
                    'adresse' => 'required',
                    'produit' => 'required',
                    'montant' => 'required|numeric',
                    'essayage' => 'required|boolean',
                    'ouvrir' => 'required|boolean',
                    'echange' => 'required|boolean',
                ]);

                if ($validator->fails()) {
                    foreach ($validator->errors()->all() as $error) {
                        $this->errors[] = "Row {$rowNumber}: {$error}";
                    }
                } else {
                    // Add user data to validated rows
                    $data['user'] = $this->user;
                    $this->validatedRows[] = $data;
                }

            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }
    }



    protected function getZoneId($zoneName)
    {
        $zone = Zone::where('zone', $zoneName)->first();
        return $zone ? $zone->id : null;
    }

    protected function getPricingId($pricingName)
    {
        $pricing = Pricing::where('name', $pricingName)->first();
        return $pricing ? $pricing->id : null;
    }








    // Optional: Set chunk size for better performance with large files
    public function chunkSize(): int
    {
        return 100;
    }
}
