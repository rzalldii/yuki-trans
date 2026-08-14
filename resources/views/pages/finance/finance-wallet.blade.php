@extends('layouts.app')
@section('title', 'Finance Wallets')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Finance Wallets</h5>
                <button type="button" class="btn btn-primary" id="createNewWallet">
                    <i class="bx bx-plus me-1"></i>Add New Wallet
                </button>
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
                                $currentBalance = $wallet->initial_balance + ($wallet->income_sum ?? 0) - ($wallet->expense_sum ?? 0);
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
@endsection
@push('script')
    <script>
        $(document).ready(function () {
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
            $('#initial_balance').on('input', function () {
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
                                    msg = 'Cannot Delete Wallet With Existing Transactions';
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
        });
    </script>
@endpush
