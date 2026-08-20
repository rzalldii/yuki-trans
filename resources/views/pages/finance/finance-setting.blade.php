@extends('layouts.app')
@section('title', 'Finance Settings')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @php
            $totalWalletBalance = $wallets->sum('current_balance');
            $activeTab = request('tab', 'wallets');
        @endphp
        <div class="nav-align-top mb-4">
            <ul class="nav nav-pills" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'wallets' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-wallets" aria-controls="tab-wallets" aria-selected="{{ $activeTab == 'wallets' ? 'true' : 'false' }}">
                        <i class="bx bx-wallet me-1"></i> Wallets ({{ $wallets->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'categories' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-categories" aria-controls="tab-categories" aria-selected="{{ $activeTab == 'categories' ? 'true' : 'false' }}">
                        <i class="bx bx-category me-1"></i> Categories ({{ $categories->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'tags' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-tags" aria-controls="tab-tags" aria-selected="{{ $activeTab == 'tags' ? 'true' : 'false' }}">
                        <i class="bx bx-tag me-1"></i> Tags ({{ $tags->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link {{ $activeTab == 'recurring' ? 'active' : '' }}" role="tab" data-bs-toggle="tab" data-bs-target="#tab-recurring" aria-controls="tab-recurring" aria-selected="{{ $activeTab == 'recurring' ? 'true' : 'false' }}">
                        <i class="bx bx-sync me-1"></i> Recurring ({{ $recurrings->count() }})
                        @if($dueCount > 0)
                            <span class="badge rounded-pill bg-danger ms-1" style="font-size: 0.7rem;">{{ $dueCount }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>
        <div class="tab-content bg-transparent p-0 shadow-none">
            <div class="tab-pane fade {{ $activeTab == 'wallets' ? 'show active' : '' }}" id="tab-wallets" role="tabpanel">
                <div class="card mb-4 shadow-sm border-0">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 border-bottom">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h5 class="mb-0 fw-semibold text-heading">Finance Wallets</h5>
                            <div class="d-flex align-items-center gap-2 border-start ps-3">
                                <span class="text-muted small">Total Balance:</span>
                                <span class="badge bg-label-primary fs-6 py-1 px-3 fw-bold">
                                    Rp {{ number_format($totalWalletBalance, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="createNewWallet">
                            <i class="bx bx-plus fs-5"></i>Add Wallet
                        </button>
                    </div>
                </div>
                <div class="row g-4">
                    @forelse ($wallets as $wallet)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-md">
                                                <span class="avatar-initial rounded-3 bg-label-primary shadow-sm">
                                                    <i class="bx bx-wallet fs-4"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="card-title mb-0 fw-semibold text-heading">{{ $wallet->name }}</h5>
                                                <span class="badge bg-label-secondary small mt-1">
                                                    {{ $wallet->transactions_count ?? 0 }} Transactions
                                                </span>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn p-0 text-muted" type="button" id="walletMenu_{{ $wallet->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="bx bx-dots-vertical-rounded fs-4"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="walletMenu_{{ $wallet->id }}">
                                                <a class="dropdown-item text-warning editWalletBtn" href="javascript:void(0);" data-id="{{ $wallet->id }}"><i class="bx bx-edit-alt me-2"></i>Edit</a>
                                                <a class="dropdown-item text-danger deleteWalletBtn" href="javascript:void(0);" data-id="{{ $wallet->id }}"><i class="bx bx-trash me-2"></i>Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-2 border-top">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small text-uppercase">Initial Balance</span>
                                            <span class="text-muted small font-monospace">Rp {{ number_format($wallet->initial_balance, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-baseline mt-2">
                                            <span class="text-muted small fw-semibold text-uppercase">Current Balance</span>
                                            <h4 class="mb-0 fw-bold font-monospace text-heading {{ $wallet->current_balance < 0 ? 'text-danger' : 'text-primary' }}">
                                                Rp {{ number_format($wallet->current_balance, 0, ',', '.') }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card text-center py-5 shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="mb-2">No wallets available.</h5>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="tab-pane fade {{ $activeTab == 'categories' ? 'show active' : '' }}" id="tab-categories" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div>
                            <h5 class="mb-0 fw-semibold text-heading">Finance Categories</h5>
                        </div>
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="createNewCategory">
                            <i class="bx bx-plus fs-5"></i>Add Category
                        </button>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="fw-semibold text-success mb-3 d-flex align-items-center gap-2">
                                    <span class="badge bg-label-success p-2 rounded-circle"><i class="bx bx-trending-up"></i></span>
                                    Income
                                </h6>
                                <div class="list-group">
                                    @forelse ($categories->where('type', 'income') as $category)
                                        @php $hasBudget = (float) $category->budget > 0; $budget = $category->budget; @endphp
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">{{ $category->name }}</h6>
                                                    @if($hasBudget)
                                                        <small class="text-muted font-monospace"><i class="bx bx-target-lock me-1"></i>Rp {{ number_format($budget, 0, ',', '.') }}</small>
                                                    @else
                                                        <small class="text-muted fst-italic"><i class="bx bx-infinite me-1"></i>No Budget</small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning editCategoryBtn" data-bs-toggle="tooltip" title="Edit" data-id="{{ $category->id }}">
                                                    <i class="bx bx-edit-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteCategoryBtn" data-bs-toggle="tooltip" title="Delete" data-id="{{ $category->id }}">
                                                    <i class="bx bx-trash"></i>
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
                                    <span class="badge bg-label-danger p-2 rounded-circle"><i class="bx bx-trending-down"></i></span>
                                    Expense
                                </h6>
                                <div class="list-group">
                                    @forelse ($categories->where('type', 'expense') as $category)
                                        @php $hasBudget = (float) $category->budget > 0; $budget = $category->budget; @endphp
                                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">{{ $category->name }}</h6>
                                                    @if($hasBudget)
                                                        <small class="text-muted font-monospace"><i class="bx bx-target-lock me-1"></i>Rp {{ number_format($budget, 0, ',', '.') }}</small>
                                                    @else
                                                        <small class="text-muted fst-italic"><i class="bx bx-infinite me-1"></i>No Budget</small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning editCategoryBtn" data-bs-toggle="tooltip" title="Edit" data-id="{{ $category->id }}">
                                                    <i class="bx bx-edit-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteCategoryBtn" data-bs-toggle="tooltip" title="Delete" data-id="{{ $category->id }}">
                                                    <i class="bx bx-trash"></i>
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
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div>
                            <h5 class="mb-0 fw-semibold text-heading">Finance Tags</h5>
                        </div>
                        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="createNewTag">
                            <i class="bx bx-plus fs-5"></i>Add Tag
                        </button>
                    </div>
                    <div class="card-body pt-4">
                        <div class="d-flex flex-wrap gap-3">
                            @forelse ($tags as $tag)
                                <div class="d-inline-flex align-items-center p-2 rounded shadow-sm border bg-white">
                                    <span class="badge rounded-pill d-inline-flex align-items-center gap-1 px-3 py-2 me-3" style="background-color: {{ $tag->color }}15; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40; font-size: 0.85rem;">
                                        <i class="bx bx-tag"></i> {{ $tag->name }}
                                        @if(($tag->transactions_count ?? 0) > 0)
                                            <span class="badge bg-white text-dark ms-1 rounded-circle px-1" style="border: 1px solid {{ $tag->color }}40;">{{ $tag->transactions_count }}</span>
                                        @endif
                                    </span>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-warning editTagBtn" data-bs-toggle="tooltip" title="Edit" data-id="{{ $tag->id }}">
                                            <i class="bx bx-edit-alt"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteTagBtn" data-bs-toggle="tooltip" title="Delete" data-id="{{ $tag->id }}">
                                            <i class="bx bx-trash"></i>
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
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 border-bottom">
                        <div>
                            <h5 class="mb-0 fw-semibold text-heading">Recurring</h5>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            @if($dueCount > 0)
                                <button type="button" class="btn btn-warning d-inline-flex align-items-center gap-1 shadow-sm" id="btnGenerateDue">
                                    <i class="bx bx-play-circle fs-5"></i>Process Due Now ({{ $dueCount }})
                                </button>
                            @endif
                            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="createNewRecurring">
                                <i class="bx bx-plus fs-5"></i>Add Recurring Rule
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-striped align-middle" id="recurringTable">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Category & Type</th>
                                        <th>Wallet</th>
                                        <th class="text-end">Amount</th>
                                        <th>Frequency</th>
                                        <th>Next Due Date</th>
                                        <th>End Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @foreach ($recurrings as $rec)
                                        @php
                                            $isDue = $rec->is_active && $rec->next_due_date->isPast() || ($rec->is_active && $rec->next_due_date->isToday());
                                        @endphp
                                        <tr>
                                            <td>
                                                @if ($rec->is_active)
                                                    <span class="badge bg-label-success d-inline-flex align-items-center gap-1">
                                                        <i class="bx bx-check-circle"></i> Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-label-secondary d-inline-flex align-items-center gap-1">
                                                        <i class="bx bx-pause-circle"></i> Paused
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-semibold text-heading">{{ $rec->category->name ?? 'Unknown' }}</span>
                                                    @if ($rec->type === 'income')
                                                        <span class="badge bg-label-success" style="font-size: 0.65rem;">Income</span>
                                                    @else
                                                        <span class="badge bg-label-danger" style="font-size: 0.65rem;">Expense</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-medium text-heading">{{ $rec->wallet->name ?? 'Unknown' }}</span>
                                            </td>
                                            <td class="text-end">
                                                @if ($rec->type === 'income')
                                                    <span class="text-success fw-semibold font-monospace">+ Rp {{ number_format($rec->amount, 0, ',', '.') }}</span>
                                                @else
                                                    <span class="text-danger fw-semibold font-monospace">- Rp {{ number_format($rec->amount, 0, ',', '.') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-label-info text-uppercase">{{ $rec->frequency }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="fw-medium {{ $isDue ? 'text-danger fw-bold' : 'text-heading' }}">
                                                        {{ $rec->next_due_date ? $rec->next_due_date->format('d M Y') : '—' }}
                                                    </span>
                                                    @if($isDue)
                                                        <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">Due</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $rec->end_date ? $rec->end_date->format('d M Y') : 'No end date' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button type="button" class="btn btn-sm btn-outline-warning editRecBtn" data-bs-toggle="tooltip" title="Edit Rule" data-id="{{ $rec->id }}">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger deleteRecBtn" data-bs-toggle="tooltip" title="Delete Rule" data-id="{{ $rec->id }}">
                                                        <i class="bx bx-trash"></i>
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
    <div class="modal fade" id="walletModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <form id="walletForm">
                    @csrf
                    <input type="hidden" name="wallet_id" id="wallet_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-semibold" id="walletModalTitle">Add Wallet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="wallet_name">Name <span class="text-danger">*</span></label>
                                <input type="text" id="wallet_name" name="name" class="form-control" placeholder="e.g., Bank BCA, Petty Cash, Driver Cash">
                                <div class="invalid-feedback" id="wallet_nameError"></div>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label" for="initial_balance">Initial Balance <span class="text-danger">*</span></label>
                                <input type="text" id="initial_balance" name="initial_balance" class="form-control text-end font-monospace" placeholder="e.g., 10.000.000">
                                <div class="invalid-feedback" id="initial_balanceError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveWalletBtn" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-save"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <form id="categoryForm">
                    @csrf
                    <input type="hidden" name="category_id" id="category_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-semibold" id="categoryModalTitle">Add Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="category_name">Name <span class="text-danger">*</span></label>
                                <input type="text" id="category_name" name="name" class="form-control" placeholder="e.g., Fuel & Toll, Vehicle Maintenance, Driver Salary">
                                <div class="invalid-feedback" id="category_nameError"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" id="category_type" class="form-select">
                                    <option value="" selected disabled>Select Type</option>
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                                <div class="invalid-feedback" id="category_typeError"></div>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label" for="budget">Budget (Optional)</label>
                                <input type="text" id="budget" name="budget" class="form-control text-end font-monospace" placeholder="5.000.000">
                                <div class="invalid-feedback" id="budgetError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveCategoryBtn" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-save"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="tagModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <form id="tagForm">
                    @csrf
                    <input type="hidden" name="tag_id" id="tag_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-semibold" id="tagModalTitle">Add Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="tag_name">Name <span class="text-danger">*</span></label>
                                <input type="text" id="tag_name" name="name" class="form-control" placeholder="e.g. liburan">
                                <div class="invalid-feedback" id="tag_nameError"></div>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label d-block">Color</label>
                                <div class="d-flex flex-wrap gap-2" id="color-palette">
                                    <!-- Blue -->
                                    <div class="form-check custom-option custom-option-color m-0 p-0">
                                        <input type="radio" class="btn-check tag-color-preset" name="color" id="color_blue" value="#696cff" autocomplete="off" checked>
                                        <label class="btn p-1 rounded-circle" for="color_blue" style="width: 32px; height: 32px; border: 2px solid #696cff; transition: all 0.2s;" onclick="document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent'); this.style.borderColor = '#696cff';">
                                            <span class="rounded-circle d-block w-100 h-100" style="background-color: #696cff;" data-bs-toggle="tooltip" title="Blue"></span>
                                        </label>
                                    </div>
                                    <!-- Gray -->
                                    <div class="form-check custom-option custom-option-color m-0 p-0">
                                        <input type="radio" class="btn-check tag-color-preset" name="color" id="color_gray" value="#8592a3" autocomplete="off">
                                        <label class="btn p-1 rounded-circle" for="color_gray" style="width: 32px; height: 32px; border: 2px solid transparent; transition: all 0.2s;" onclick="document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent'); this.style.borderColor = '#8592a3';">
                                            <span class="rounded-circle d-block w-100 h-100" style="background-color: #8592a3;" data-bs-toggle="tooltip" title="Gray"></span>
                                        </label>
                                    </div>
                                    <!-- Green -->
                                    <div class="form-check custom-option custom-option-color m-0 p-0">
                                        <input type="radio" class="btn-check tag-color-preset" name="color" id="color_green" value="#71dd37" autocomplete="off">
                                        <label class="btn p-1 rounded-circle" for="color_green" style="width: 32px; height: 32px; border: 2px solid transparent; transition: all 0.2s;" onclick="document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent'); this.style.borderColor = '#71dd37';">
                                            <span class="rounded-circle d-block w-100 h-100" style="background-color: #71dd37;" data-bs-toggle="tooltip" title="Green"></span>
                                        </label>
                                    </div>
                                    <!-- Red -->
                                    <div class="form-check custom-option custom-option-color m-0 p-0">
                                        <input type="radio" class="btn-check tag-color-preset" name="color" id="color_red" value="#ff3e1d" autocomplete="off">
                                        <label class="btn p-1 rounded-circle" for="color_red" style="width: 32px; height: 32px; border: 2px solid transparent; transition: all 0.2s;" onclick="document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent'); this.style.borderColor = '#ff3e1d';">
                                            <span class="rounded-circle d-block w-100 h-100" style="background-color: #ff3e1d;" data-bs-toggle="tooltip" title="Red"></span>
                                        </label>
                                    </div>
                                    <!-- Yellow -->
                                    <div class="form-check custom-option custom-option-color m-0 p-0">
                                        <input type="radio" class="btn-check tag-color-preset" name="color" id="color_yellow" value="#ffab00" autocomplete="off">
                                        <label class="btn p-1 rounded-circle" for="color_yellow" style="width: 32px; height: 32px; border: 2px solid transparent; transition: all 0.2s;" onclick="document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent'); this.style.borderColor = '#ffab00';">
                                            <span class="rounded-circle d-block w-100 h-100" style="background-color: #ffab00;" data-bs-toggle="tooltip" title="Yellow"></span>
                                        </label>
                                    </div>
                                    <!-- Cyan -->
                                    <div class="form-check custom-option custom-option-color m-0 p-0">
                                        <input type="radio" class="btn-check tag-color-preset" name="color" id="color_cyan" value="#03c3ec" autocomplete="off">
                                        <label class="btn p-1 rounded-circle" for="color_cyan" style="width: 32px; height: 32px; border: 2px solid transparent; transition: all 0.2s;" onclick="document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent'); this.style.borderColor = '#03c3ec';">
                                            <span class="rounded-circle d-block w-100 h-100" style="background-color: #03c3ec;" data-bs-toggle="tooltip" title="Cyan"></span>
                                        </label>
                                    </div>
                                    <!-- Dark -->
                                    <div class="form-check custom-option custom-option-color m-0 p-0">
                                        <input type="radio" class="btn-check tag-color-preset" name="color" id="color_dark" value="#233446" autocomplete="off">
                                        <label class="btn p-1 rounded-circle" for="color_dark" style="width: 32px; height: 32px; border: 2px solid transparent; transition: all 0.2s;" onclick="document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent'); this.style.borderColor = '#233446';">
                                            <span class="rounded-circle d-block w-100 h-100" style="background-color: #233446;" data-bs-toggle="tooltip" title="Dark"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="invalid-feedback d-block mt-1" style="display:none;" id="tag_colorError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveTagBtn" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-save"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="recurringModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow">
                <form id="recurringForm">
                    @csrf
                    <input type="hidden" name="recurring_id" id="recurring_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-semibold" id="recurringModalTitle">Add Recurring Rule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="rec_wallet_id">Wallet <span class="text-danger">*</span></label>
                                <select id="rec_wallet_id" name="wallet_id" class="form-select">
                                    <option value="" selected disabled>Select Wallet</option>
                                    @foreach ($wallets as $wallet)
                                        <option value="{{ $wallet->id }}">{{ $wallet->name }} (Rp {{ number_format($wallet->current_balance, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="rec_wallet_idError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="rec_category_id">Category <span class="text-danger">*</span></label>
                                <select id="rec_category_id" name="category_id" class="form-select">
                                    <option value="" selected disabled>Select Category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }} ({{ ucfirst($category->type) }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="rec_category_idError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="rec_amount">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" id="rec_amount" name="amount" class="form-control text-end font-monospace" placeholder="1.000.000">
                                </div>
                                <div class="invalid-feedback" id="rec_amountError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="rec_frequency">Frequency <span class="text-danger">*</span></label>
                                <select id="rec_frequency" name="frequency" class="form-select">
                                    <option value="monthly" selected>Monthly (Every month on same date)</option>
                                    <option value="weekly">Weekly (Every week)</option>
                                    <option value="daily">Daily (Every day)</option>
                                    <option value="yearly">Yearly (Every year)</option>
                                </select>
                                <div class="invalid-feedback" id="rec_frequencyError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="rec_start_date">First Due Date / Start Date <span class="text-danger">*</span></label>
                                <input type="date" id="rec_start_date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback" id="rec_start_dateError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="rec_end_date">End Date (Optional)</label>
                                <input type="date" id="rec_end_date" name="end_date" class="form-control">
                                <small class="text-muted">Leave empty to recur indefinitely</small>
                                <div class="invalid-feedback" id="rec_end_dateError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="rec_description">Description (Optional)</label>
                                <textarea id="rec_description" name="description" class="form-control" rows="2" placeholder="e.g., Office internet monthly fee"></textarea>
                                <div class="invalid-feedback" id="rec_descriptionError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="rec_is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label fw-semibold" for="rec_is_active">Rule is active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveRecBtn" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-save"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        $(document).ready(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.extend(true, DataTable.ext.classes, {
                search: { input: 'form-control' },
                length: { select: 'form-select' }
            });
            var recurringTable = $('#recurringTable').DataTable({
                order: [[5, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [7] }
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No recurring rules available.",
                    zeroRecords: "No matching rules found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search Rule",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
            recurringTable.on('draw', function () {
                initTooltips();
            });
            function initTooltips() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            initTooltips();
            function formatRupiah(angka) {
                if (angka === null || angka === undefined || angka === '') return '';
                var isNegative = angka.toString().startsWith('-');
                var str = angka.toString().replace(/^-/, '').split('.')[0];
                var number_string = str.replace(/[^,\d]/g, ''),
                    split = number_string.split(','),
                    sisa = split[0].length % 3,
                    rupiah = split[0].substr(0, sisa),
                    ribuan = split[0].substr(sisa).match(/\d{3}/gi);
                if (ribuan) {
                    var separator = sisa ? '.' : '';
                    rupiah += separator + ribuan.join('.');
                }
                rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
                return (isNegative ? '-' : '') + rupiah;
            }
            $('#initial_balance, #budget, #rec_amount').on('input', function () {
                var val = $(this).val();
                var isNegative = val.startsWith('-');
                var rawValue = val.replace(/\./g, '').replace(/-/g, '');
                var formatted = formatRupiah(rawValue);
                $(this).val(isNegative && formatted !== '' ? '-' + formatted : (isNegative ? '-' : formatted));
            });
            function resetWalletForm() {
                $('#walletForm')[0].reset();
                $('#wallet_id').val('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
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
                initInput.val(formatRupiah(rawInit));
                if (walletId) {
                    formData += '&_method=PUT';
                }
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                $('#saveWalletBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveWalletBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
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
                            window.location.href = '{{ route("finance-settings.index", ["tab" => "wallets"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveWalletBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#wallet_' + field + 'Error, #' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            $('#walletModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Process Request',
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
                    $('#initial_balance').val(formatRupiah(data.initial_balance.toString()));
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
                    title: 'Confirm Wallet Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
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
                                    location.reload();
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 422 ? 'Cannot Delete Wallet' : (xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Wallet'),
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            function resetCategoryForm() {
                $('#categoryForm')[0].reset();
                $('#category_id').val('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
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
                var budgetInput = $('#budget');
                var rawBudget = budgetInput.val() ? budgetInput.val().replace(/\./g, '') : '';
                budgetInput.val(rawBudget);
                var formData = $(this).serialize();
                budgetInput.val(formatRupiah(rawBudget));
                if (categoryId) {
                    formData += '&_method=PUT';
                }
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                $('#saveCategoryBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveCategoryBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
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
                            window.location.href = '{{ route("finance-settings.index", ["tab" => "categories"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveCategoryBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#category_' + field + 'Error, #' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Save Category',
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
                    if (data.budget) {
                        $('#budget').val(formatRupiah(data.budget.toString()));
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
                    title: 'Confirm Category Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
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
                            data: { _token: '{{ csrf_token() }}' },
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Category Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    window.location.href = '{{ route("finance-settings.index", ["tab" => "categories"]) }}';
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 422 ? 'Cannot Delete Category' : (xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Category'),
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            function resetTagForm() {
                $('#tagForm')[0].reset();
                $('#tag_id').val('');
                var firstRadio = $('.tag-color-preset').first();
                firstRadio.prop('checked', true);
                document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent');
                firstRadio.next('label').css('border-color', firstRadio.val());
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
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
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                $('#saveTagBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveTagBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
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
                            window.location.href = '{{ route("finance-settings.index", ["tab" => "tags"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveTagBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#tag_' + field + 'Error, #' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Save Tag',
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
                    var radio = $("input[name='color'][value='" + data.color + "']");
                    if (radio.length) {
                        radio.prop('checked', true);
                        document.querySelectorAll('.tag-color-preset + label').forEach(l => l.style.borderColor = 'transparent');
                        radio.next('label').css('border-color', data.color);
                    }
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
                    title: 'Confirm Tag Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
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
                            data: { _token: '{{ csrf_token() }}' },
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Tag Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    window.location.href = '{{ route("finance-settings.index", ["tab" => "tags"]) }}';
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 422 ? 'Cannot Delete Tag' : (xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Tag'),
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            function resetRecurringForm() {
                $('#recurringForm')[0].reset();
                $('#recurring_id').val('');
                $('#rec_start_date').val(new Date().toISOString().split('T')[0]);
                $('#rec_is_active').prop('checked', true);
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewRecurring').click(function () {
                resetRecurringForm();
                $('#recurringModalTitle').text('Add Recurring Rule');
                $('#recurringModal').modal('show');
            });
            $('#recurringForm').on('submit', function (e) {
                e.preventDefault();
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
                var serialized = $.param(formData);
                if (recurringId) {
                    serialized += '&_method=PUT';
                }
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                $('#saveRecBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: serialized,
                    success: function (data, textStatus, xhr) {
                        $('#saveRecBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
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
                            title: 'Recurring Rule Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            window.location.href = '{{ route("finance-settings.index", ["tab" => "recurring"]) }}';
                        });
                    },
                    error: function (xhr) {
                        $('#saveRecBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#rec_' + field + 'Error, #' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed to Save Rule',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editRecBtn', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Loading Rule...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/finance-recurring/' + id + '/edit', function (data) {
                    Swal.close();
                    resetRecurringForm();
                    $('#recurringModalTitle').text('Edit Recurring Rule');
                    $('#recurring_id').val(data.id);
                    $('#rec_wallet_id').val(data.wallet_id);
                    $('#rec_category_id').val(data.category_id);
                    $('#rec_amount').val(formatRupiah(data.amount.toString()));
                    $('#rec_frequency').val(data.frequency);
                    $('#rec_start_date').val(data.start_date);
                    $('#rec_end_date').val(data.end_date || '');
                    $('#rec_description').val(data.description);
                    $('#rec_is_active').prop('checked', !!data.is_active);
                    $('#recurringModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Rule',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            $('body').on('click', '.deleteRecBtn', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Confirm Rule Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting Rule...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/finance-recurring/' + id,
                            data: { _token: '{{ csrf_token() }}' },
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Rule Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    window.location.href = '{{ route("finance-settings.index", ["tab" => "recurring"]) }}';
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Rule',
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            $('#btnGenerateDue').on('click', function () {
                Swal.fire({
                    title: 'Process Due Recurring Transactions?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'Yes, process now!'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Processing Transactions...',
                            allowOutsideClick: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'POST',
                            url: '{{ route("finance-recurring.generate") }}',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (res) {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Generated ' + (res.generated || 0) + ' Transaction(s)',
                                    confirmButtonColor: '#696cff'
                                }).then(function () {
                                    window.location.href = '{{ route("finance-settings.index", ["tab" => "recurring"]) }}';
                                });
                            },
                            error: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Processing Failed',
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