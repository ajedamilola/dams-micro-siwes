<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Models\Loan\Loan;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OverviewController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['sometimes', 'nullable', 'date'],
            'to' => ['sometimes', 'nullable', 'date'],
        ]);

        $from = CarbonImmutable::parse($data['from'] ?? now()->subDays(29)->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? now()->toDateString())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        if ($from->diffInDays($to) > 366) {
            $from = $to->subDays(365)->startOfDay();
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $disbursements = DB::table('loans')
            ->whereNotNull('disbursement_date')
            ->whereBetween('disbursement_date', [$fromDate, $toDate])
            ->selectRaw('disbursement_date as day, SUM(principal) as total, COUNT(*) as loans')
            ->groupBy('disbursement_date')
            ->get()
            ->keyBy('day');

        $repayments = DB::table('loan_repayments')
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->selectRaw('
                payment_date as day,
                SUM(amount) as total,
                SUM(principal_component) as principal,
                SUM(interest_component) as interest,
                SUM(fee_component) as fee,
                SUM(penalty_component) as penalty,
                COUNT(*) as payments
            ')
            ->groupBy('payment_date')
            ->get()
            ->keyBy('day');

        $activity = [];
        for ($cursor = $from->startOfDay(); $cursor->lte($to); $cursor = $cursor->addDay()) {
            $key = $cursor->toDateString();
            $activity[] = [
                'date' => $key,
                'disbursed' => round((float) ($disbursements->get($key)?->total ?? 0), 2),
                'collected' => round((float) ($repayments->get($key)?->total ?? 0), 2),
            ];
        }

        $disbursed = $disbursements->sum(fn ($row) => (float) $row->total);
        $disbursedCount = (int) $disbursements->sum(fn ($row) => (int) $row->loans);
        $collected = $repayments->sum(fn ($row) => (float) $row->total);
        $collectedCount = (int) $repayments->sum(fn ($row) => (int) $row->payments);
        $interest = $repayments->sum(fn ($row) => (float) $row->interest);
        $fees = $repayments->sum(fn ($row) => (float) $row->fee);
        $penalties = $repayments->sum(fn ($row) => (float) $row->penalty);
        $principalCollected = $repayments->sum(fn ($row) => (float) $row->principal);

        $outstandingSub = DB::table('loan_schedules as schedule')
            ->join('loans', 'schedule.loan_id', '=', 'loans.id')
            ->where('loans.status', 'active')
            ->whereNull('loans.deleted_at');

        $outstanding = (float) (clone $outstandingSub)
            ->selectRaw('COALESCE(SUM(schedule.total_due - schedule.principal_paid - schedule.interest_paid - schedule.fee_paid - schedule.penalty_paid), 0) as total')
            ->value('total');

        $portfolio = (clone $outstandingSub)
            ->join('loan_products', 'loans.loan_product_id', '=', 'loan_products.id')
            ->selectRaw('loan_products.name as label, COALESCE(SUM(schedule.total_due - schedule.principal_paid - schedule.interest_paid - schedule.fee_paid - schedule.penalty_paid), 0) as value')
            ->groupBy('loan_products.id', 'loan_products.name')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'key' => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $row->label) ?: 'product'),
                'label' => $row->label,
                'value' => round((float) $row->value, 2),
            ])
            ->filter(fn (array $row) => $row['value'] > 0)
            ->values()
            ->all();

        $statusCounts = Loan::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $overdue = DB::table('loan_schedules as schedule')
            ->join('loans', 'schedule.loan_id', '=', 'loans.id')
            ->where('loans.status', 'active')
            ->whereNull('loans.deleted_at')
            ->whereDate('schedule.due_date', '<', now()->toDateString())
            ->whereRaw('(schedule.total_due - schedule.principal_paid - schedule.interest_paid - schedule.fee_paid - schedule.penalty_paid) > 0')
            ->distinct('loans.id')
            ->count('loans.id');

        $recent = Loan::query()
            ->whereBetween('created_at', [$from, $to])
            ->with([
                'customer:id,first_name,middle_name,last_name,business_name,customer_type',
                'product:id,name,code',
            ])
            ->orderByDesc('created_at')
            ->limit(7)
            ->get()
            ->map(fn (Loan $loan) => [
                'id' => $loan->id,
                'loan_no' => $loan->loan_no,
                'customer' => $loan->customer?->display_name ?? 'Unknown customer',
                'product' => $loan->product?->name,
                'principal' => (float) $loan->principal,
                'status' => $loan->status,
                'disbursement_date' => $loan->disbursement_date?->toDateString(),
                'created_at' => $loan->created_at?->toDateString(),
            ])
            ->all();

        return Inertia::render('Loan/Overview', [
            'filters' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'stats' => [
                'disbursed' => round($disbursed, 2),
                'disbursed_count' => $disbursedCount,
                'collected' => round($collected, 2),
                'collected_count' => $collectedCount,
                'interest' => round($interest, 2),
                'fees' => round($fees, 2),
                'penalties' => round($penalties, 2),
                'principal_collected' => round($principalCollected, 2),
                'outstanding' => round($outstanding, 2),
                'active_loans' => (int) $statusCounts->get('active', 0),
                'pending_approvals' => (int) $statusCounts->get('pending_approval', 0),
                'overdue_loans' => $overdue,
            ],
            'activity' => $activity,
            'portfolio' => $portfolio,
            'status' => $statusCounts->map(fn ($count, $status) => [
                'key' => $status,
                'label' => str_replace('_', ' ', (string) $status),
                'count' => (int) $count,
            ])->values()->all(),
            'recent' => $recent,
        ]);
    }
}
