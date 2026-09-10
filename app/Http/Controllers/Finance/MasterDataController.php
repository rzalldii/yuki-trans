<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Tag;
use App\Models\Finance\Wallet;

class MasterDataController extends Controller
{
    public function index()
    {
        $wallets = Wallet::withCount('transactions')->orderBy('name')->get();
        $categories = Category::withCount('transactions')->orderBy('name')->get();
        $tags = Tag::withCount('transactions')->orderBy('name')->get();
        $recurrings = Recurring::with(['wallet', 'toWallet', 'category'])->orderByDesc('is_active')->orderBy('next_due_date')->get();
        $dueCount = $recurrings->filter(fn($r) => $r->is_active && $r->next_due_date && ($r->next_due_date->isPast() || $r->next_due_date->isToday()))->count();
        $currentMonth = now()->format('Y-m');
        return view('pages.finance.master-data', compact('wallets', 'categories', 'tags', 'recurrings', 'dueCount', 'currentMonth'));
    }
}