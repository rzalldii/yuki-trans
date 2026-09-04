@extends('layouts.app')
@section('title', 'Users')
@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Users</li>
@endsection
@push('style')
    <link href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.css') }}" rel="stylesheet">
@endpush
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User List</h5>
                <button type="button" class="btn btn-primary" id="createNewUser" data-entity="user" data-action="create">
                    <i class="bx bx-plus me-1" aria-hidden="true"></i>Add User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="userTable">
                        <caption class="visually-hidden">User List</caption>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact Info</th>
                                <th>Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @foreach ($users as $user)
                                <tr id="user-row-{{ $user->id }}" data-id="{{ $user->id }}" data-entity="user" data-entity-id="{{ $user->id }}">
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div>
                                                <span class="fw-bold">{{ $user->full_name ?? $user->username }}</span>
                                                @if ($user->id === auth()->id())
                                                    <span class="badge bg-label-primary ms-1">You</span>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ '@' . $user->username }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            @if($user->email)
                                                <div class="d-flex align-items-center text-body-secondary small" title="{{ $user->email }}">
                                                    <i class="bx bx-envelope text-muted me-1" aria-hidden="true"></i>
                                                    <span class="text-truncate">{{ $user->email }}</span>
                                                </div>
                                            @endif
                                            @if($user->phone_number)
                                                <div class="d-flex align-items-center text-body-secondary small" title="{{ $user->formatted_phone_number }}">
                                                    <i class="bx bx-phone text-muted me-1" aria-hidden="true"></i>
                                                    <span class="text-truncate">{{ $user->formatted_phone_number }}</span>
                                                </div>
                                            @endif
                                            @if(!$user->email && !$user->phone_number)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($user->role === 'admin')
                                            @if ($user->isPrimary())
                                                <span class="text-truncate d-flex align-items-center text-heading">
                                                    <i class="bx bx-crown text-warning me-2" aria-hidden="true"></i>Primary Admin
                                                </span>
                                            @else
                                                <span class="text-truncate d-flex align-items-center text-heading">
                                                    <i class="bx bx-desktop text-danger me-2" aria-hidden="true"></i>Admin
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-truncate d-flex align-items-center text-heading">
                                                <i class="bx bx-user text-success me-2" aria-hidden="true"></i>User
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $canEdit = auth()->user()->canEdit($user);
                                            $canDelete = auth()->user()->canDelete($user);
                                            $isSelf = auth()->user()->isSelf($user);
                                        @endphp
                                        @if (!$isSelf)
                                            <div class="d-flex gap-1 justify-content-center">
                                                @if (!$user->isPrimary())
                                                    <a href="{{ route('users.profile', $user) }}" class="btn btn-sm btn-icon btn-outline-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View" aria-label="View" data-entity="user" data-action="view">
                                                        <i class="bx bx-show" aria-hidden="true"></i>
                                                    </a>
                                                @endif
                                                @if ($canEdit)
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-warning editBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-id="{{ $user->id }}" aria-label="Edit" data-entity="user" data-action="edit">
                                                        <i class="bx bx-edit-alt" aria-hidden="true"></i>
                                                    </button>
                                                    @if ($canDelete)
                                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger deleteBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-id="{{ $user->id }}" aria-label="Delete" data-entity="user" data-action="delete">
                                                            <i class="bx bx-trash" aria-hidden="true"></i>
                                                        </button>
                                                    @endif
                                                @else
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" aria-label="Locked" disabled>
                                                        <i class="bx bx-lock-alt" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="userModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="userForm" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="user_id" id="user_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="e.g., johndoe123" autocomplete="username" required aria-describedby="usernameError">
                            <div class="invalid-feedback" id="usernameError"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3 form-password-toggle">
                            <label class="form-label" id="passwordLabel" for="password">Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" autocomplete="new-password" minlength="8" required aria-describedby="passwordError passwordHelp">
                                <button type="button" class="input-group-text cursor-pointer" aria-label="Show password">
                                    <i class="bx bx-hide" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordError"></div>
                            <div class="form-text" id="passwordHelp">Min. 8 characters, letters & numbers.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-3">
                            <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                            <select name="role" id="role" class="form-select" required aria-describedby="roleError">
                                <option value="" selected disabled>Select Role</option>
                                @if (auth()->user()->isPrimary())
                                    <option value="admin">Admin</option>
                                @endif
                                <option value="user">User</option>
                            </select>
                            <div class="invalid-feedback" id="roleError"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary" data-entity="user" data-action="save">
                        <i class="bx bx-save me-1" aria-hidden="true"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ asset('vendor/libs/datatables/dataTables.js') }}"></script>
    <script src="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.js') }}"></script>
    <script nonce="{{ $cspNonce }}">
        $(document).ready(function () {
            $('#username').on('input', function () {
                this.value = this.value.toLowerCase().replace(/[^a-z0-9_.]/g, '');
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
            var table = $('#userTable').DataTable({
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [3] }
                ],
                pageLength: 10,
                language: {
                    emptyTable: "No users available.",
                    zeroRecords: "No matching users found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search User",
                    paginate: {
                        first: '<i class="bx bx-chevrons-left" aria-hidden="true"></i>',
                        previous: '<i class="bx bx-chevron-left" aria-hidden="true"></i>',
                        next: '<i class="bx bx-chevron-right" aria-hidden="true"></i>',
                        last: '<i class="bx bx-chevrons-right" aria-hidden="true"></i>'
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
                $('#userForm')[0].reset();
                $('#user_id').val('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
            }
            $('#createNewUser').click(function () {
                resetForm();
                $('#modalTitle').text('Add User');
                $('#passwordLabel').html('Password <span class="text-danger">*</span>');
                $('#password').attr('placeholder', '••••••••').prop('required', true);
                $('#userModal').modal('show');
            });
            $('#userForm').on('submit', function (e) {
                e.preventDefault();
                var userId = $('#user_id').val();
                var baseUrl = '{{ url("users") }}';
                var url = userId ? baseUrl + '/' + userId : baseUrl;
                var formData = $(this).serialize();
                if (userId) {
                    formData += '&_method=PUT';
                }
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                var $modal = $('#userModal');
                var $submitBtn = $('#saveBtn');
                var $closeBtns = $modal.find('.btn-close, [data-bs-dismiss="modal"]');
                $submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1" aria-hidden="true"></i>Saving...').prop('disabled', true);
                $closeBtns.prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $submitBtn.html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 204) {
                            $modal.modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $modal.modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'User Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        $submitBtn.html('<i class="bx bx-save me-1" aria-hidden="true"></i>Save').prop('disabled', false);
                        $closeBtns.prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                $('[name="' + field + '"]').addClass('is-invalid');
                                $('#' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            $modal.modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Save User',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.editBtn', function () {
                var userId = $(this).data('id');
                Swal.fire({
                    title: 'Loading User...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.get('/users/' + userId + '/edit', function (data) {
                    Swal.close();
                    resetForm();
                    $('#modalTitle').text('Edit User');
                    $('#user_id').val(data.id);
                    $('#username').val(data.username);
                    $('#role').val(data.role);
                    $('#passwordLabel').text('New Password (Optional)');
                    $('#password').attr('placeholder', 'Leave blank to retain current password').prop('required', false);
                    $('#userModal').modal('show');
                }).fail(function () {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load User',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            $('body').on('click', '.deleteBtn', function () {
                var userId = $(this).data('id');
                Swal.fire({
                    title: 'Delete User?',
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
                            title: 'Deleting User...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: function () {
                                Swal.showLoading();
                            }
                        });
                        $.ajax({
                            type: 'DELETE',
                            url: '/users/' + userId,
                            success: function () {
                                Swal.close();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'User Deleted Successfully',
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
                                    title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Delete User',
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