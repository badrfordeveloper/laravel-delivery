<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardAdminController extends Controller
{

    public function headerStatistics(Request $request)
    {
         // Get last week's start (Monday) and end (Sunday)
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
         $userId = auth()->id();

        // Colis statistics
        $colisStats = DB::table('colis')
            ->select(
                DB::raw('COUNT(*) as total_colis'),
                DB::raw('SUM(montant) as total_montant'),
                DB::raw('SUM(CASE WHEN statut = "LIVRE" OR statut = "LIVRE_PARTIELLEMENT" THEN 1 ELSE 0 END) as livraison_success')
            )
            ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
            ->first();
        // Ramassage statistics
        $ramassageTotal = DB::table('ramassages')
             ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
            ->count();

        // Retour statistics
        $retourTotal = DB::table('retours')
            ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
            ->count();

        // Calculate delivery rate
        $deliveryRate = 0;
        if ($colisStats->total_colis > 0) {
            $deliveryRate = round(($colisStats->livraison_success / $colisStats->total_colis) * 100, 2);
        }

        return response()->json([
            'nombre_colis' => $colisStats->total_colis,
            'total_montant_colis' => $colisStats->total_montant ?? 0,
            'taux_livraison' => $deliveryRate,
            'total_ramassage' => $ramassageTotal,
            'total_retour' => $retourTotal,
        ]);
    }

    public function colisByZonePercent()
    {

          // Get last week's start (Monday) and end (Sunday)
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
         $userId = auth()->id();

        $totalColis = DB::table('colis')->count();

        $results = DB::table('zones')
            ->leftJoin('colis', 'zones.id', '=', 'colis.zone_id')
            ->select(
                'zones.zone',
                DB::raw('COUNT(colis.id) as colis_count'),
                DB::raw('CASE WHEN ? > 0 THEN ROUND(COUNT(colis.id) * 100.0 / ?, 2) ELSE 0 END as percentage')
            )
            ->setBindings([$totalColis, $totalColis])
            ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])

            ->groupBy('zones.zone')
            ->orderBy('colis_count', 'desc')
            ->limit(6)
            ->get();

        return response()->json( $results);
    }


    public function stasticsByDay()
    {
        return response()->json([
            'colis' => $this->ColisOfTheWeekByDay(),
        ]);
    }

    public function ColisOfTheWeekByDay(){

        $userId = auth()->id();
        // Set Carbon locale to French
        Carbon::setLocale('fr');
        // Define last week's date range (Monday to Sunday)
        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $endDate = Carbon::now()->subWeek()->endOfWeek();

        // Initialize arrays for all days of the week
        $daysOfWeek = [];
        $dayNames = [];
        $currentDay = $startDate->copy();

        // Generate all days in the week with French abbreviations
        $frenchDayMap = [
            'Mon' => 'Lun',
            'Tue' => 'Mar',
            'Wed' => 'Mer',
            'Thu' => 'Jeu',
            'Fri' => 'Ven',
            'Sat' => 'Sam',
            'Sun' => 'Dim'
        ];

        while ($currentDay <= $endDate) {
            $dayKey = $currentDay->format('Y-m-d');
            $daysOfWeek[$dayKey] = 0;

            // Get English short day name and map to French
            $englishDay = $currentDay->format('D');
            $dayNames[] = $frenchDayMap[$englishDay] ?? $englishDay;

            $currentDay->addDay();
        }

        // Get colis counts for each day of last week
        $colisCounts = DB::table('colis')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->pluck('count', 'date');

        // Merge the actual counts with our initialized days
        foreach ($colisCounts as $date => $count) {
            $daysOfWeek[$date] = $count;
        }

        return [
            'days' => $dayNames,
            'values' => array_values($daysOfWeek)
        ];

    }

    public function listFacutres()
    {

        // Get last week's start (Monday) and end (Sunday)
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();
        $userId = auth()->id();


        $result = Facture::query()
            ->whereBetween('factures.created_at', [$startOfLastWeek, $endOfLastWeek])
            ->limit(6)
            ->orderBy('id','desc')->get();

        return response()->json($result);
    }

    public function suiviColis()
    {
        // Get last week's start (Monday) and end (Sunday)
        $startOfLastWeek = Carbon::now()->subWeek()->startOfWeek();
        $endOfLastWeek = Carbon::now()->subWeek()->endOfWeek();

         $userId = auth()->id();




        // Get total colis count for the month
        $totalColis = DB::table('colis')
            ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])

            ->count();

        // Get count for each statut in a single query
        $statutStats = DB::table('colis')
            ->selectRaw("
                SUM(CASE WHEN statut = 'EN_ATTENTE' THEN 1 ELSE 0 END) as en_attente,
                SUM(CASE WHEN statut = 'ENTREPOT' THEN 1 ELSE 0 END) as entrepot,
                SUM(CASE WHEN statut = 'EN_COURS_LIVRAISON' THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN statut = 'LIVRE' THEN 1 ELSE 0 END) as livre,
                SUM(CASE WHEN statut = 'LIVRE_PARTIELLEMENT' THEN 1 ELSE 0 END) as livre_partiellement,
                SUM(CASE WHEN statut = 'REFUSE' THEN 1 ELSE 0 END) as refuse,
                SUM(CASE WHEN statut = 'ANNULE' THEN 1 ELSE 0 END) as annule
            ")

            ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
            ->first();

        // Calculate percentages
        $percentages = [];
        if ($totalColis > 0) {
            $percentages = [
                'EN_ATTENTE' => round(($statutStats->en_attente / $totalColis) * 100, 2),
                'ENTREPOT' => round(($statutStats->entrepot / $totalColis) * 100, 2),
                'EN_COURS_LIVRAISON' => round(($statutStats->en_cours / $totalColis) * 100, 2),
                'DELIVERY_SUCCESS' => round((($statutStats->livre + $statutStats->livre_partiellement) / $totalColis) * 100, 2),
                'REFUSE' => round(($statutStats->refuse / $totalColis) * 100, 2),
                'ANNULE' => round(($statutStats->annule / $totalColis) * 100, 2),
            ];
        } else {
            $percentages = [
                'EN_ATTENTE' => 0,
                'ENTREPOT' => 0,
                'EN_COURS_LIVRAISON' => 0,
                'DELIVERY_SUCCESS' => 0,
                'REFUSE' => 0,
                'ANNULE' => 0,
            ];
        }

        return response()->json($percentages);
    }
}
