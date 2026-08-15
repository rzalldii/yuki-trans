@extends('layouts.app')
@section('title', 'Finance Transactions')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row mb-4">
            <div class="col-lg-4 col-md-4 col-sm-12 mb-3 mb-lg-0">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="card-info">
                                <p class="text-heading mb-1">Total Income</p>
                                <div class="d-flex align-items-center mb-1">
                                    <h4 class="card-title text-success mb-0 me-2">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h4>
                                </div>
                                <span title="{{ $currentMonthLabel }}">Period Summary</span>
                            </div>
                            <div class="card-icon">
                                <span class="badge bg-label-success rounded p-2">
                                    <i class="icon-base bx bx-trending-up icon-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12 mb-3 mb-lg-0">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="card-info">
                                <p class="text-heading mb-1">Total Expense</p>
                                <div class="d-flex align-items-center mb-1">
                                    <h4 class="card-title text-danger mb-0 me-2">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h4>
                                </div>
                                <span title="{{ $currentMonthLabel }}">Period Summary</span>
                            </div>
                            <div class="card-icon">
                                <span class="badge bg-label-danger rounded p-2">
                                    <i class="icon-base bx bx-trending-down icon-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12 mb-3 mb-lg-0">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div class="card-info">
                                <p class="text-heading mb-1">Current Balance</p>
                                <div class="d-flex align-items-center mb-1">
                                    <h4 class="card-title mb-0 me-2 {{ $netBalance >= 0 ? 'text-primary' : 'text-warning' }}">
                                        {{ $netBalance < 0 ? '— ' : '' }}Rp {{ number_format(abs($netBalance), 0, ',', '.') }}
                                    </h4>
                                </div>
                                <span>All Time Summary</span>
                            </div>
                            <div class="card-icon">
                                <span class="badge bg-label-{{ $netBalance >= 0 ? 'primary' : 'warning' }} rounded p-2">
                                    <i class="icon-base bx bx-wallet icon-lg"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Finance Transactions</h5>
                <button type="button" class="btn btn-primary" id="createNewTransaction">
                    <i class="bx bx-plus me-1"></i>Add New Transaction
                </button>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="text-body-secondary fw-medium d-inline-flex align-items-center me-1 ps-3">
                        <i class="bx bx-calendar me-1"></i>Date
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <input type="date" id="filterStartDate" class="form-control form-control-sm" value="{{ $startDate }}" title="Start Date">
                        <span class="text-muted">-</span>
                        <input type="date" id="filterEndDate" class="form-control form-control-sm" value="{{ $endDate }}" title="End Date">
                        <button type="button" id="applyDateFilter" class="btn btn-sm btn-outline-primary" title="Apply Date Filter">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                    <div class="vr mx-2 text-muted d-none d-md-block"></div>
                    <span class="text-body-secondary fw-medium d-inline-flex align-items-center me-1">
                        <i class="bx bx-filter-alt me-1"></i>Filters
                    </span>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterWallet" data-filter-label="Wallet">
                            Wallet
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterWallet">
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
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterCategory">
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
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterType">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Types</a></li>
                            @foreach ($filterTypes as $type)
                                <li><a class="dropdown-item filterOption" href="#" data-value="{{ $type }}">{{ ucfirst($type) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @if (auth()->user()->isAdmin())
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterUser" data-filter-label="User">
                                User
                            </button>
                            <ul class="dropdown-menu filterMenu" data-filter-target="filterUser">
                                <li><a class="dropdown-item filterOption" href="#" data-value="">All Users</a></li>
                                @foreach ($transactions->pluck('user.username')->unique()->sort() as $username)
                                    @if ($username)
                                        <li><a class="dropdown-item filterOption" href="#" data-value="{{ $username }}">{{ $username }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <button type="button" id="clearFilters" class="btn btn-sm btn-link text-danger d-none align-items-center gap-1 text-decoration-none ms-1">
                        <i class="bx bx-x-circle"></i>
                        <span>Clear all</span>
                    </button>
                </div>
                <div id="activeFilterChips" class="d-flex flex-wrap gap-2 mb-1"></div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="transactionTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Wallet</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                @if (auth()->user()->isAdmin())
                                    <th>User</th>
                                @endif
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->transaction_date->format('d M Y') }}</td>
                                    <td>{{ $transaction->wallet->name }}</td>
                                    <td data-category="{{ $transaction->category->name }}">
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="fw-medium">{{ $transaction->category->name }}</span>
                                        </div>
                                    </td>
                                    <td data-type="{{ $transaction->category->type }}">
                                        @if ($transaction->category->type === 'income')
                                            <span class="badge bg-label-success d-inline-flex align-items-center gap-1">Income</span>
                                        @else
                                            <span class="badge bg-label-danger d-inline-flex align-items-center gap-1">Expense</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($transaction->category->type === 'income')
                                            <span class="text-success fw-medium">+ Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                        @else
                                            <span class="text-danger fw-medium">- Rp {{ number_format($transaction->amount, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    @if (auth()->user()->isAdmin())
                                        <td>{{ $transaction->user->username ?? 'Unknown' }}</td>
                                    @endif
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-info viewTransactionBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="View Details" aria-label="View Details" data-date="{{ $transaction->transaction_date->format('d M Y') }}" data-wallet="{{ $transaction->wallet->name }}" data-category="{{ $transaction->category->name }} ({{ ucfirst($transaction->category->type) }})" data-type="{{ $transaction->category->type }}" data-amount="{{ $transaction->category->type === 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}" data-desc="{{ $transaction->description ?? '-' }}">
                                                <i class="bx bx-show"></i>
                                            </button>
                                            @php
                                                $canModify = auth()->user()->isAdmin() || $transaction->user_id === auth()->id();
                                            @endphp
                                            @if ($canModify)
                                                <button type="button" class="btn btn-sm btn-outline-warning editBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Edit Transaction" aria-label="Edit Transaction" data-id="{{ $transaction->id }}">
                                                    <i class="bx bx-edit-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger deleteBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Delete Transaction" aria-label="Delete Transaction" data-id="{{ $transaction->id }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
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
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="transactionForm">
                    @csrf
                    <input type="hidden" name="transaction_id" id="transaction_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Transaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="transaction_date">Date <span class="text-danger">*</span></label>
                                <input type="date" id="transaction_date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback" id="transaction_dateError"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="wallet_id">Wallet <span class="text-danger">*</span></label>
                                <select id="wallet_id" name="wallet_id" class="form-select">
                                    <option value="" selected disabled>Select Wallet</option>
                                    @foreach ($wallets as $wallet)
                                        @php
                                            $walletCurrentBalance = $wallet->initial_balance + ($wallet->income_sum ?? 0) - ($wallet->expense_sum ?? 0) - ($wallet->transferred_out_sum ?? 0) + ($wallet->transferred_in_sum ?? 0);
                                        @endphp
                                        <option value="{{ $wallet->id }}">
                                            {{ $wallet->name }} (Rp {{ number_format($walletCurrentBalance, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="wallet_idError"></div>
                            </div>
                            <div class="col-12 mb-3">
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
                            <div class="col-12 mb-3">
                                <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                                <input type="text" id="amount" name="amount" class="form-control text-end" placeholder="e.g., 150.000">
                                <div class="invalid-feedback" id="amountError"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="description">Description (Optional)</label>
                                <textarea id="description" name="description" class="form-control" rows="3" placeholder="e.g., Trip Bali"></textarea>
                                <div class="invalid-feedback" id="descriptionError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" id="saveBtn" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewTransactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="ps-0" style="width: 130px;">Date</th>
                                <td id="view_transaction_date"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Wallet</th>
                                <td id="view_transaction_wallet"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Category</th>
                                <td id="view_transaction_category"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Amount</th>
                                <td id="view_transaction_amount" class="fw-medium"></td>
                            </tr>
                            <tr>
                                <th class="ps-0 align-top">Description</th>
                                <td id="view_transaction_description" style="white-space: pre-wrap;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        $(document).ready(function () {
            $('#applyDateFilter').on('click', function() {
                var start = $('#filterStartDate').val();
                var end = $('#filterEndDate').val();
                if (start && end) {
                    if (start !== "{{ $startDate }}" || end !== "{{ $endDate }}") {
                        window.location.href = '{{ url()->current() }}?start_date=' + start + '&end_date=' + end;
                    }
                }
            });
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.extend(true, DataTable.ext.classes, {
                search: { input: 'form-control' },
                length: { select: 'form-select' }
            });
            var filterState = {
                filterWallet: '',
                filterCategory: '',
                filterType: '',
                @if (auth()->user()->isAdmin())
                    filterUser: ''
                @endif
            };
            var filterLabels = {
                filterWallet: 'Wallet',
                filterCategory: 'Category',
                filterType: 'Type',
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
                    searchPlaceholder: "Search Transaction",
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
                var walletCol = data[1] || '';
                var categoryCol = $(tr).find('td:eq(2)').attr('data-category') || '';
                var typeCol = $(tr).find('td:eq(3)').attr('data-type') || '';
                @if (auth()->user()->isAdmin())
                    var userCol = data[5] || '';
                @endif
                if (filterState.filterWallet && walletCol !== filterState.filterWallet) return false;
                if (filterState.filterCategory && categoryCol !== filterState.filterCategory) return false;
                if (filterState.filterType && typeCol !== filterState.filterType) return false;
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
                        chipsHtml += '<span class="badge rounded-pill bg-primary-subtle text-primary d-inline-flex align-items-center gap-1 py-2 px-3">' +
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
            function formatRupiah(angka) {
                if (!angka) return '';
                var number_string = angka.toString().replace(/[^,\d]/g, ''),
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
            $('#amount').on('input', function () {
                $(this).val(formatRupiah($(this).val()));
            });
            function resetForm() {
                $('#transactionForm')[0].reset();
                $('#transaction_id').val('');
                $('#transaction_date').val(new Date().toISOString().split('T')[0]);
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewTransaction').click(function () {
                resetForm();
                $('#modalTitle').text('Add New Transaction');
                $('#transactionModal').modal('show');
            });
            $('#transactionForm').on('submit', function (e) {
                e.preventDefault();
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
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
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
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                if (field === 'amount') {
                                    $('#amountError').text(messages[0]).addClass('d-block');
                                } else {
                                    $('#' + field + 'Error').text(messages[0]).addClass('d-block');
                                }
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
                    resetForm();
                    $('#modalTitle').text('Edit Transaction');
                    $('#transaction_id').val(data.id);
                    $('#transaction_date').val(data.transaction_date.split('T')[0]);
                    $('#wallet_id').val(data.wallet_id);
                    $('#category_id').val(data.category_id);
                    $('#amount').val(formatRupiah(data.amount));
                    $('#description').val(data.description);
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
            $('body').on('click', '.deleteBtn', function () {
                var transactionId = $(this).data('id');
                Swal.fire({
                    title: 'Confirm Transaction Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
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
                            url: '/finance-transactions/' + transactionId,
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
                $('#view_transaction_date').text($(this).data('date'));
                $('#view_transaction_wallet').text($(this).data('wallet'));
                $('#view_transaction_category').text($(this).data('category'));
                var type = $(this).data('type');
                $('#view_transaction_amount')
                    .text($(this).data('amount'))
                    .removeClass('text-success text-danger')
                    .addClass(type === 'income' ? 'text-success' : 'text-danger');
                $('#view_transaction_description').text($(this).data('desc'));
                $('#viewTransactionModal').modal('show');
            });
        });
    </script>
@endpush