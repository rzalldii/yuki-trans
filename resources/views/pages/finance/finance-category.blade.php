@extends('layouts.app')
@section('title', 'Finance Categories')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Finance Categories</h5>
                <button type="button" class="btn btn-primary" id="createNewCategory">
                    <i class="bx bx-plus me-1"></i>Add New Category
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="categoryTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($categories as $category)
                                <tr>
                                    <td>
                                        <span class="fw-medium text-heading">{{ $category->name }}</span>
                                    </td>
                                    <td>
                                        @if ($category->type === 'income')
                                            <span class="badge bg-label-success d-inline-flex align-items-center gap-1">
                                                <i class="bx bx-trending-up"></i> Income
                                            </span>
                                        @else
                                            <span class="badge bg-label-danger d-inline-flex align-items-center gap-1">
                                                <i class="bx bx-trending-down"></i> Expense
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-warning editBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Edit Category" aria-label="Edit Category" data-id="{{ $category->id }}">
                                                <i class="bx bx-edit-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger deleteBtn" data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top" title="Delete Category" aria-label="Delete Category" data-id="{{ $category->id }}">
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
    <div class="modal fade" id="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="categoryForm">
                    @csrf
                    <input type="hidden" name="category_id" id="category_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="e.g., Bills, Transportation, Salary, etc.">
                                <div class="invalid-feedback" id="nameError"></div>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label d-block">Type <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="type" id="type_income" value="income" autocomplete="off">
                                        <label class="btn btn-outline-success w-100 d-flex align-items-center justify-content-center gap-1 py-2" for="type_income">
                                            <i class="bx bx-trending-up fs-5"></i> Income
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="type" id="type_expense" value="expense" autocomplete="off">
                                        <label class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-1 py-2" for="type_expense">
                                            <i class="bx bx-trending-down fs-5"></i> Expense
                                        </label>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="typeError"></div>
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
            var table = $('#categoryTable').DataTable({
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [2] }
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No categories available.",
                    zeroRecords: "No matching categories found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search Category",
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
            function resetForm() {
                $('#categoryForm')[0].reset();
                $('input[name="type"]').prop('checked', false);
                $('#category_id').val('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewCategory').click(function () {
                resetForm();
                $('#modalTitle').text('Add Category');
                $('#categoryModal').modal('show');
            });
            $('#categoryForm').on('submit', function (e) {
                e.preventDefault();
                var categoryId = $('#category_id').val();
                var baseUrl = '{{ url("finance-categories") }}';
                var url = categoryId ? baseUrl + '/' + categoryId : baseUrl;
                var formData = $(this).serialize();
                if (categoryId) {
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
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        $('#saveBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                $('[name="' + field + '"]').addClass('is-invalid');
                                $('#' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            $('#categoryModal').modal('hide');
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
                    resetForm();
                    $('#modalTitle').text('Edit Category');
                    $('#category_id').val(data.id);
                    $('#name').val(data.name);
                    $('input[name="type"][value="' + data.type + '"]').prop('checked', true);
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
            $('body').on('click', '.deleteBtn', function () {
                var categoryId = $(this).data('id');
                Swal.fire({
                    title: 'Confirm Category Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
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
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Category Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function () {
                                    location.reload();
                                });
                            },
                            error: function (xhr) {
                                Swal.close();
                                let msg = xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete Category';
                                if (xhr.status === 422) {
                                    msg = 'Cannot Delete Category With Existing Transactions';
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