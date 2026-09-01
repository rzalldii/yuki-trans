<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceRecurring;
use App\Models\Finance\FinanceTag;
use App\Models\Finance\FinanceWallet;

class FinanceSettingController extends Controller
{
    public function index()
    {
        $wallets = FinanceWallet::withCount('transactions')->orderBy('name')->get();
        $categories = FinanceCategory::withCount('transactions')->orderBy('name')->get();
        $tags = FinanceTag::withCount('transactions')->orderBy('name')->get();
        $recurrings = FinanceRecurring::with(['wallet', 'category'])->orderByDesc('is_active')->orderBy('next_due_date')->get();
        $currentMonth = now()->format('Y-m');
        return view('pages.finance.finance-setting', compact('wallets', 'categories', 'tags', 'recurrings', 'currentMonth'));
    }
}