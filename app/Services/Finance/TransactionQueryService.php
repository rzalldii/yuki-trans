<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\TransactionType;
use App\Models\Finance\Category;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TransactionQueryService
{
    public function getIndexData(Request $request): array
    {
        $categories = Category::orderBy('name')->get();
        $wallets = Wallet::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $ledger = $this->getTransactionsLedger($request, $startDate, $endDate);
        [$totalIncome, $totalExpense] = $this->getMonthlySummary($startDate, $endDate);
        $netBalance = $this->getNetBalance();
        $filterCategories = $categories->pluck('name')->unique()->sort()->values();
        $filterTypes = collect(['income', 'expense', 'transfer']);
        $filterTags = $tags->pluck('name')->unique()->sort()->values();
        $currentMonthLabel = ($startDate === '2020-01-01' && !$request->has('start_date'))
            ? 'All Time'
            : Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        return compact(
            'wallets',
            'categories',
            'tags',
            'ledger',
            'filterCategories',
            'filterTypes',
            'filterTags',
            'totalIncome',
            'totalExpense',
            'netBalance',
            'currentMonthLabel',
            'startDate',
            'endDate'
        );
    }

    public function resolveDateRange(Request $request): array
    {
        $hasEntityFilter = $request->filled('wallet') || $request->filled('category') || $request->filled('tag');
        $startDate = (string) $request->input('start_date', $hasEntityFilter ? '2020-01-01' : now()->startOfMonth()->toDateString());
        $endDate = (string) $request->input('end_date', $hasEntityFilter ? now()->addYear()->toDateString() : now()->endOfMonth()->toDateString());
        return [$startDate, $endDate];
    }

    public function getTransactionsLedger(Request $request, string $startDate, string $endDate): LengthAwarePaginator|Collection
    {
        $query = Transaction::with(['user', 'wallet', 'category', 'transferPair.wallet', 'tags'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', '!=', TransactionType::TransferIn->value)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
        if ($request->has('page') || $request->boolean('paginate')) {
            $perPage = min((int) $request->input('per_page', 50), 100);
            return $query->paginate($perPage)->withQueryString();
        }
        return $query->take(1000)->get();
    }

    public function getMonthlySummary(string $startDate, string $endDate): array
    {
        $version = Cache::get('finance_summary_version', 1);
        $summaryCacheKey = "finance_summary_{$startDate}_{$endDate}_v{$version}";
        $monthlySummary = Cache::remember($summaryCacheKey, 3600, function () use ($startDate, $endDate) {
            return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                ")
                ->first();
        });
        $totalIncome = (float) ($monthlySummary->total_income ?? 0);
        $totalExpense = (float) ($monthlySummary->total_expense ?? 0);
        return [$totalIncome, $totalExpense];
    }

    public function getNetBalance(): float
    {
        return (float) Cache::rememberForever('finance_net_balance', function () {
            return Wallet::sum('current_balance');
        });
    }
}