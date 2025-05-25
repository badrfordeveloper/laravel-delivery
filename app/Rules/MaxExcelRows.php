<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MaxExcelRows implements Rule
{
    protected $maxRows;

    public function __construct($maxRows)
    {
        $this->maxRows = $maxRows;
    }

    public function passes($attribute, $value)
    {
        try {
            $rows = Excel::toArray(new \stdClass(), $value);
            return count($rows[0]) <= $this->maxRows;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function message()
    {
        return "Le fichier Excel ne peut pas contenir plus de {$this->maxRows} lignes.";
    }
}
