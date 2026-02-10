<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $companyFilter = $user->role === 'super_admin' ? null : $user->company_id;

        $ticketQuery = Ticket::query();
        $assetQuery = Asset::query();

        if ($companyFilter) {
            $ticketQuery->where('company_id', $companyFilter);
            $assetQuery->where('company_id', $companyFilter);
        }

        $totalTickets = (clone $ticketQuery)->count();
        $openTickets = (clone $ticketQuery)->where('status', 'open')->count();
        $inProgressTickets = (clone $ticketQuery)->where('status', 'in_progress')->count();
        $waitingPartsTickets = (clone $ticketQuery)->where('status', 'waiting_parts')->count();
        $closedTickets = (clone $ticketQuery)->where('status', 'closed')->count();
        $totalAssets = $assetQuery->count();

        return response()->json([
            'totalTickets' => $totalTickets,
            'openTickets' => $openTickets,
            'inProgressTickets' => $inProgressTickets,
            'waitingPartsTickets' => $waitingPartsTickets,
            'closedTickets' => $closedTickets,
            'totalAssets' => $totalAssets,
        ]);
    }

    public function ticketsByStatus(Request $request)
    {
        $user = $request->user();
        $query = Ticket::query();

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        }

        $data = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        return response()->json($data);
    }

    public function ticketsByPriority(Request $request)
    {
        $user = $request->user();
        $query = Ticket::query();

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        }

        $data = $query->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();

        return response()->json($data);
    }

    public function assetsByType(Request $request)
    {
        $user = $request->user();
        $query = Asset::query();

        if ($user->role !== 'super_admin') {
            $query->where('company_id', $user->company_id);
        }

        $data = $query->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        return response()->json($data);
    }
}
