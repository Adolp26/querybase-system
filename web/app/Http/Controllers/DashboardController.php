<?php

namespace App\Http\Controllers;

use App\Models\Query;
use App\Models\Datasource;
use App\Models\QueryExecution;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalQueries = Query::count();
        $activeQueries = Query::active()->count();
        $totalDatasources = Datasource::count();
        $activeDatasources = Datasource::active()->count();

        $executionStats = QueryExecution::getStats(7);
        $topQueries = QueryExecution::getTopQueries(5, 7);
        $slowestQueries = QueryExecution::getSlowestQueries(5, 7);

        $recentExecutions = QueryExecution::with('parentQuery')
            ->latest('executed_at')
            ->limit(10)
            ->get();

        $recentQueries = Query::with('datasource')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalQueries',
            'activeQueries',
            'totalDatasources',
            'activeDatasources',
            'executionStats',
            'topQueries',
            'slowestQueries',
            'recentExecutions',
            'recentQueries'
        ));
    }
}
