@extends('layouts.app')
@section('title', 'Finance Wallets')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Finance Wallets</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" id="openTransferModal">
                        <i class="bx bx-transfer me-1"></i>Transfer Funds
                    </button>
                    <button type="button" class="btn btn-primary" id="createNewWallet">
                        <i class="bx bx-plus me-1"></i>Add New Wallet
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            @forelse ($wallets as $wallet)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 text-white" style="background: linear-gradient(135deg, #696cff 0%, #4a4cbf 100%) !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded-circle bg-white text-primary"><i class="bx bx-wallet"></i></span>
                                    </div>
                                    <h5 class="card-title text-white mb-0">{{ $wallet->name }}</h5>
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0 text-white" type="button" id="walletMenu_{{ $wallet->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="walletMenu_{{ $wallet->id }}">
                                        <a class="dropdown-item text-warning editBtn" href="javascript:void(0);" data-id="{{ $wallet->id }}"><i class="bx bx-edit-alt me-1"></i> Edit</a>
                                        <a class="dropdown-item text-danger deleteBtn" href="javascript:void(0);" data-id="{{ $wallet->id }}"><i class="bx bx-trash me-1"></i> Delete</a>
                                    </div>
                                </div>
                            </div>
                            <p class="mb-1 text-white-50">Current Balance</p>
                            @php
                                $currentBalance = $wallet->initial_balance + ($wallet->income_sum ?? 0) - ($wallet->expense_sum ?? 0) - ($wallet->transferred_out_sum ?? 0) + ($wallet->transferred_in_sum ?? 0);
                            @endphp
                            <h4 class="text-white mb-0">Rp {{ number_format($currentBalance, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card text-center py-5">
                        <div class="card-body">
                            <h5 class="mb-2">No wallets found.</h5>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
    <div class="container-xxl flex-grow-1 container-p-y pt-0">
        <div class="card">
            <div class="card-body pt-3">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="transferTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From Wallet</th>
                                <th>To Wallet</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($transfers ?? [] as $transfer)
                                <tr>
                                    <td>{{ $transfer->transfer_date->format('d M Y') }}</td>
                                    <td>
                                        @if ($transfer->fromWallet)
                                            {{ $transfer->fromWallet->name }}
                                            @if ($transfer->fromWallet->trashed())
                                                <span class="badge bg-label-danger ms-1" style="font-size: 0.65rem;">Deleted</span>
                                            @endif
                                        @else
                                            <span class="text-danger fst-italic">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($transfer->toWallet)
                                            {{ $transfer->toWallet->name }}
                                            @if ($transfer->toWallet->trashed())
                                                <span class="badge bg-label-danger ms-1" style="font-size: 0.65rem;">Deleted</span>
                                            @endif
                                        @else
                                            <span class="text-danger fst-italic">Unknown</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-info fw-medium">
                                        Rp {{ number_format($transfer->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-info viewTransferBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="View Transfer" aria-label="View Transfer" data-date="{{ $transfer->transfer_date->format('d M Y') }}" data-from="{{ optional($transfer->fromWallet)->name ?? 'Unknown' }}" data-to="{{ optional($transfer->toWallet)->name ?? 'Unknown' }}" data-amount="Rp {{ number_format($transfer->amount, 0, ',', '.') }}" data-desc="{{ $transfer->description ?? '-' }}">
                                                <i class="bx bx-show"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning editTransferBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Edit Transfer" aria-label="Edit Transfer" data-id="{{ $transfer->id }}">
                                                <i class="bx bx-edit-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger deleteTransferBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Delete Transfer" aria-label="Delete Transfer" data-id="{{ $transfer->id }}">
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
    <div class="modal fade" id="walletModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="walletForm">
                    @csrf
                    <input type="hidden" name="wallet_id" id="wallet_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Wallet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="e.g., Main Cash, Operational Cash">
                                <div class="invalid-feedback" id="nameError"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="initial_balance">Initial Balance <span class="text-danger">*</span></label>
                                <input type="text" id="initial_balance" name="initial_balance" class="form-control text-end" placeholder="e.g., 10.000.000">
                                <div class="invalid-feedback" id="initial_balanceError"></div>
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
    <div class="modal fade" id="transferModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <form id="transferForm">
                    @csrf
                    <input type="hidden" name="transfer_id" id="transfer_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transferModalTitle">Transfer Funds</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="transfer_date">Date <span class="text-danger">*</span></label>
                                <input type="date" id="transfer_date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback" id="transfer_dateError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="transfer_amount">Amount <span class="text-danger">*</span></label>
                                <input type="text" id="transfer_amount" name="amount" class="form-control text-end" placeholder="e.g., 5.000.000">
                                <div class="invalid-feedback" id="transfer_amountError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="from_wallet_id">From Wallet <span class="text-danger">*</span></label>
                                <select id="from_wallet_id" name="from_wallet_id" class="form-select">
                                    <option value="" selected disabled>Select Wallet</option>
                                    @foreach ($wallets as $wallet)
                                        @php
                                            $curr = $wallet->initial_balance + ($wallet->income_sum ?? 0) - ($wallet->expense_sum ?? 0) - ($wallet->transferred_out_sum ?? 0) + ($wallet->transferred_in_sum ?? 0);
                                        @endphp
                                        <option value="{{ $wallet->id }}">{{ $wallet->name }} (Rp {{ number_format($curr, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="from_wallet_idError"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="to_wallet_id">To Wallet <span class="text-danger">*</span></label>
                                <select id="to_wallet_id" name="to_wallet_id" class="form-select">
                                    <option value="" selected disabled>Select Wallet</option>
                                    @foreach ($wallets as $wallet)
                                        @php
                                            $curr = $wallet->initial_balance + ($wallet->income_sum ?? 0) - ($wallet->expense_sum ?? 0) - ($wallet->transferred_out_sum ?? 0) + ($wallet->transferred_in_sum ?? 0);
                                        @endphp
                                        <option value="{{ $wallet->id }}">{{ $wallet->name }} (Rp {{ number_format($curr, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="to_wallet_idError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="transfer_description">Description (Optional)</label>
                                <textarea id="transfer_description" name="description" class="form-control" rows="2" placeholder="e.g., Replenish cash"></textarea>
                                <div class="invalid-feedback" id="transfer_descriptionError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" id="saveTransferBtn" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="viewTransferModal" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transfer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <th class="ps-0" style="width: 130px;">Date</th>
                                <td id="view_transfer_date"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">From Wallet</th>
                                <td id="view_from_wallet"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">To Wallet</th>
                                <td id="view_to_wallet"></td>
                            </tr>
                            <tr>
                                <th class="ps-0">Amount</th>
                                <td id="view_transfer_amount" class="text-info fw-medium"></td>
                            </tr>
                            <tr>
                                <th class="ps-0 align-top">Description</th>
                                <td id="view_transfer_description" style="white-space: pre-wrap;"></td>
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
            $.extend(true, DataTable.ext.classes, {
                search: { input: 'form-control' },
                length: { select: 'form-select' }
            });
            var table = $('#transferTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [4] }
                ],
                language: {
                    emptyTable: "No transfers recorded yet.",
                    zeroRecords: "No matching transfers found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search Transfer",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });

            table.on('draw', function () {
                initTooltips();
            });
            function initTooltips() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
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
            $('#initial_balance, #transfer_amount').on('input', function () {
                $(this).val(formatRupiah($(this).val()));
            });
            function resetForm() {
                $('#walletForm')[0].reset();
                $('#wallet_id').val('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewWallet').click(function () {
                resetForm();
                $('#modalTitle').text('Add New Wallet');
                $('#walletModal').modal('show');
            });

            function resetTransferForm() {
                $('#transferForm')[0].reset();
                $('#transfer_id').val('');
                $('#transfer_date').val(new Date().toISOString().split('T')[0]);
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
            }
            $('#openTransferModal').click(function () {
                resetTransferForm();
                $('#transferModalTitle').text('Transfer Funds');
                $('#transferModal').modal('show');
            });
            $('#walletForm').on('submit', function (e) {
                e.preventDefault();
                var walletId = $('#wallet_id').val();
                var baseUrl = '{{ url("finance-wallets") }}';
                var url = walletId ? baseUrl + '/' + walletId : baseUrl;
                var balanceInput = $('#initial_balance');
                var rawBalance = balanceInput.val().replace(/\./g, '');
                balanceInput.val(rawBalance);
                var formData = $(this).serialize();
                balanceInput.val(formatRupiah(rawBalance));
                if (walletId) {
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
                                if (field === 'initial_balance') {
                                    $('#initial_balanceError').text(messages[0]).addClass('d-block');
                                } else {
                                    $('#' + field + 'Error').text(messages[0]).addClass('d-block');
                                }
                            });
                        } else {
                            $('#walletModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Unable to Process Request',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editBtn', function () {
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
                    resetForm();
                    $('#modalTitle').text('Edit Wallet');
                    $('#wallet_id').val(data.id);
                    $('#name').val(data.name);
                    $('#initial_balance').val(formatRupiah(data.initial_balance));
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
            $('body').on('click', '.deleteBtn', function () {
                var walletId = $(this).data('id');
                Swal.fire({
                    title: 'Confirm Wallet Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
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
                                let msg = xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Wallet';
                                if (xhr.status === 422) {
                                    msg = 'Cannot Delete Wallet With Existing Transactions or Transfers';
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: msg,
                                    confirmButtonColor: '#696cff'
                                });
                            }
                        });
                    }
                });
            });
            $('#transferForm').on('submit', function (e) {
                e.preventDefault();
                var transferId = $('#transfer_id').val();
                var baseUrl = '{{ url("finance-transfers") }}';
                var url = transferId ? baseUrl + '/' + transferId : baseUrl;
                var amountInput = $('#transfer_amount');
                var rawAmount = amountInput.val().replace(/\./g, '');
                amountInput.val(rawAmount);
                var formData = $(this).serialize();
                amountInput.val(formatRupiah(rawAmount));
                if (transferId) {
                    formData += '&_method=PUT';
                }
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
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
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                var input = $('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                if (field === 'amount') {
                                    $('#transfer_amountError').text(messages[0]).addClass('d-block');
                                } else {
                                    $('#' + field + 'Error').text(messages[0]).addClass('d-block');
                                }
                            });
                        } else {
                            $('#transferModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Unable to Process Transfer',
                                text: xhr.responseJSON?.message || '',
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
                $.get('/finance-transfers/' + transferId + '/edit', function (data) {
                    Swal.close();
                    resetTransferForm();
                    $('#transferModalTitle').text('Edit Transfer');
                    $('#transfer_id').val(data.id);
                    $('#transfer_date').val(data.transfer_date);
                    $('#from_wallet_id').val(data.from_wallet_id);
                    $('#to_wallet_id').val(data.to_wallet_id);
                    $('#transfer_amount').val(formatRupiah(data.amount));
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
            $('body').on('click', '.viewTransferBtn', function () {
                $('#view_transfer_date').text($(this).data('date'));
                $('#view_from_wallet').text($(this).data('from'));
                $('#view_to_wallet').text($(this).data('to'));
                $('#view_transfer_amount').text($(this).data('amount'));
                $('#view_transfer_description').text($(this).data('desc'));
                $('#viewTransferModal').modal('show');
            });
            $('body').on('click', '.deleteTransferBtn', function () {
                var transferId = $(this).data('id');
                Swal.fire({
                    title: 'Confirm Deleting Transfer',
                    text: 'This will revert the balances for both wallets.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting Transfer...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/finance-transfers/' + transferId,
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Transfer Deleted',
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
                                    title: 'Unable to Delete Transfer',
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