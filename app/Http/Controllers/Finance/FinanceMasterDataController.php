<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceRecurring;
use App\Models\Finance\FinanceTag;
use App\Models\Finance\FinanceWallet;

class FinanceMasterDataController extends Controller
{
    public function index()
    {
        $wallets = FinanceWallet::withCount('transactions')->orderBy('name')->get();
        $categories = FinanceCategory::withCount('transactions')->orderBy('name')->get();
        $tags = FinanceTag::withCount('transactions')->orderBy('name')->get();
        $recurrings = FinanceRecurring::with(['wallet', 'toWallet', 'category'])->orderByDesc('is_active')->orderBy('next_due_date')->get();
        $dueCount = $recurrings->filter(fn($r) => $r->is_active && $r->next_due_date && ($r->next_due_date->isPast() || $r->next_due_date->isToday()))->count();
        $currentMonth = now()->format('Y-m');
        return view('pages.finance.master-data', compact('wallets', 'categories', 'tags', 'recurrings', 'dueCount', 'currentMonth'));
    }
}