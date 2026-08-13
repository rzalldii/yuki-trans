@extends('layouts.app')
@section('title', 'Finance Wallets')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Finance Wallets</h5>
                <button type="button" class="btn btn-primary" id="createNewWallet">
                    <i class="bx bx-plus me-1"></i>Add New Wallet
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="walletTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-end">Initial Balance</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($wallets as $wallet)
                                <tr>
                                    <td>
                                        <span class="fw-medium">{{ $wallet->name }}</span>
                                    </td>
                                    <td class="text-end">Rp {{ number_format($wallet->initial_balance, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-warning editBtn"
                                                data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top"
                                                title="Edit Wallet" aria-label="Edit Wallet"
                                                data-id="{{ $wallet->id }}">
                                                <i class="bx bx-edit-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger deleteBtn"
                                                data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top"
                                                title="Delete Wallet" aria-label="Delete Wallet"
                                                data-id="{{ $wallet->id }}">
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
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="e.g., Main Cash, Operational Cash">
                                <div class="invalid-feedback" id="nameError"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label" for="initial_balance">Initial Balance <span class="text-danger">*</span></label>
                                <input type="text" id="initial_balance" name="initial_balance" class="form-control"
                                    placeholder="e.g., 10.000.000">
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
            $.extend(true, DataTable.ext.classes, {
                search: { input: 'form-control' },
                length: { select: 'form-select' }
            });
            $('#walletTable').DataTable({
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [2] }
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No wallets available.",
                    zeroRecords: "No matching wallets found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search Wallet",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
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
                            error: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Unable to Delete Wallet',
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
