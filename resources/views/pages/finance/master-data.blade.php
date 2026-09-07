@extends('layouts.app')
@section('title', 'Finance Master Data')
@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Finance Master Data</li>
@endsection
@push('style')
    <link rel="stylesheet" href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.css') }}">
@endpush
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @php
            $totalWalletBalance = $wallets->sum('current_balance');
            $activeTab = request('tab', 'wallets');
            $dueCount = $dueCount ?? $recurrings->filter(fn($r) => $r->is_active && $r->next_due_date && ($r->next_due_date->isPast() || $r->next_due_date->isToday()))->count();
        @endphp
        <div class="nav-align-top mb-4">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'wallets' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-wallets" aria-controls="tab-wallets" aria-selected="{{ $activeTab == 'wallets' ? 'true' : 'false' }}">
                        <i class="bx bx-wallet me-1" aria-hidden="true"></i> Wallets ({{ $wallets->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'categories' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-categories" aria-controls="tab-categories" aria-selected="{{ $activeTab == 'categories' ? 'true' : 'false' }}">
                        <i class="bx bx-category me-1" aria-hidden="true"></i> Categories ({{ $categories->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'tags' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-tags" aria-controls="tab-tags" aria-selected="{{ $activeTab == 'tags' ? 'true' : 'false' }}">
                        <i class="bx bx-purchase-tag me-1" aria-hidden="true"></i> Tags ({{ $tags->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'recurring' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-recurring" aria-controls="tab-recurring" aria-selected="{{ $activeTab == 'recurring' ? 'true' : 'false' }}">
                        <i class="bx bx-sync me-1" aria-hidden="true"></i> Recurring ({{ $recurrings->count() }})
                    </button>
                </li>
            </ul>
        </div>
        <div class="tab-content bg-transparent p-0 shadow-none">
            <div class="tab-pane fade {{ $activeTab == 'wallets' ? 'show active' : '' }}" id="tab-wallets" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h5 class="mb-0">Finance Wallets</h5>
                            <div class="d-flex align-items-center gap-2 border-start ps-3">
                                <span class="text-muted small">Total Balance:</span>
                                <span class="badge bg-label-primary">
                                    Rp {{ number_format($totalWalletBalance, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" id="createNewWallet" data-entity="wallet" data-action="create">
                            <i class="bx bx-plus me-1" aria-hidden="true"></i>Add Wallet
                        </button>
                    </div>
                </div>
                <div class="row g-4">
                    @forelse ($wallets as $wallet)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-md">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="bx bx-wallet" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-0 fw-semibold">{{ $wallet->name }}</h5>
                                                <span class="badge bg-label-secondary mt-1">
                                                    {{ $wallet->transactions_count ?? 0 }} Transactions
                                                </span>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn p-0 text-muted" type="button" id="walletMenu_{{ $wallet->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Wallet options">
                                                <i class="bx bx-dots-vertical-rounded" aria-hidden="true"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="walletMenu_{{ $wallet->id }}">
                                                <a class="dropdown-item text-warning editWalletBtn" href="javascript:void(0);" data-id="{{ $wallet->id }}"><i class="bx bx-edit-alt me-2" aria-hidden="true"></i>Edit</a>
                                                <a class="dropdown-item text-danger deleteWalletBtn" href="javascript:void(0);" data-id="{{ $wallet->id }}"><i class="bx bx-trash me-2" aria-hidden="true"></i>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-2 border-top">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small">Initial Balance</span>
                                            <span class="text-muted small">Rp {{ number_format($wallet->initial_balance, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-baseline mt-2">
                                            <span class="text-muted small">Current Balance</span>
                                            <h4 class="mb-0 {{ $wallet->current_balance < 0 ? 'text-danger' : 'text-primary' }}">
                                                Rp {{ number_format($wallet->current_balance, 0, ',', '.') }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card text-center py-5">
                                <div class="card-body">
                                    <h5 class="mb-2">No wallets available.</h5>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="tab-pane fade {{ $activeTab == 'categories' ? 'show active' : '' }}" id="tab-categories" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Finance Categories</h5>
                        <button type="button" class="btn btn-primary" id="createNewCategory" data-entity="category" data-action="create">
                            <i class="bx bx-plus me-1" aria-hidden="true"></i>Add Category
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="fw-semibold text-success mb-3 d-flex align-items-center gap-2">
                                    <span class="badge bg-label-success p-2 rounded-circle"><i class="bx bx-trending-up" aria-hidden="true"></i></span>
                                    Income
                                </h6>
                                <div class="list-group">
                                    @forelse ($categories->where('type', 'income') as $category)
                                        @php $hasAmount = (float) $category->amount > 0; $amount = $category->amount; @endphp
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <h6 class="mb-0 fw-semibold">{{ $category->name }}</h6>
                                                        <span class="badge bg-label-secondary">{{ $category->transactions_count ?? 0 }} Transactions</span>
                                                    </div>
                                                    @if($hasAmount)
                                                        <span class="badge bg-label-primary font-monospace"><i class="bx bx-wallet me-1" aria-hidden="true"></i>Target: Rp {{ number_format($amount, 0, ',', '.') }}</span>
                                                    @else
                                                        <div class="d-inline-flex align-items-center text-muted small fst-italic"><i class="bx bx-infinite me-1" aria-hidden="true"></i>No Target</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning editCategoryBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-id="{{ $category->id }}" aria-label="Edit">
                                                    <i class="bx bx-edit-alt" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteCategoryBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-id="{{ $category->id }}" aria-label="Delete">
                                                    <i class="bx bx-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="list-group-item text-center text-muted py-4">No income categories available.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-semibold text-danger mb-3 d-flex align-items-center gap-2">
                                    <span class="badge bg-label-danger p-2 rounded-circle"><i class="bx bx-trending-down" aria-hidden="true"></i></span>
                                    Expense
                                </h6>
                                <div class="list-group">
                                    @forelse ($categories->where('type', 'expense') as $category)
                                        @php $hasAmount = (float) $category->amount > 0; $amount = $category->amount; @endphp
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <h6 class="mb-0 fw-semibold">{{ $category->name }}</h6>
                                                        <span class="badge bg-label-secondary">{{ $category->transactions_count ?? 0 }} Transactions</span>
                                                    </div>
                                                    @if($hasAmount)
                                                        <span class="badge bg-label-primary font-monospace"><i class="bx bx-wallet me-1" aria-hidden="true"></i>Budget: Rp {{ number_format($amount, 0, ',', '.') }}</span>
                                                    @else
                                                        <div class="d-inline-flex align-items-center text-muted small fst-italic"><i class="bx bx-infinite me-1" aria-hidden="true"></i>No Budget</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning editCategoryBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-id="{{ $category->id }}" aria-label="Edit">
                                                    <i class="bx bx-edit-alt" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteCategoryBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-id="{{ $category->id }}" aria-label="Delete">
                                                    <i class="bx bx-trash" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="list-group-item text-center text-muted py-4">No expense categories available.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade {{ $activeTab == 'tags' ? 'show active' : '' }}" id="tab-tags" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Finance Tags</h5>
                        <button type="button" class="btn btn-primary" id="createNewTag" data-entity="tag" data-action="create">
                            <i class="bx bx-plus me-1" aria-hidden="true"></i>Add Tag
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            @forelse ($tags as $tag)
                                <div class="d-inline-flex align-items-center p-2 rounded border">
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 px-3 py-2 me-3 {{ $tag->badge_class }}">
                                        <i class="bx bx-purchase-tag" aria-hidden="true"></i> {{ $tag->name }}
                                        @if(($tag->transactions_count ?? 0) > 0)
                                            <span class="badge bg-white text-dark ms-1 rounded-circle px-1 tag-count-pill">{{ $tag->transactions_count }}</span>
                                        @endif
                                    </span>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-warning editTagBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-id="{{ $tag->id }}" aria-label="Edit">
                                            <i class="bx bx-edit-alt" aria-hidden="true"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteTagBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-id="{{ $tag->id }}" aria-label="Delete">
                                            <i class="bx bx-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="w-100 text-center text-muted py-5">
                                    <h6 class="mb-0">No tags available.</h6>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade {{ $activeTab == 'recurring' ? 'show active' : '' }}" id="tab-recurring" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <h5 class="mb-0">Finance Recurring</h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center" id="processRecurringsBtn" data-entity="recurring" data-action="generate">
                                <i class="bx bx-play-circle me-1" aria-hidden="true"></i>Process Due
                                @if ($dueCount > 0)
                                    <span class="badge bg-danger rounded-pill ms-1" id="processDueBadge">{{ $dueCount }}</span>
                                @endif
                            </button>
                            <button type="button" class="btn btn-primary" id="createNewRecurring" data-entity="recurring" data-action="create">
                                <i class="bx bx-plus me-1" aria-hidden="true"></i>Add Recurring
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-striped" id="recurringTable">
                                <caption class="visually-hidden">Recurring Rules</caption>
                                <thead>
                                    <tr>
                                        <th class="text-center">Status</th>
                                        <th>Rule & Wallet</th>
                                        <th class="text-end">Amount & Frequency</th>
                                        <th>Schedule & Timeline</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @foreach ($recurrings as $rec)
                                        @php
                                            $isDue = $rec->is_active && $rec->next_due_date && ($rec->next_due_date->isPast() || $rec->next_due_date->isToday());
                                        @endphp
                                        <tr class="{{ $rec->is_active ? '' : 'opacity-50' }}" data-is-due="{{ $isDue ? '1' : '0' }}">
                                            <td class="text-center">
                                                <div class="form-check form-switch m-0 d-flex align-items-center justify-content-center">
                                                    <input class="form-check-input toggle-recurring-status cursor-pointer" type="checkbox" id="toggle-rec-{{ $rec->id }}" data-id="{{ $rec->id }}" {{ $rec->is_active ? 'checked' : '' }} aria-label="Toggle active status for {{ $rec->type === 'transfer' ? 'Transfer' : ($rec->category->name ?? 'Recurring') }}">
                                                </div>
                                            </td>
                                            <td>
                                                @if ($rec->type === 'transfer')
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="fw-semibold text-heading">Transfer</span>
                                                        <span class="badge bg-label-primary">Transfer</span>
                                                    </div>
                                                    <div class="text-muted small d-flex align-items-center gap-1">
                                                        <i class="bx bx-wallet" aria-hidden="true"></i> {{ $rec->wallet->name ?? 'Unknown' }}
                                                        <i class="bx bx-right-arrow-alt text-primary mx-1" aria-hidden="true"></i>
                                                        <i class="bx bx-wallet" aria-hidden="true"></i> {{ $rec->toWallet->name ?? 'Unknown' }}
                                                    </div>
                                                @else
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <span class="fw-semibold text-heading">{{ $rec->category->name ?? 'Unknown' }}</span>
                                                        @if ($rec->type === 'income')
                                                            <span class="badge bg-label-success">Income</span>
                                                        @else
                                                            <span class="badge bg-label-danger">Expense</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted small d-flex align-items-center gap-1">
                                                        <i class="bx bx-wallet" aria-hidden="true"></i> {{ $rec->wallet->name ?? 'Unknown' }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="mb-1">
                                                    @if ($rec->type === 'income')
                                                        <span class="text-success fw-semibold font-monospace">+ Rp {{ number_format($rec->amount, 0, ',', '.') }}</span>
                                                    @elseif ($rec->type === 'expense')
                                                        <span class="text-danger fw-semibold font-monospace">- Rp {{ number_format($rec->amount, 0, ',', '.') }}</span>
                                                    @else
                                                        <span class="text-primary fw-semibold font-monospace">Rp {{ number_format($rec->amount, 0, ',', '.') }}</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="badge bg-label-info">{{ ucfirst($rec->frequency) }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1 mb-1">
                                                    <span class="fw-medium {{ $isDue ? 'text-danger fw-bold' : 'text-heading' }}">
                                                        <i class="bx bx-calendar-event me-1 text-muted" aria-hidden="true"></i>{{ $rec->next_due_date ? $rec->next_due_date->format('d M Y') : '—' }}
                                                    </span>
                                                    @if($isDue)
                                                        <span class="badge bg-danger ms-1">Due</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted small d-flex align-items-center gap-2">
                                                    <span>Last: {{ $rec->last_generated_at ? $rec->last_generated_at->format('d M Y') : '—' }}</span>
                                                    <span>•</span>
                                                    <span>End: {{ $rec->end_date ? $rec->end_date->format('d M Y') : 'No End' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-warning editRecBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-id="{{ $rec->id }}" aria-label="Edit" data-entity="recurring" data-action="edit">
                                                        <i class="bx bx-edit-alt" aria-hidden="true"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteRecBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-id="{{ $rec->id }}" aria-label="Delete" data-entity="recurring" data-action="delete">
                                                        <i class="bx bx-trash" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="walletModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="walletModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="walletForm" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="wallet_id" id="wallet_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="walletModalTitle">Add Wallet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label" for="wallet_name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="wallet_name" class="form-control" autocomplete="off" required aria-describedby="wallet_nameError">
                            <div class="invalid-feedback" id="wallet_nameError"></div>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label" for="initial_balance">Initial Balance <span class="text-danger">*</span></label>
                            <input type="text" name="initial_balance" id="initial_balance" class="form-control text-end font-monospace" inputmode="numeric" required aria-describedby="wallet_initial_balanceError">
                            <div class="invalid-feedback" id="wallet_initial_balanceError"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveWalletBtn" class="btn btn-primary" data-entity="wallet" data-action="save">
                        <i class="bx bx-save me-1" aria-hidden="true"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="categoryModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="categoryForm" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="category_id" id="category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label" for="category_name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="category_name" class="form-control" autocomplete="off" required aria-describedby="category_nameError">
                            <div class="invalid-feedback" id="category_nameError"></div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label" for="category_type">Type <span class="text-danger">*</span></label>
                            <select name="type" id="category_type" class="form-select" required aria-describedby="category_typeError">
                                <option value="" selected disabled>Select Type</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                            <div class="invalid-feedback" id="category_typeError"></div>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label" for="category_amount" id="category_amount_label">Target / Budget (Optional)</label>
                            <input type="text" name="amount" id="category_amount" class="form-control text-end font-monospace" inputmode="numeric" aria-describedby="category_amountError">
                            <div class="invalid-feedback" id="category_amountError"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveCategoryBtn" class="btn btn-primary" data-entity="category" data-action="save">
                        <i class="bx bx-save me-1" aria-hidden="true"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="tagModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="tagModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="tagForm" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="tag_id" id="tag_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="tagModalTitle">Add Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label" for="tag_name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="tag_name" class="form-control" autocomplete="off" required aria-describedby="tag_nameError">
                            <div class="invalid-feedback" id="tag_nameError"></div>
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label d-block" id="colorPaletteLabel">Color</label>
                            <div class="d-flex flex-wrap gap-2" id="color-palette" role="radiogroup" aria-labelledby="colorPaletteLabel" aria-describedby="tag_colorError">
                                <div class="form-check custom-option custom-option-color m-0 p-0">
                                    <input type="radio" class="btn-check tag-color-preset cursor-pointer" name="color" id="color_blue" value="#696cff" autocomplete="off" checked>
                                    <label class="btn p-1 rounded-circle tag-color-swatch-label cursor-pointer" for="color_blue" title="Blue">
                                        <span class="tag-color-circle bg-blue"></span>
                                    </label>
                                </div>
                                <div class="form-check custom-option custom-option-color m-0 p-0">
                                    <input type="radio" class="btn-check tag-color-preset cursor-pointer" name="color" id="color_gray" value="#8592a3" autocomplete="off">
                                    <label class="btn p-1 rounded-circle tag-color-swatch-label cursor-pointer" for="color_gray" title="Gray">
                                        <span class="tag-color-circle bg-gray"></span>
                                    </label>
                                </div>
                                <div class="form-check custom-option custom-option-color m-0 p-0">
                                    <input type="radio" class="btn-check tag-color-preset cursor-pointer" name="color" id="color_green" value="#71dd37" autocomplete="off">
                                    <label class="btn p-1 rounded-circle tag-color-swatch-label cursor-pointer" for="color_green" title="Green">
                                        <span class="tag-color-circle bg-green"></span>
                                    </label>
                                </div>
                                <div class="form-check custom-option custom-option-color m-0 p-0">
                                    <input type="radio" class="btn-check tag-color-preset cursor-pointer" name="color" id="color_red" value="#ff3e1d" autocomplete="off">
                                    <label class="btn p-1 rounded-circle tag-color-swatch-label cursor-pointer" for="color_red" title="Red">
                                        <span class="tag-color-circle bg-red"></span>
                                    </label>
                                </div>
                                <div class="form-check custom-option custom-option-color m-0 p-0">
                                    <input type="radio" class="btn-check tag-color-preset cursor-pointer" name="color" id="color_yellow" value="#ffab00" autocomplete="off">
                                    <label class="btn p-1 rounded-circle tag-color-swatch-label cursor-pointer" for="color_yellow" title="Yellow">
                                        <span class="tag-color-circle bg-yellow"></span>
                                    </label>
                                </div>
                                <div class="form-check custom-option custom-option-color m-0 p-0">
                                    <input type="radio" class="btn-check tag-color-preset cursor-pointer" name="color" id="color_cyan" value="#03c3ec" autocomplete="off">
                                    <label class="btn p-1 rounded-circle tag-color-swatch-label cursor-pointer" for="color_cyan" title="Cyan">
                                        <span class="tag-color-circle bg-cyan"></span>
                                    </label>
                                </div>
                                <div class="form-check custom-option custom-option-color m-0 p-0">
                                    <input type="radio" class="btn-check tag-color-preset cursor-pointer" name="color" id="color_dark" value="#233446" autocomplete="off">
                                    <label class="btn p-1 rounded-circle tag-color-swatch-label cursor-pointer" for="color_dark" title="Dark">
                                        <span class="tag-color-circle bg-dark"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="invalid-feedback d-none mt-1" id="tag_colorError"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveTagBtn" class="btn btn-primary" data-entity="tag" data-action="save">
                        <i class="bx bx-save me-1" aria-hidden="true"></i>Save
                    </button>
                </div>
            </form> 
        </div>
    </div>
    <div class="modal fade" id="recurringModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="recurringModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <form id="recurringForm" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="recurring_id" id="recurring_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="recurringModalTitle">Add Recurring</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label" for="rec_type">Type <span class="text-danger">*</span></label>
                            <select name="type" id="rec_type" class="form-select" required aria-describedby="rec_typeError">
                                <option value="" selected disabled>Select Type</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="transfer">Transfer</option>
                            </select>
                            <div class="invalid-feedback" id="rec_typeError"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="rec_wallet_id"><span id="recWalletLabel">Wallet</span> <span class="text-danger">*</span></label>
                            <select name="wallet_id" id="rec_wallet_id" class="form-select" required aria-describedby="rec_wallet_idError">
                                <option value="" selected disabled>Select Wallet</option>
                                @foreach ($wallets as $wallet)
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }} (Rp {{ number_format($wallet->current_balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="rec_wallet_idError"></div>
                        </div>
                        <div class="col-md-6 mb-3" id="recCategoryGroup">
                            <label class="form-label" for="rec_category_id">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="rec_category_id" class="form-select" aria-describedby="rec_category_idError">
                                <option value="" selected disabled>Select Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" data-type="{{ $category->type }}">{{ $category->name }} ({{ ucfirst($category->type) }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="rec_category_idError"></div>
                        </div>
                        <div class="col-md-6 mb-3 d-none" id="recToWalletGroup">
                            <label class="form-label" for="rec_to_wallet_id">To Wallet <span class="text-danger">*</span></label>
                            <select name="to_wallet_id" id="rec_to_wallet_id" class="form-select" aria-describedby="rec_to_wallet_idError">
                                <option value="" selected disabled>Select Destination Wallet</option>
                                @foreach ($wallets as $wallet)
                                    <option value="{{ $wallet->id }}">{{ $wallet->name }} (Rp {{ number_format($wallet->current_balance, 0, ',', '.') }})</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="rec_to_wallet_idError"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="rec_amount">Amount <span class="text-danger">*</span></label>
                            <input type="text" name="amount" id="rec_amount" class="form-control text-end font-monospace" inputmode="numeric" required aria-describedby="rec_amountError">
                            <div class="invalid-feedback" id="rec_amountError"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="rec_frequency">Frequency <span class="text-danger">*</span></label>
                            <select name="frequency" id="rec_frequency" class="form-select" required aria-describedby="rec_frequencyError">
                                <option value="" selected disabled>Select Frequency</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <div class="invalid-feedback" id="rec_frequencyError"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="rec_start_date">First Due Date / Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="rec_start_date" class="form-control" value="{{ date('Y-m-d') }}" required aria-describedby="rec_start_dateError">
                            <div class="invalid-feedback" id="rec_start_dateError"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="rec_end_date">End Date (Optional)</label>
                            <input type="date" name="end_date" id="rec_end_date" class="form-control" aria-describedby="rec_end_dateError">
                            <div class="invalid-feedback" id="rec_end_dateError"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="rec_tags_input">Tags (Optional)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="bx bx-purchase-tag" aria-hidden="true"></i></span>
                                <input type="text" id="rec_tags_input" class="form-control" autocomplete="off">
                            </div>
                            <div id="recSelectedTagsWrapper" class="position-relative mt-2 tags-collapsible-wrapper">
                                <div id="recSelectedTagsContainer" class="d-flex flex-wrap gap-2"></div>
                            </div>
                            <div id="recSelectedTagsControls" class="d-flex justify-content-between align-items-center mt-1 d-none"></div>
                            <div id="recHiddenTagsInputs"></div>
                            @if($tags->count() > 0)
                                <div class="mt-2 pt-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center py-1 cursor-pointer user-select-none" id="recToggleAvailableTags">
                                        <span class="text-muted small d-inline-flex align-items-center">
                                            <i class="bx bx-chevron-right me-1 toggle-icon toggle-icon-rotate" id="recToggleAvailableTagsIcon" aria-hidden="true"></i>
                                            <span id="recToggleAvailableTagsText">Show available tags ({{ $tags->count() }})</span>
                                        </span>
                                        <span class="text-muted small" id="recTagMatchCount"></span>
                                    </div>
                                    <div id="recAvailableTagsPanel" class="d-none mt-1">
                                        <div id="recQuickTagsSuggestions" class="d-flex flex-wrap gap-1 tags-suggestions-box">
                                            @foreach($tags as $tag)
                                                <button type="button" class="btn btn-xs rounded-pill rec-quick-tag-btn btn-quick-tag d-inline-flex align-items-center gap-1 cursor-pointer {{ $tag->badge_class }}" data-tag-name="{{ $tag->name }}" data-tag-color="{{ $tag->color }}" aria-label="Add tag {{ $tag->name }}">
                                                    <i class="bx bx-plus fs-6 rec-quick-tag-icon" aria-hidden="true"></i>
                                                    <span>{{ $tag->name }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                        <div id="recNoTagsFoundHint" class="text-muted small fst-italic py-1 d-none">
                                            Press <kbd class="px-1 py-0 bg-light border text-dark">Enter</kbd> to add new tag "<span id="recNewTagNameDisplay" class="fw-semibold text-primary"></span>"
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="rec_description">Description (Optional)</label>
                            <textarea name="description" id="rec_description" class="form-control" rows="1" aria-describedby="rec_descriptionError"></textarea>
                            <div class="invalid-feedback" id="rec_descriptionError"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" name="is_active" id="rec_is_active" class="form-check-input cursor-pointer" value="1" checked>
                        <label class="form-check-label fw-semibold cursor-pointer" for="rec_is_active">Active</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveRecBtn" class="btn btn-primary" data-entity="recurring" data-action="save">
                            <i class="bx bx-save me-1" aria-hidden="true"></i>Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ asset('vendor/libs/datatables/dataTables.js') }}"></script>
    <script src="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.js') }}"></script>
    <script src="{{ asset('js/finance-helpers.js') }}"></script>
    <script nonce="{{ $cspNonce }}">
        $(document).ready(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            var availableTagsMap = @json($tags->pluck('color', 'name'));
            var currentTags = [];
            var isTagsExpanded = false;
            var isAvailableTagsExpanded = false;
            function renderSelectedTags() {
                var html = '';
                var inputsHtml = '';
                currentTags.forEach(function (tag, index) {
                    var color = availableTagsMap[tag] || '#696cff';
                    html += '<span class="badge rounded-pill tag-chip-badge d-inline-flex align-items-center gap-1 py-1 px-3 ' + getTagBadgeClass(color) + '">' +
                        '<i class="bx bx-purchase-tag fs-6" aria-hidden="true"></i> ' + escapeHtml(tag) +
                        '<i class="bx bx-x remove-tag-chip fs-5 ms-1 cursor-pointer" data-index="' + index + '" title="Remove" aria-hidden="true"></i>' +
                        '</span>';
                    inputsHtml += '<input type="hidden" name="tags[]" value="' + escapeHtml(tag) + '">';
                });
                $('#recSelectedTagsContainer').html(html);
                $('#recHiddenTagsInputs').html(inputsHtml);
                updateQuickTagsState();
                updateSelectedTagsControls();
            }
            function updateSelectedTagsControls() {
                var wrapper = $('#recSelectedTagsWrapper');
                var controls = $('#recSelectedTagsControls');
                if (currentTags.length === 0) {
                    wrapper.css('max-height', '34px');
                    controls.addClass('d-none').html('');
                    isTagsExpanded = false;
                    return;
                }
                if (isTagsExpanded) {
                    wrapper.css('max-height', 'none');
                } else {
                    wrapper.css('max-height', '34px');
                }
                var chips = $('#recSelectedTagsContainer .badge');
                var hiddenCount = 0;
                if (chips.length > 0) {
                    var firstTop = chips.first().position().top;
                    chips.each(function () {
                        if ($(this).position().top > firstTop + 5) {
                            hiddenCount++;
                        }
                    });
                }
                var leftControlsHtml = '';
                if (!isTagsExpanded && hiddenCount > 0) {
                    leftControlsHtml = '<a href="javascript:void(0);" role="button" class="badge bg-label-primary toggle-tags-expand text-decoration-none badge-sm cursor-pointer" title="Show all selected tags">+' + hiddenCount + ' more</a>';
                } else if (isTagsExpanded && chips.length > 0) {
                    leftControlsHtml = '<a href="javascript:void(0);" role="button" class="text-primary small toggle-tags-expand text-decoration-none d-inline-flex align-items-center cursor-pointer"><i class="bx bx-chevron-up me-1" aria-hidden="true"></i>Show less</a>';
                }
                var rightControlsHtml = '';
                if (currentTags.length >= 2) {
                    rightControlsHtml = '<button type="button" class="btn btn-xs btn-outline-secondary clear-all-tags d-inline-flex align-items-center gap-1 ms-auto cursor-pointer" title="Remove all selected tags"><i class="bx bx-trash-alt" aria-hidden="true"></i> Clear All</button>';
                }
                if (leftControlsHtml || rightControlsHtml) {
                    controls.html('<div class="d-flex align-items-center">' + leftControlsHtml + '</div><div class="d-flex align-items-center">' + rightControlsHtml + '</div>').removeClass('d-none');
                } else {
                    controls.addClass('d-none').html('');
                }
            }
            function setAvailableTagsExpanded(expanded) {
                isAvailableTagsExpanded = expanded;
                var panel = $('#recAvailableTagsPanel');
                var icon = $('#recToggleAvailableTagsIcon');
                var text = $('#recToggleAvailableTagsText');
                var totalTags = {{ $tags->count() }};
                if (isAvailableTagsExpanded) {
                    panel.removeClass('d-none');
                    icon.css('transform', 'rotate(90deg)');
                    text.text('Hide available tags');
                } else {
                    panel.addClass('d-none');
                    icon.css('transform', 'rotate(0deg)');
                    text.text('Show available tags (' + totalTags + ')');
                }
            }
            $('#recToggleAvailableTags').on('click', function () {
                setAvailableTagsExpanded(!isAvailableTagsExpanded);
            });
            $('body').on('click', '.toggle-tags-expand', function (e) {
                e.preventDefault();
                isTagsExpanded = !isTagsExpanded;
                updateSelectedTagsControls();
            });
            function updateQuickTagsState() {
                $('.rec-quick-tag-btn').each(function () {
                    var tagName = String($(this).data('tag-name'));
                    var color = $(this).data('tag-color') || availableTagsMap[tagName] || '#696cff';
                    var isSelected = currentTags.indexOf(tagName) !== -1;
                    var icon = $(this).find('.rec-quick-tag-icon');
                    if (isSelected) {
                        $(this).addClass('active').css({
                            'background-color': color,
                            'color': '#ffffff',
                            'border-color': color,
                            'opacity': '1',
                            'text-decoration': 'none'
                        });
                        icon.removeClass('bx-plus').addClass('bx-check');
                    } else {
                        $(this).removeClass('active').css({
                            'background-color': color + '15',
                            'color': color,
                            'border-color': color + '40',
                            'opacity': '1',
                            'text-decoration': 'none'
                        });
                        icon.removeClass('bx-check').addClass('bx-plus');
                    }
                });
            }
            function addTag(tagName) {
                var clean = tagName.trim().replace(/^#/, '');
                if (clean && currentTags.indexOf(clean) === -1) {
                    if (!availableTagsMap[clean]) {
                        availableTagsMap[clean] = getRandomTagColor();
                    }
                    currentTags.push(clean);
                    renderSelectedTags();
                }
                $('#rec_tags_input').val('').trigger('input');
            }
            function clearAllTags() {
                currentTags = [];
                isTagsExpanded = false;
                renderSelectedTags();
            }
            $('#rec_tags_input').on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    var val = $(this).val();
                    if (val) {
                        val.split(',').forEach(function (t) { addTag(t); });
                    }
                }
            });
            $('#rec_tags_input').on('input', function () {
                var query = $(this).val().trim().toLowerCase().replace(/^#/, '');
                var matchCount = 0;
                if (query) {
                    setAvailableTagsExpanded(true);
                    var hasExactMatch = false;
                    $('.rec-quick-tag-btn').each(function () {
                        var name = String($(this).data('tag-name')).toLowerCase();
                        if (name.indexOf(query) !== -1) {
                            $(this).removeClass('d-none');
                            matchCount++;
                            if (name === query) hasExactMatch = true;
                        } else {
                            $(this).addClass('d-none');
                        }
                    });
                    if (!hasExactMatch && query.length > 0) {
                        $('#recNoTagsFoundHint').removeClass('d-none');
                        $('#recNewTagNameDisplay').text(query);
                    } else {
                        $('#recNoTagsFoundHint').addClass('d-none');
                    }
                    $('#recTagMatchCount').text(matchCount + ' found');
                } else {
                    $('.rec-quick-tag-btn').removeClass('d-none');
                    $('#recNoTagsFoundHint').addClass('d-none');
                    $('#recTagMatchCount').text('');
                }
            });
            $('body').on('click', '.rec-quick-tag-btn', function (e) {
                e.preventDefault();
                var name = String($(this).data('tag-name'));
                var index = currentTags.indexOf(name);
                if (index === -1) {
                    currentTags.push(name);
                } else {
                    currentTags.splice(index, 1);
                }
                renderSelectedTags();
            });
            $('body').on('click', '.remove-tag-chip, .rec-remove-tag-chip', function () {
                var idx = $(this).data('index');
                currentTags.splice(idx, 1);
                renderSelectedTags();
            });
            $('body').on('click', '.clear-all-tags', function () {
                clearAllTags();
            });
            $.extend(true, DataTable.ext.classes, {
                search: { input: 'form-control' },
                length: { select: 'form-select' }
            });
            var recurringTable = $('#recurringTable').DataTable({
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [0, 4] }
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No recurrings available.",
                    zeroRecords: "No matching recurrings found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search Recurring",
                    paginate: {
                        first: '<i class="bx bx-chevrons-left" aria-hidden="true"></i>',
                        previous: '<i class="bx bx-chevron-left" aria-hidden="true"></i>',
                        next: '<i class="bx bx-chevron-right" aria-hidden="true"></i>',
                        last: '<i class="bx bx-chevrons-right" aria-hidden="true"></i>'
                    }
                }
            });
            recurringTable.on('draw', function () {
                initTooltips();
            });
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                var targetId = $(e.target).data('bs-target');
                var tabMap = {
                    '#tab-wallets': 'wallets',
                    '#tab-categories': 'categories',
                    '#tab-tags': 'tags',
                    '#tab-recurring': 'recurring'
                };
                var tabName = tabMap[targetId] || 'wallets';
                var newUrl = new URL(window.location.href);
                newUrl.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', newUrl);
                if (tabName === 'recurring' && recurringTable) {
                    recurringTable.columns.adjust().draw();
                }
            });
            function initTooltips() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            initTooltips();
            $('#initial_balance, #category_amount, #rec_amount').on('input', function () {
                var val = $(this).val();
                var rawValue = val.replace(/\D/g, '');
                var formatted = formatRupiah(rawValue);
                $(this).val(formatted);
            });
            function resetWalletForm() {
                $('#walletForm')[0].reset();
                $('#wallet_id').val('');
                $('#initial_balance').prop('disabled', false);
                $('#walletForm .is-invalid').removeClass('is-invalid');
                $('#walletForm .invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewWallet').click(function () {
                resetWalletForm();
                $('#walletModalTitle').text('Add Wallet');
                $('#walletModal').modal('show');
            });
            $('#walletForm').on('submit', function (e) {
                e.preventDefault();
                var walletId = $('#wallet_id').val();
                var url = walletId ? '/finance-wallets/' + walletId : '{{ route("finance-wallets.store") }}';
                var initInput = $('#initial_balance');
                var rawInit = initInput.val().replace(/\./g, '');
                initInput.val(rawInit);
                var formData = $(this).serialize();
                if ($('#initial_balance').is(':disabled')) {
                    formData += '&initial_balance=' + encodeURIComponent(rawInit);
                }
                initInput.val(formatRupiah(rawInit));
                if (walletId) {
                    formData += '&_method=PUT';
                }
                $('#walletForm .is-invalid').removeClass('is-invalid');
                $('#walletForm .invalid-feedback').text('').removeClass('d-block');
                var $closeBtns = $('#walletModal').find('.btn-close, [data-bs-dismiss="modal"]');
                $closeBtns.prop('disabled', true);
                $('#saveWalletBtn').html('<i class="bx bx-loader-alt bx-spin me-1" aria-hidden="true"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveWalletBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 204) {
                            $('#walletModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $('#walletModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Wallet Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            window.location.href = '{{ route("finance-master-data.index", ["tab" => "wallets"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveWalletBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('#walletForm [name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#wallet_' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            $('#walletModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Save Wallet',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editWalletBtn', function () {
                var walletId = $(this).data('id');
                Swal.fire({
                    title: 'Loading Wallet...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/finance-wallets/' + walletId + '/edit', function (data) {
                    Swal.close();
                    resetWalletForm();
                    $('#walletModalTitle').text('Edit Wallet');
                    $('#wallet_id').val(data.id);
                    $('#wallet_name').val(data.name);
                    if (data.has_transactions) {
                        $('#initial_balance').prop('disabled', true);
                    } else {
                        $('#initial_balance').prop('disabled', false);
                    }
                    if (data.initial_balance !== null && data.initial_balance !== undefined) {
                        $('#initial_balance').val(formatRupiah(data.initial_balance.toString()));
                    }
                    $('#walletModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Wallet',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            $('body').on('click', '.deleteWalletBtn', function () {
                var walletId = $(this).data('id');
                Swal.fire({
                    title: 'Delete Wallet?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#8592a3',
                    focusCancel: true,
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting Wallet...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/finance-wallets/' + walletId,
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Wallet Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    window.location.href = '{{ route("finance-master-data.index", ["tab" => "wallets"]) }}';
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 422 ? 'Wallet In Use' : (xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Wallet'),
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            function updateCategoryAmountField(type) {
                var label = $('#category_amount_label');
                if (type === 'income') {
                    label.text('Target (Optional)');
                } else if (type === 'expense') {
                    label.text('Budget (Optional)');
                } else {
                    label.text('Target / Budget (Optional)');
                }
            }
            $('#category_type').on('change', function () {
                updateCategoryAmountField($(this).val());
            });
            function resetCategoryForm() {
                $('#categoryForm')[0].reset();
                $('#category_id').val('');
                $('#category_type').prop('disabled', false);
                updateCategoryAmountField('');
                $('#categoryForm .is-invalid').removeClass('is-invalid');
                $('#categoryForm .invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewCategory').click(function () {
                resetCategoryForm();
                $('#categoryModalTitle').text('Add Category');
                $('#categoryModal').modal('show');
            });
            $('#categoryForm').on('submit', function (e) {
                e.preventDefault();
                var categoryId = $('#category_id').val();
                var url = categoryId ? '/finance-categories/' + categoryId : '{{ route("finance-categories.store") }}';
                var amountInput = $('#category_amount');
                var rawAmount = amountInput.val() ? amountInput.val().replace(/\./g, '') : '';
                amountInput.val(rawAmount);
                var formData = $(this).serialize();
                if ($('#category_type').is(':disabled')) {
                    formData += '&type=' + encodeURIComponent($('#category_type').val());
                }
                amountInput.val(formatRupiah(rawAmount));
                if (categoryId) {
                    formData += '&_method=PUT';
                }
                $('#categoryForm .is-invalid').removeClass('is-invalid');
                $('#categoryForm .invalid-feedback').text('').removeClass('d-block');
                var $closeBtns = $('#categoryModal').find('.btn-close, [data-bs-dismiss="modal"]');
                $closeBtns.prop('disabled', true);
                $('#saveCategoryBtn').html('<i class="bx bx-loader-alt bx-spin me-1" aria-hidden="true"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveCategoryBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 204) {
                            $('#categoryModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $('#categoryModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Category Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            window.location.href = '{{ route("finance-master-data.index", ["tab" => "categories"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveCategoryBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('#categoryForm [name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#category_' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Save Category',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editCategoryBtn', function () {
                var categoryId = $(this).data('id');
                Swal.fire({
                    title: 'Loading Category...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/finance-categories/' + categoryId + '/edit', function (data) {
                    Swal.close();
                    resetCategoryForm();
                    $('#categoryModalTitle').text('Edit Category');
                    $('#category_id').val(data.id);
                    $('#category_name').val(data.name);
                    $('#category_type').val(data.type);
                    if (data.has_transactions) {
                        $('#category_type').prop('disabled', true);
                    } else {
                        $('#category_type').prop('disabled', false);
                    }
                    updateCategoryAmountField(data.type);
                    if (data.amount) {
                        $('#category_amount').val(formatRupiah(data.amount.toString()));
                    }
                    $('#categoryModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Category',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            $('body').on('click', '.deleteCategoryBtn', function () {
                var categoryId = $(this).data('id');
                Swal.fire({
                    title: 'Delete Category?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#8592a3',
                    focusCancel: true,
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting Category...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/finance-categories/' + categoryId,
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Category Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    window.location.href = '{{ route("finance-master-data.index", ["tab" => "categories"]) }}';
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 422 ? 'Category In Use' : (xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Category'),
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            function updateTagColorSelection() {
                $('.tag-color-preset').each(function () {
                    var $radio = $(this);
                    var $label = $radio.next('label');
                    var color = $radio.val();
                    if ($radio.is(':checked')) {
                        $label.css({
                            'border-color': color,
                            'transform': 'scale(1.12)',
                            'box-shadow': '0 2px 6px ' + color + '60'
                        });
                    } else {
                        $label.css({
                            'border-color': 'transparent',
                            'transform': 'scale(1)',
                            'box-shadow': 'none'
                        });
                    }
                });
            }
            $(document).on('change', "input[name='color']", function () {
                updateTagColorSelection();
            });
            function resetTagForm() {
                $('#tagForm')[0].reset();
                $('#tag_id').val('');
                var firstRadio = $('.tag-color-preset').first();
                firstRadio.prop('checked', true);
                updateTagColorSelection();
                $('#tagForm .is-invalid').removeClass('is-invalid');
                $('#tagForm .invalid-feedback').text('').removeClass('d-block');
                $('#tag_colorError').hide();
            }
            $('#createNewTag').click(function () {
                resetTagForm();
                $('#tagModalTitle').text('Add Tag');
                $('#tagModal').modal('show');
            });
            $('#tagForm').on('submit', function (e) {
                e.preventDefault();
                var tagId = $('#tag_id').val();
                var url = tagId ? '/finance-tags/' + tagId : '{{ route("finance-tags.store") }}';
                var formData = $(this).serialize();
                if (tagId) {
                    formData += '&_method=PUT';
                }
                $('#tagForm .is-invalid').removeClass('is-invalid');
                $('#tagForm .invalid-feedback').text('').removeClass('d-block');
                var $closeBtns = $('#tagModal').find('.btn-close, [data-bs-dismiss="modal"]');
                $closeBtns.prop('disabled', true);
                $('#saveTagBtn').html('<i class="bx bx-loader-alt bx-spin me-1" aria-hidden="true"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveTagBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 204) {
                            $('#tagModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $('#tagModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Tag Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            window.location.href = '{{ route("finance-master-data.index", ["tab" => "tags"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveTagBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('#tagForm [name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#tag_' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Save Tag',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editTagBtn', function () {
                var tagId = $(this).data('id');
                Swal.fire({
                    title: 'Loading Tag...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/finance-tags/' + tagId + '/edit', function (data) {
                    Swal.close();
                    resetTagForm();
                    $('#tagModalTitle').text('Edit Tag');
                    $('#tag_id').val(data.id);
                    $('#tag_name').val(data.name);
                    var tagColor = (data.color || '').toLowerCase();
                    var radio = $("input[name='color']").filter(function () {
                        return this.value.toLowerCase() === tagColor;
                    });
                    if (radio.length) {
                        radio.prop('checked', true);
                    } else {
                        $('.tag-color-preset').first().prop('checked', true);
                    }
                    updateTagColorSelection();
                    $('#tagModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Tag',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            $('body').on('click', '.deleteTagBtn', function () {
                var tagId = $(this).data('id');
                Swal.fire({
                    title: 'Delete Tag?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#8592a3',
                    focusCancel: true,
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting Tag...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/finance-tags/' + tagId,
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Tag Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    window.location.href = '{{ route("finance-master-data.index", ["tab" => "tags"]) }}';
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 422 ? 'Tag In Use' : (xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Tag'),
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            function updateRecurringModalType(type) {
                $('#rec_type').val(type);
                if (type === 'transfer') {
                    $('#recCategoryGroup').addClass('d-none');
                    $('#rec_category_id').prop('required', false).val('');
                    $('#recToWalletGroup').removeClass('d-none');
                    $('#rec_to_wallet_id').prop('required', true);
                    $('#recWalletLabel').text('From Wallet');
                } else if (type === 'income' || type === 'expense') {
                    $('#recToWalletGroup').addClass('d-none');
                    $('#rec_to_wallet_id').prop('required', false).val('');
                    $('#recCategoryGroup').removeClass('d-none');
                    $('#rec_category_id').prop('required', true);
                    $('#recWalletLabel').text('Wallet');
                    $('#rec_category_id option').each(function () {
                        var catType = $(this).data('type');
                        if (!catType) return;
                        if (catType === type) {
                            $(this).removeClass('d-none').prop('disabled', false);
                        } else {
                            $(this).addClass('d-none').prop('disabled', true);
                        }
                    });
                    var currentCatVal = $('#rec_category_id').val();
                    var $selectedOption = $('#rec_category_id option:selected');
                    if ($selectedOption.length && $selectedOption.data('type') && $selectedOption.data('type') !== type) {
                        $('#rec_category_id').val('');
                    }
                } else {
                    $('#recToWalletGroup').addClass('d-none');
                    $('#rec_to_wallet_id').prop('required', false).val('');
                    $('#recCategoryGroup').removeClass('d-none');
                    $('#rec_category_id').prop('required', true).val('');
                    $('#recWalletLabel').text('Wallet');
                    $('#rec_category_id option').each(function () {
                        var catType = $(this).data('type');
                        if (!catType) return;
                        $(this).addClass('d-none').prop('disabled', true);
                    });
                }
            }
            $('#rec_type').on('change', function () {
                updateRecurringModalType($(this).val());
            });
            function resetRecurringForm() {
                $('#recurringForm')[0].reset();
                $('#recurring_id').val('');
                $('#rec_start_date').val(new Date().toISOString().split('T')[0]);
                $('#rec_is_active').prop('checked', true);
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                currentTags = [];
                isTagsExpanded = false;
                renderSelectedTags();
                setAvailableTagsExpanded(false);
                $('#rec_tags_input').val('');
                $('#recNoTagsFoundHint').addClass('d-none');
                $('.rec-quick-tag-btn').removeClass('d-none');
                $('#recTagMatchCount').text('');
                $('#rec_type').prop('disabled', false).val('');
                $('#rec_frequency').val('');
                $('#rec_wallet_id').val('');
                $('#rec_start_date').prop('disabled', false);
                updateRecurringModalType('');
            }
            $('#createNewRecurring').click(function () {
                resetRecurringForm();
                $('#recurringModalTitle').text('Add Recurring');
                $('#recurringModal').modal('show');
            });
            $('#recurringForm').on('submit', function (e) {
                e.preventDefault();
                var pendingTag = $('#rec_tags_input').val();
                if (pendingTag) {
                    pendingTag.split(',').forEach(function (t) { addTag(t); });
                }
                var recurringId = $('#recurring_id').val();
                var url = recurringId ? '/finance-recurring/' + recurringId : '{{ route("finance-recurring.store") }}';
                var amountInput = $('#rec_amount');
                var rawAmount = amountInput.val().replace(/\./g, '');
                amountInput.val(rawAmount);
                var formData = $(this).serializeArray();
                amountInput.val(formatRupiah(rawAmount));
                var hasActive = false;
                formData.forEach(function (item) {
                    if (item.name === 'is_active') hasActive = true;
                });
                if (!hasActive) {
                    formData.push({ name: 'is_active', value: '0' });
                }
                if ($('#rec_type').is(':disabled')) {
                    formData.push({ name: 'type', value: $('#rec_type').val() });
                }
                if ($('#rec_start_date').is(':disabled')) {
                    formData.push({ name: 'start_date', value: $('#rec_start_date').val() });
                }
                var serialized = $.param(formData);
                if (recurringId) {
                    serialized += '&_method=PUT';
                }
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                var $closeBtns = $('#recurringModal').find('.btn-close, [data-bs-dismiss="modal"]');
                $closeBtns.prop('disabled', true);
                $('#saveRecBtn').html('<i class="bx bx-loader-alt bx-spin me-1" aria-hidden="true"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: serialized,
                    success: function (data, textStatus, xhr) {
                        $('#saveRecBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 204) {
                            $('#recurringModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $('#recurringModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Recurring Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            window.location.href = '{{ route("finance-master-data.index", ["tab" => "recurring"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveRecBtn').html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('#recurringForm [name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#rec_' + field + 'Error, #' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Save Recurring',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editRecBtn', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Loading Recurring...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/finance-recurring/' + id + '/edit', function (data) {
                    Swal.close();
                    resetRecurringForm();
                    $('#recurringModalTitle').text('Edit Recurring');
                    $('#recurring_id').val(data.id);
                    if (data.has_transactions) {
                        $('#rec_type').prop('disabled', true);
                        $('#rec_start_date').prop('disabled', true);
                    } else {
                        $('#rec_type').prop('disabled', false);
                        $('#rec_start_date').prop('disabled', false);
                    }
                    updateRecurringModalType(data.type || 'income');
                    $('#rec_wallet_id').val(data.wallet_id);
                    if (data.to_wallet_id) {
                        $('#rec_to_wallet_id').val(data.to_wallet_id);
                    }
                    if (data.category_id) {
                        $('#rec_category_id').val(data.category_id);
                    }
                    $('#rec_amount').val(formatRupiah(data.amount.toString()));
                    $('#rec_frequency').val(data.frequency);
                    $('#rec_start_date').val(data.start_date);
                    $('#rec_end_date').val(data.end_date || '');
                    $('#rec_description').val(data.description);
                    $('#rec_is_active').prop('checked', !!data.is_active);
                    if (data.tags && data.tags.length > 0) {
                        data.tags.forEach(function (t) {
                            if (!availableTagsMap[t]) {
                                availableTagsMap[t] = getRandomTagColor();
                            }
                        });
                        currentTags = data.tags;
                        renderSelectedTags();
                    }
                    $('#recurringModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Recurring',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            function updateProcessDueBadge() {
                var count = 0;
                $('#recurringTable tbody tr').each(function () {
                    var $tr = $(this);
                    var isActive = $tr.find('.toggle-recurring-status').is(':checked');
                    if (isActive && $tr.data('is-due') == 1) {
                        count++;
                    }
                });
                var $badge = $('#processDueBadge');
                if (count > 0) {
                    if ($badge.length) {
                        $badge.text(count).show();
                    } else {
                        $('#processRecurringsBtn').append('<span class="badge bg-danger rounded-pill ms-1" id="processDueBadge">' + count + '</span>');
                    }
                } else {
                    $badge.remove();
                }
            }
            $(document).on('change', '.toggle-recurring-status', function (e) {
                e.preventDefault();
                var $checkbox = $(this);
                var id = $checkbox.data('id');
                var isChecked = $checkbox.is(':checked');
                var $row = $checkbox.closest('tr');
                $checkbox.prop('checked', !isChecked);
                var confirmTitle = isChecked ? 'Activate Recurring?' : 'Pause Recurring?';
                var confirmText = isChecked ? 'Yes, Activate' : 'Yes, Pause';
                Swal.fire({
                    title: confirmTitle,
                    icon: isChecked ? 'question' : 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: isChecked ? '#696cff' : '#dc3545',
                    cancelButtonColor: '#8592a3',
                    focusCancel: true,
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Updating Status...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'PATCH',
                            url: '/finance-recurring/' + id + '/toggle-status',
                            success: function (res) {
                                Swal.close();
                                $checkbox.prop('checked', isChecked);
                                if (isChecked) {
                                    $row.removeClass('opacity-50');
                                } else {
                                    $row.addClass('opacity-50');
                                }
                                updateProcessDueBadge();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Status Updated',
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Update Status',
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            $('#processRecurringsBtn').on('click', function () {
                Swal.fire({
                    title: 'Process Due Recurrings?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Process',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3',
                    focusCancel: true,
                    reverseButtons: true
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Processing Recurrings...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'POST',
                            url: '{{ route("finance-recurring.generate") }}',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (res) {
                                Swal.close();
                                if (res.generated > 0) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: res.generated + ' Transaction(s) Generated',
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(function () {
                                        window.location.href = '{{ route("finance-master-data.index", ["tab" => "recurring"]) }}';
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'info',
                                        title: 'No Due Recurrings Found',
                                        confirmButtonColor: '#696cff'
                                    });
                                }
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Process Recurrings',
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            $('body').on('click', '.deleteRecBtn', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Recurring?',
                    html: '<div class="d-flex align-items-center justify-content-center mt-3">' +
                          '<input class="form-check-input mt-0 me-2 swal-checkbox-lg cursor-pointer" type="checkbox" id="swal-delete-transactions">' +
                          '<label class="form-check-label mb-0 cursor-pointer" for="swal-delete-transactions">Delete All Generated Transactions</label>' +
                          '</div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#8592a3',
                    focusCancel: true,
                    reverseButtons: true,
                    preConfirm: () => {
                        return document.getElementById('swal-delete-transactions').checked;
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        var deleteTransactions = result.value ? 1 : 0;
                        Swal.fire({
                            title: 'Deleting Recurring...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/finance-recurring/' + id,
                            data: { delete_transactions: deleteTransactions },
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Recurring Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    window.location.href = '{{ route("finance-master-data.index", ["tab" => "recurring"]) }}';
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Recurring',
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush