@extends('layouts.app')
@section('title', 'Finance Transactions')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row mb-4 g-3">
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="card-info">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Income</span>
                                <h3 class="card-title text-success mb-1 fw-bold">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
                                <span class="badge bg-label-success small mt-1" title="{{ $currentMonthLabel }}">
                                    <i class="bx bx-calendar me-1"></i>{{ $currentMonthLabel }}
                                </span>
                            </div>
                            <div class="avatar avatar-lg">
                                <span class="avatar-initial rounded-3 bg-label-success shadow-sm">
                                    <i class="bx bx-trending-up fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="card-info">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Expense</span>
                                <h3 class="card-title text-danger mb-1 fw-bold">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
                                <span class="badge bg-label-danger small mt-1" title="{{ $currentMonthLabel }}">
                                    <i class="bx bx-calendar me-1"></i>{{ $currentMonthLabel }}
                                </span>
                            </div>
                            <div class="avatar avatar-lg">
                                <span class="avatar-initial rounded-3 bg-label-danger shadow-sm">
                                    <i class="bx bx-trending-down fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-12">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="card-info">
                                <span class="text-muted small fw-semibold text-uppercase d-block mb-1">Total Wallet Balance</span>
                                <h3 class="card-title mb-1 fw-bold {{ $netBalance >= 0 ? 'text-primary' : 'text-warning' }}">
                                    {{ $netBalance < 0 ? '— ' : '' }}Rp {{ number_format(abs($netBalance), 0, ',', '.') }}
                                </h3>
                                <span class="badge bg-label-{{ $netBalance >= 0 ? 'primary' : 'warning' }} small mt-1">
                                    <i class="bx bx-wallet me-1"></i>All Active Wallets
                                </span>
                            </div>
                            <div class="avatar avatar-lg">
                                <span class="avatar-initial rounded-3 bg-label-{{ $netBalance >= 0 ? 'primary' : 'warning' }} shadow-sm">
                                    <i class="bx bx-wallet fs-2"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom py-3">
                <div>
                    <h5 class="mb-0 fw-semibold text-heading">Finance Transaction</h5>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" id="openTransferModal">
                        <i class="bx bx-transfer fs-5"></i>Add Transfer
                    </button>
                    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" id="createNewTransaction">
                        <i class="bx bx-plus fs-5"></i>Add Transaction
                    </button>
                </div>
            </div>
            <div class="card-body pt-4">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-1 me-2">
                        <input type="date" id="filterStartDate" class="form-control form-control-sm" value="{{ $startDate }}" title="Start Date">
                        <span class="text-muted">-</span>
                        <input type="date" id="filterEndDate" class="form-control form-control-sm" value="{{ $endDate }}" title="End Date">
                        <button type="button" id="applyDateFilter" class="btn btn-sm btn-outline-primary" title="Apply Date Filter">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bx bx-calendar-event me-1"></i>Presets
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li><a class="dropdown-item date-preset-opt" href="#" data-preset="this_month"><i class="bx bx-calendar me-2"></i>This Month</a></li>
                            <li><a class="dropdown-item date-preset-opt" href="#" data-preset="last_month"><i class="bx bx-history me-2"></i>Last Month</a></li>
                            <li><a class="dropdown-item date-preset-opt" href="#" data-preset="this_year"><i class="bx bx-calendar-alt me-2"></i>This Year</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item date-preset-opt" href="#" data-preset="all_time"><i class="bx bx-infinite me-2"></i>All Time</a></li>
                        </ul>
                    </div>
                    <div class="vr mx-2 text-muted d-none d-md-block"></div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterWallet" data-filter-label="Wallet">
                            Wallet
                        </button>
                        <ul class="dropdown-menu filterMenu shadow-sm" data-filter-target="filterWallet">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Wallets</a></li>
                            @foreach ($wallets as $wallet)
                                <li><a class="dropdown-item filterOption" href="#" data-value="{{ $wallet->name }}">{{ $wallet->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterCategory" data-filter-label="Category">
                            Category
                        </button>
                        <ul class="dropdown-menu filterMenu shadow-sm" data-filter-target="filterCategory">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Categories</a></li>
                            @foreach ($filterCategories as $cat)
                                <li><a class="dropdown-item filterOption" href="#" data-value="{{ $cat }}">{{ $cat }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterType" data-filter-label="Type">
                            Type
                        </button>
                        <ul class="dropdown-menu filterMenu shadow-sm" data-filter-target="filterType">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Types</a></li>
                            @foreach ($filterTypes as $type)
                                <li><a class="dropdown-item filterOption" href="#" data-value="{{ $type }}">{{ ucfirst($type) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @if(isset($filterTags) && $filterTags->count() > 0)
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterTag" data-filter-label="Tag">
                                Tag
                            </button>
                            <ul class="dropdown-menu filterMenu shadow-sm" data-filter-target="filterTag">
                                <li><a class="dropdown-item filterOption" href="#" data-value="">All Tags</a></li>
                                @foreach ($filterTags as $tagName)
                                    <li><a class="dropdown-item filterOption" href="#" data-value="{{ $tagName }}">#{{ $tagName }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if (auth()->user()->isAdmin())
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterUser" data-filter-label="User">
                                User
                            </button>
                            <ul class="dropdown-menu filterMenu shadow-sm" data-filter-target="filterUser">
                                <li><a class="dropdown-item filterOption" href="#" data-value="">All Users</a></li>
                                @foreach ($ledger->pluck('user.username')->unique()->filter()->sort() as $username)
                                    <li><a class="dropdown-item filterOption" href="#" data-value="{{ $username }}">{{ $username }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <button type="button" id="clearFilters" class="btn btn-sm btn-link text-danger d-none align-items-center gap-1 text-decoration-none ms-1">
                        <i class="bx bx-x-circle"></i>
                        <span>Clear all</span>
                    </button>
                </div>
                <div id="activeFilterChips" class="d-flex flex-wrap gap-2 mb-3"></div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover align-middle border-top-0" id="transactionTable">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 rounded-start">Date</th>
                                <th class="border-0">Wallet</th>
                                <th class="border-0">Category</th>
                                <th class="border-0">Type</th>
                                <th class="border-0 text-end">Amount</th>
                                @if (auth()->user()->isAdmin())
                                    <th class="border-0">User</th>
                                @endif
                                <th class="border-0 text-center rounded-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($ledger as $item)
                                @php
                                    $isTransfer = $item->isTransfer();
                                    $isRecurring = $item->isRecurring();
                                    $fromWalletName = $item->wallet->name ?? 'Unknown';
                                    $toWalletName = $item->transferPair && $item->transferPair->wallet ? $item->transferPair->wallet->name : 'Unknown';
                                    $categoryName = $isTransfer ? 'Transfer' : ($item->category->name ?? 'Uncategorized');
                                    $tagNames = $item->tags->pluck('name')->implode(',');
                                    $canModify = auth()->user()->isAdmin() || $item->user_id === auth()->id();
                                @endphp
                                <tr data-tags="{{ $tagNames }}">
                                    <td>
                                        <span class="fw-medium text-heading">{{ $item->transaction_date->format('d M Y') }}</span>
                                        @if($isRecurring)
                                            <span class="badge bg-label-secondary ms-1" style="font-size: 0.65rem;" title="Generated from recurring schedule">
                                                <i class="bx bx-sync me-1"></i>Auto
                                            </span>
                                        @endif
                                    </td>
                                    <td data-wallet="{{ $isTransfer ? $fromWalletName . ' ' . $toWalletName : $fromWalletName }}">
                                        @if ($isTransfer)
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="fw-medium text-heading">{{ $fromWalletName }}</span>
                                                <i class="bx bx-right-arrow-alt text-primary fs-5"></i>
                                                <span class="fw-medium text-heading">{{ $toWalletName }}</span>
                                            </div>
                                        @else
                                            <span class="fw-medium text-heading">{{ $fromWalletName }}</span>
                                            @if ($item->wallet && $item->wallet->trashed())
                                                <span class="badge bg-label-danger ms-1" style="font-size: 0.65rem;">Deleted</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td data-category="{{ $categoryName }}">
                                        @if ($isTransfer)
                                            <span class="text-muted fst-italic">—</span>
                                        @else
                                            <span class="fw-medium text-heading">{{ $categoryName }}</span>
                                            @if ($item->category && $item->category->trashed())
                                                <span class="badge bg-label-danger ms-1" style="font-size: 0.65rem;">Deleted</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td data-type="{{ $isTransfer ? 'transfer' : $item->type }}">
                                        @if ($isTransfer)
                                            <span class="badge bg-label-info d-inline-flex align-items-center gap-1">
                                                <i class="bx bx-transfer"></i> Transfer
                                            </span>
                                        @elseif ($item->type === 'income')
                                            <span class="badge bg-label-success d-inline-flex align-items-center gap-1">
                                                <i class="bx bx-trending-up"></i> Income
                                            </span>
                                        @else
                                            <span class="badge bg-label-danger d-inline-flex align-items-center gap-1">
                                                <i class="bx bx-trending-down"></i> Expense
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($isTransfer)
                                            <span class="fw-semibold font-monospace text-heading">Rp {{ number_format($item->amount, 0, ',', '.') }}</span>
                                        @elseif ($item->type === 'income')
                                            <span class="text-success fw-semibold font-monospace">+ Rp {{ number_format($item->amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-danger fw-semibold font-monospace">- Rp {{ number_format($item->amount, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    @if (auth()->user()->isAdmin())
                                        <td>
                                            @if ($item->user)
                                                <span class="text-body">{{ $item->user->username }}</span>
                                                @if ($item->user->trashed())
                                                    <span class="badge bg-label-danger ms-1" style="font-size: 0.65rem;">Deleted</span>
                                                @endif
                                            @else
                                                <span class="text-danger fst-italic">Unknown</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if ($isTransfer)
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info viewTransferBtn" data-bs-toggle="tooltip" title="View"
                                                    data-date="{{ $item->transaction_date->format('d M Y') }}"
                                                    data-from="{{ $fromWalletName }}"
                                                    data-to="{{ $toWalletName }}"
                                                    data-amount="Rp {{ number_format($item->amount, 0, ',', '.') }}"
                                                    data-desc="{{ $item->description ?? '—' }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                                @if ($canModify)
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-warning editTransferBtn" data-bs-toggle="tooltip" title="Edit" data-id="{{ $item->id }}">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteBtn" data-bs-toggle="tooltip" title="Delete" data-id="{{ $item->id }}">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                @endif
                                            @else
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-info viewTransactionBtn" data-bs-toggle="tooltip" title="View"
                                                    data-date="{{ $item->transaction_date->format('d M Y') }}"
                                                    data-wallet="{{ $fromWalletName }}"
                                                    data-category="{{ $categoryName }}"
                                                    data-type="{{ ucfirst($item->type) }}"
                                                    data-amount="Rp {{ number_format($item->amount, 0, ',', '.') }}"
                                                    data-desc="{{ $item->description ?? '—' }}"
                                                    data-tags="{{ $tagNames }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                                @if ($canModify)
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-warning editBtn" data-bs-toggle="tooltip" title="Edit" data-id="{{ $item->id }}">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteBtn" data-bs-toggle="tooltip" title="Delete" data-id="{{ $item->id }}">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                @endif
                                            @endif
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
    <div class="modal fade" id="transactionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow">
                <form id="transactionForm">
                    @csrf
                    <input type="hidden" name="transaction_id" id="transaction_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-semibold" id="modalTitle">Add Transaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="transaction_date">Date <span class="text-danger">*</span></label>
                                <input type="date" id="transaction_date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback" id="transaction_dateError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                                <input type="text" id="amount" name="amount" class="form-control text-end font-monospace" placeholder="Contoh: 350.000">
                                <div class="invalid-feedback" id="amountError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="wallet_id">Wallet <span class="text-danger">*</span></label>
                                <select id="wallet_id" name="wallet_id" class="form-select">
                                    <option value="" selected disabled>Select Wallet</option>
                                    @foreach ($wallets as $wallet)
                                        <option value="{{ $wallet->id }}">
                                            {{ $wallet->name }} (Rp {{ number_format($wallet->current_balance, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="wallet_idError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="" selected disabled>Select Category</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name }} ({{ ucfirst($category->type) }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="category_idError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="tags_input">Tags (Optional)</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="bx bx-purchase-tag"></i></span>
                                    <input type="text" id="tags_input" class="form-control" placeholder="Ketik nama tag lalu tekan Enter (atau pilih di bawah)">
                                </div>
                                <div id="selectedTagsContainer" class="d-flex flex-wrap gap-2 mt-2"></div>
                                <div id="hiddenTagsInputs"></div>
                                @if($tags->count() > 0)
                                    <div class="mt-2 pt-2 border-top">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="text-muted small">
                                                <i class="bx bx-list-ul me-1"></i>Available Tags:
                                            </span>
                                            <span class="text-muted small" id="tagMatchCount" style="font-size: 0.75rem;"></span>
                                        </div>
                                        <div id="quickTagsSuggestions" class="d-flex flex-wrap gap-1" style="max-height: 85px; overflow-y: auto;">
                                            @foreach($tags as $tag)
                                                <button type="button" 
                                                    class="btn btn-xs rounded-pill quick-tag-btn d-inline-flex align-items-center gap-1"
                                                    data-tag-name="{{ $tag->name }}"
                                                    style="background-color: {{ $tag->color }}15; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40; font-size: 0.75rem; padding: 0.25rem 0.6rem;">
                                                    <i class="bx bx-plus fs-6 quick-tag-icon"></i>
                                                    <span>{{ $tag->name }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                        <div id="noTagsFoundHint" class="text-muted small fst-italic py-1 d-none">
                                            Press <kbd class="px-1 py-0 bg-light border text-dark">Enter</kbd> to add new tag "<span id="newTagNameDisplay" class="fw-semibold text-primary"></span>"
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="form-label" for="description">Description (Optional)</label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="Contoh: Pembelian Solar Hiace B 1234 YK, Isi Saldo E-Toll Operasional, Servis Rutin Avanza, Uang Jalan Driver"></textarea>
                                <div class="invalid-feedback" id="descriptionError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveBtn" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-save"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="transferModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow">
                <form id="transferForm">
                    @csrf
                    <input type="hidden" name="transfer_id" id="transfer_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-semibold" id="transferModalTitle">Add Transfer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="transfer_transaction_date">Date <span class="text-danger">*</span></label>
                                <input type="date" id="transfer_transaction_date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback" id="transfer_transaction_dateError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="transfer_amount">Amount <span class="text-danger">*</span></label>
                                <input type="text" id="transfer_amount" name="amount" class="form-control text-end font-monospace" placeholder="Contoh: 1.500.000">
                                <div class="invalid-feedback" id="transfer_amountError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="from_wallet_id">From Wallet (Source) <span class="text-danger">*</span></label>
                                <select id="from_wallet_id" name="from_wallet_id" class="form-select">
                                    <option value="" selected disabled>Select Source Wallet</option>
                                    @foreach ($wallets as $wallet)
                                        <option value="{{ $wallet->id }}">{{ $wallet->name }} (Rp {{ number_format($wallet->current_balance, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="from_wallet_idError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="to_wallet_id">To Wallet (Destination) <span class="text-danger">*</span></label>
                                <select id="to_wallet_id" name="to_wallet_id" class="form-select">
                                    <option value="" selected disabled>Select Destination Wallet</option>
                                    @foreach ($wallets as $wallet)
                                        <option value="{{ $wallet->id }}">{{ $wallet->name }} (Rp {{ number_format($wallet->current_balance, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="to_wallet_idError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="form-label" for="transfer_description">Description (Optional)</label>
                                <textarea id="transfer_description" name="description" class="form-control" rows="2" placeholder="Contoh: Tarik tunai kas jalan driver, Pindah dana BCA ke Kas Operasional Kantor"></textarea>
                                <div class="invalid-feedback" id="transfer_descriptionError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveTransferBtn" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="bx bx-save"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewTransactionModal" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center p-3 mb-4 rounded-3 bg-label-secondary">
                        <span id="view_transaction_type_badge" class="badge bg-label-success mb-2 px-3 py-1">Income</span>
                        <h2 id="view_transaction_amount" class="mb-0 fw-bold font-monospace text-heading"></h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <span class="text-muted">Date</span>
                            <span id="view_transaction_date" class="fw-medium text-heading"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <span class="text-muted">Wallet</span>
                            <span id="view_transaction_wallet" class="fw-medium text-heading"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <span class="text-muted">Category</span>
                            <span id="view_transaction_category" class="fw-medium text-heading"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <span class="text-muted">Tags</span>
                            <span id="view_transaction_tags" class="fw-medium text-heading"></span>
                        </li>
                        <li class="list-group-item px-0 py-2">
                            <span class="text-muted d-block mb-1">Description</span>
                            <p id="view_transaction_desc" class="mb-0 text-heading fst-italic"></p>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewTransferModal" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">Transfer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center p-3 mb-4 rounded-3 bg-label-info">
                        <span class="badge bg-info mb-2 px-3 py-1"><i class="bx bx-transfer me-1"></i>Transfer</span>
                        <h2 id="view_transfer_amount" class="mb-0 fw-bold font-monospace text-heading"></h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <span class="text-muted">Date</span>
                            <span id="view_transfer_date" class="fw-medium text-heading"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <span class="text-muted">From Wallet</span>
                            <span id="view_transfer_from" class="fw-medium text-heading"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-bottom">
                            <span class="text-muted">To Wallet</span>
                            <span id="view_transfer_to" class="fw-medium text-heading"></span>
                        </li>
                        <li class="list-group-item px-0 py-2">
                            <span class="text-muted d-block mb-1">Description</span>
                            <p id="view_transfer_desc" class="mb-0 text-heading fst-italic"></p>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        $(document).ready(function () {
            var filterState = {
                filterWallet: '',
                filterCategory: '',
                filterType: '',
                filterTag: '',
                @if (auth()->user()->isAdmin())
                    filterUser: ''
                @endif
            };
            var filterLabels = {
                filterWallet: 'Wallet',
                filterCategory: 'Category',
                filterType: 'Type',
                filterTag: 'Tag',
                @if (auth()->user()->isAdmin())
                    filterUser: 'User'
                @endif
            };
            var table = $('#transactionTable').DataTable({
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [{{ auth()->user()->isAdmin() ? '6' : '5' }}] }
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No transactions available.",
                    zeroRecords: "No matching transactions found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Cari transaksi, driver, armada, keterangan...",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'transactionTable') return true;
                var tr = settings.aoData[dataIndex].nTr;
                var walletCol = $(tr).find('td:eq(1)').attr('data-wallet') || data[1] || '';
                var categoryCol = $(tr).find('td:eq(2)').attr('data-category') || '';
                var typeCol = $(tr).find('td:eq(3)').attr('data-type') || '';
                var tagsCol = $(tr).attr('data-tags') || '';
                @if (auth()->user()->isAdmin())
                    var userCol = data[5] || '';
                @endif
                if (filterState.filterWallet && walletCol.indexOf(filterState.filterWallet) === -1) return false;
                if (filterState.filterCategory && categoryCol !== filterState.filterCategory) return false;
                if (filterState.filterType && typeCol !== filterState.filterType) return false;
                if (filterState.filterTag && tagsCol.indexOf(filterState.filterTag) === -1) return false;
                @if (auth()->user()->isAdmin())
                    if (filterState.filterUser && userCol !== filterState.filterUser) return false;
                @endif
                return true;
            });
            function setDropdownState(target, hasValue) {
                $('.filterDropdownBtn[data-filter-target="' + target + '"]')
                    .toggleClass('btn-primary', hasValue)
                    .toggleClass('text-white', hasValue)
                    .toggleClass('btn-outline-secondary', !hasValue);
            }
            function renderFilterChips() {
                var chipsHtml = '';
                var activeCount = 0;
                $.each(filterState, function (key, value) {
                    if (value) {
                        activeCount++;
                        chipsHtml += '<span class="badge rounded-pill bg-primary-subtle text-primary d-inline-flex align-items-center gap-1 py-2 px-3 shadow-none border border-primary-subtle">' +
                            '<span class="fw-semibold">' + filterLabels[key] + ':</span>' +
                            '<span>' + value + '</span>' +
                            '<i class="bx bx-x chip-remove" role="button" data-target="' + key + '" style="cursor:pointer;"></i>' +
                            '</span>';
                    }
                });
                $('#activeFilterChips').html(chipsHtml);
                $('#clearFilters')
                    .toggleClass('d-none', activeCount === 0)
                    .toggleClass('d-inline-flex', activeCount > 0);
            }
            $('body').on('click', '.filterOption', function (e) {
                e.preventDefault();
                var target = $(this).closest('.filterMenu').data('filter-target');
                var value = $(this).data('value') || '';
                var text = $(this).text();
                var label = $('.filterDropdownBtn[data-filter-target="' + target + '"]').data('filter-label');
                filterState[target] = value;
                $('.filterDropdownBtn[data-filter-target="' + target + '"]').text(value ? text : label);
                setDropdownState(target, !!value);
                renderFilterChips();
                table.draw();
            });
            $('body').on('click', '.chip-remove', function () {
                var target = $(this).data('target');
                var label = $('.filterDropdownBtn[data-filter-target="' + target + '"]').data('filter-label');
                filterState[target] = '';
                $('.filterDropdownBtn[data-filter-target="' + target + '"]').text(label);
                setDropdownState(target, false);
                renderFilterChips();
                table.draw();
            });
            $('#clearFilters').on('click', function () {
                $.each(filterState, function (key) {
                    filterState[key] = '';
                    var label = $('.filterDropdownBtn[data-filter-target="' + key + '"]').data('filter-label');
                    $('.filterDropdownBtn[data-filter-target="' + key + '"]').text(label);
                    setDropdownState(key, false);
                });
                renderFilterChips();
                table.draw();
            });
            renderFilterChips();
            function initTooltips() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            initTooltips();
            table.on('draw', function () {
                initTooltips();
            });
            function formatRupiah(angka) {
                if (!angka) return '';
                var number_string = angka.toString().replace(/\D/g, ''),
                    split = number_string.split(','),
                    sisa = split[0].length % 3,
                    rupiah = split[0].substr(0, sisa),
                    ribuan = split[0].substr(sisa).match(/\d{3}/gi);
                if (ribuan) {
                    var separator = sisa ? '.' : '';
                    rupiah += separator + ribuan.join('.');
                }
                rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
                return rupiah;
            }
            $('#amount, #transfer_amount').on('input', function () {
                $(this).val(formatRupiah($(this).val()));
            });
            $('.date-preset-opt').on('click', function (e) {
                e.preventDefault();
                var preset = $(this).data('preset');
                var now = new Date();
                var start, end;
                if (preset === 'this_month') {
                    start = new Date(now.getFullYear(), now.getMonth(), 1);
                    end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                } else if (preset === 'last_month') {
                    start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                    end = new Date(now.getFullYear(), now.getMonth(), 0);
                } else if (preset === 'this_year') {
                    start = new Date(now.getFullYear(), 0, 1);
                    end = new Date(now.getFullYear(), 11, 31);
                } else if (preset === 'all_time') {
                    start = new Date(2020, 0, 1);
                    end = new Date(now.getFullYear() + 1, 11, 31);
                }
                var formatDate = function (d) {
                    return d.toISOString().split('T')[0];
                };
                $('#filterStartDate').val(formatDate(start));
                $('#filterEndDate').val(formatDate(end));
                $('#applyDateFilter').click();
            });
            $('#applyDateFilter').on('click', function () {
                var start = $('#filterStartDate').val();
                var end = $('#filterEndDate').val();
                var url = new URL(window.location.href);
                url.searchParams.set('start_date', start);
                url.searchParams.set('end_date', end);
                window.location.href = url.toString();
            });
            var availableTagsMap = {
                @foreach ($tags as $tag)
                    "{{ addslashes($tag->name) }}": "{{ $tag->color }}",
                @endforeach
            };
            var currentTags = [];
            function renderSelectedTags() {
                var html = '';
                var inputsHtml = '';
                currentTags.forEach(function (tag, index) {
                    var color = availableTagsMap[tag] || '#696cff';
                    html += '<span class="badge rounded-pill d-inline-flex align-items-center gap-1 py-1 px-3" style="background-color: ' + color + '15; color: ' + color + '; border: 1px solid ' + color + '40; font-size: 0.8rem;">' +
                        '<i class="bx bx-tag fs-6"></i> ' + tag +
                        '<i class="bx bx-x remove-tag-chip fs-5 ms-1" data-index="' + index + '" style="cursor:pointer;"></i>' +
                        '</span>';
                    inputsHtml += '<input type="hidden" name="tags[]" value="' + tag + '">';
                });
                $('#selectedTagsContainer').html(html);
                $('#hiddenTagsInputs').html(inputsHtml);
                updateQuickTagsState();
            }
            function updateQuickTagsState() {
                $('.quick-tag-btn').each(function () {
                    var tagName = String($(this).data('tag-name'));
                    var isSelected = currentTags.indexOf(tagName) !== -1;
                    var icon = $(this).find('.quick-tag-icon');
                    if (isSelected) {
                        $(this).addClass('active').css('opacity', '0.4').css('text-decoration', 'line-through');
                        icon.removeClass('bx-plus').addClass('bx-check');
                    } else {
                        $(this).removeClass('active').css('opacity', '1').css('text-decoration', 'none');
                        icon.removeClass('bx-check').addClass('bx-plus');
                    }
                });
            }
            function addTag(tagName) {
                var clean = tagName.trim().replace(/^#/, '');
                if (clean && currentTags.indexOf(clean) === -1) {
                    currentTags.push(clean);
                    renderSelectedTags();
                }
                $('#tags_input').val('').trigger('input');
            }
            $('#tags_input').on('input', function () {
                var query = $(this).val().trim().toLowerCase().replace(/^#/, '');
                var matchCount = 0;
                if (query) {
                    var hasExactMatch = false;
                    $('.quick-tag-btn').each(function () {
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
                        $('#noTagsFoundHint').removeClass('d-none');
                        $('#newTagNameDisplay').text(query);
                    } else {
                        $('#noTagsFoundHint').addClass('d-none');
                    }
                    $('#tagMatchCount').text(matchCount + ' found');
                } else {
                    $('.quick-tag-btn').removeClass('d-none');
                    $('#noTagsFoundHint').addClass('d-none');
                    $('#tagMatchCount').text('');
                }
            });
            $('body').on('click', '.quick-tag-btn', function (e) {
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
            $('#tags_input').on('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    var val = $(this).val();
                    if (val) {
                        val.split(',').forEach(function (t) { addTag(t); });
                    }
                }
            });
            $('body').on('click', '.remove-tag-chip', function () {
                var index = $(this).data('index');
                currentTags.splice(index, 1);
                renderSelectedTags();
            });
            function resetTransactionForm() {
                $('#transactionForm')[0].reset();
                $('#transaction_id').val('');
                $('#transaction_date').val(new Date().toISOString().split('T')[0]);
                currentTags = [];
                renderSelectedTags();
                $('.quick-tag-btn').removeClass('d-none');
                $('#noTagsFoundHint').addClass('d-none');
                $('#tagMatchCount').text('');
                $('#transactionForm .is-invalid').removeClass('is-invalid');
                $('#transactionForm .invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewTransaction').click(function () {
                resetTransactionForm();
                $('#modalTitle').text('Add Transaction');
                $('#transactionModal').modal('show');
            });
            $('#transactionForm').on('submit', function (e) {
                e.preventDefault();
                var pendingTag = $('#tags_input').val();
                if (pendingTag) {
                    pendingTag.split(',').forEach(function (t) { addTag(t); });
                }
                var transactionId = $('#transaction_id').val();
                var baseUrl = '{{ url("finance-transactions") }}';
                var url = transactionId ? baseUrl + '/' + transactionId : baseUrl;
                var amountInput = $('#amount');
                var rawAmount = amountInput.val().replace(/\./g, '');
                amountInput.val(rawAmount);
                var formData = $(this).serialize();
                amountInput.val(formatRupiah(rawAmount));
                if (transactionId) {
                    formData += '&_method=PUT';
                }
                $('#transactionForm .is-invalid').removeClass('is-invalid');
                $('#transactionForm .invalid-feedback').text('').removeClass('d-block');
                $('#saveBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 204) {
                            $('#transactionModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $('#transactionModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Transaction Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        $('#saveBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('#transactionForm [name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            $('#transactionModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Process Request',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editBtn', function () {
                var transactionId = $(this).data('id');
                Swal.fire({
                    title: 'Loading Transaction...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/finance-transactions/' + transactionId + '/edit', function (data) {
                    Swal.close();
                    resetTransactionForm();
                    $('#modalTitle').text('Edit Transaction');
                    $('#transaction_id').val(data.id);
                    $('#transaction_date').val(data.transaction_date);
                    $('#amount').val(formatRupiah(data.amount.toString()));
                    $('#wallet_id').val(data.wallet_id);
                    $('#category_id').val(data.category_id);
                    $('#description').val(data.description);
                    if (data.tags && Array.isArray(data.tags)) {
                        currentTags = data.tags;
                        renderSelectedTags();
                    }
                    $('#transactionModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Transaction',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            function resetTransferForm() {
                $('#transferForm')[0].reset();
                $('#transfer_id').val('');
                $('#transfer_transaction_date').val(new Date().toISOString().split('T')[0]);
                $('#transferForm .is-invalid').removeClass('is-invalid');
                $('#transferForm .invalid-feedback').text('').removeClass('d-block');
            }
            $('#openTransferModal').click(function () {
                resetTransferForm();
                $('#transferModalTitle').text('Add Transfer');
                $('#transferModal').modal('show');
            });
            $('#transferForm').on('submit', function (e) {
                e.preventDefault();
                var transferId = $('#transfer_id').val();
                var url = transferId ? '/finance-transactions/' + transferId + '/transfer' : '{{ route("finance-transactions.transfer.store") }}';
                var amountInput = $('#transfer_amount');
                var rawAmount = amountInput.val().replace(/\./g, '');
                amountInput.val(rawAmount);
                var formData = $(this).serialize();
                amountInput.val(formatRupiah(rawAmount));
                if (transferId) {
                    formData += '&_method=PUT';
                }
                $('#transferForm .is-invalid').removeClass('is-invalid');
                $('#transferForm .invalid-feedback').text('').removeClass('d-block');
                $('#saveTransferBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $('#saveTransferBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 204) {
                            $('#transferModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $('#transferModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Transfer Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        $('#saveTransferBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var errorId = field === 'transaction_date' ? 'transfer_transaction_dateError' : field + 'Error';
                                var input = $('#transferForm [name="' + field + '"]');
                                input.addClass('is-invalid');
                                $('#' + errorId).text(messages[0]).addClass('d-block');
                            });
                        } else {
                            $('#transferModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Process Transfer',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editTransferBtn', function () {
                var transferId = $(this).data('id');
                Swal.fire({
                    title: 'Loading Transfer...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/finance-transactions/' + transferId + '/edit', function (data) {
                    Swal.close();
                    resetTransferForm();
                    $('#transferModalTitle').text('Edit Transfer');
                    $('#transfer_id').val(data.id);
                    $('#transfer_transaction_date').val(data.transaction_date);
                    $('#transfer_amount').val(formatRupiah(data.amount.toString()));
                    $('#from_wallet_id').val(data.from_wallet_id);
                    $('#to_wallet_id').val(data.to_wallet_id);
                    $('#transfer_description').val(data.description);
                    $('#transferModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load Transfer',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            $('body').on('click', '.deleteBtn', function () {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Confirm Transaction Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting Transaction...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/finance-transactions/' + id,
                            data: { _token: '{{ csrf_token() }}' },
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Transaction Deleted Successfully',
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
                                    title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Transaction',
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            $('body').on('click', '.viewTransactionBtn', function () {
                var btn = $(this);
                var type = btn.data('type') ? btn.data('type').toLowerCase() : '';
                var amount = btn.data('amount');
                var badge = $('#view_transaction_type_badge');
                badge.removeClass('bg-label-success bg-label-danger')
                    .addClass(type === 'income' ? 'bg-label-success' : 'bg-label-danger')
                    .text(type.toUpperCase());
                $('#view_transaction_amount').text(amount)
                    .removeClass('text-success text-danger')
                    .addClass(type === 'income' ? 'text-success' : 'text-danger');
                $('#view_transaction_date').text(btn.data('date'));
                $('#view_transaction_wallet').text(btn.data('wallet'));
                $('#view_transaction_category').text(btn.data('category'));
                var tags = btn.data('tags');
                if (tags) {
                    var tagsHtml = tags.split(',').map(function (t) {
                        return '<span class="badge bg-label-primary me-1"><i class="bx bx-tag fs-6"></i> ' + t + '</span>';
                    }).join('');
                    $('#view_transaction_tags').html(tagsHtml);
                } else {
                    $('#view_transaction_tags').html('<span class="text-muted">—</span>');
                }
                $('#view_transaction_desc').text(btn.data('desc'));
                $('#viewTransactionModal').modal('show');
            });
            $('body').on('click', '.viewTransferBtn', function () {
                var btn = $(this);
                $('#view_transfer_amount').text(btn.data('amount'));
                $('#view_transfer_date').text(btn.data('date'));
                $('#view_transfer_from').text(btn.data('from'));
                $('#view_transfer_to').text(btn.data('to'));
                $('#view_transfer_desc').text(btn.data('desc'));
                $('#viewTransferModal').modal('show');
            });
        });
    </script>
@endpush