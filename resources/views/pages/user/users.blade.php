@extends('layouts.app')
@section('title', 'Users')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User List</h5>
                <button type="button" class="btn btn-primary" id="createNewUser">
                    <i class="bx bx-plus me-1"></i>Add New User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="userTable">
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
                                <tr id="user-row-{{ $user->id }}" data-id="{{ $user->id }}">
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
                                        <div class="d-flex flex-column">
                                            @if($user->email)
                                                <span class="text-truncate" style="max-width: 200px;" title="{{ $user->email }}">
                                                    <i class="bx bx-envelope text-muted me-1"></i><small>{{ $user->email }}</small>
                                                </span>
                                            @endif
                                            @if($user->phone_number)
                                                <span class="text-truncate" style="max-width: 200px;" title="{{ $user->formatted_phone_number }}">
                                                    <i class="bx bx-phone text-muted me-1"></i><small>{{ $user->formatted_phone_number }}</small>
                                                </span>
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
                                                    <i class="bx bx-crown text-warning me-2"></i>Primary Admin
                                                </span>
                                            @else
                                                <span class="text-truncate d-flex align-items-center text-heading">
                                                    <i class="bx bx-desktop text-danger me-2"></i>Admin
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-truncate d-flex align-items-center text-heading">
                                                <i class="bx bx-user text-success me-2"></i>User
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
                                                    <a href="{{ route('users.profile', $user) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View" aria-label="View">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                @endif
                                                @if ($canEdit)
                                                    <button type="button" class="btn btn-sm btn-outline-warning editBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit" data-id="{{ $user->id }}">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </button>
                                                    @if ($canDelete)
                                                        <button type="button" class="btn btn-sm btn-outline-danger deleteBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" aria-label="Delete" data-id="{{ $user->id }}">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    @endif
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="bx bx-lock-alt"></i>
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
    <div class="modal fade" id="userModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="userForm">
                    @csrf
                    <input type="hidden" name="user_id" id="user_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control" placeholder="e.g., johndoe123" oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9_.]/g, '')">
                                <div class="invalid-feedback" id="usernameError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3 form-password-toggle">
                                <label class="form-label" id="passwordLabel">Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••">
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback" id="passwordError"></div>
                                <div class="form-text" id="passwordHelp">
                                    Min. 8 characters, letters & numbers.
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" id="role" class="form-select">
                                    <option value="" selected disabled>Select Role</option>
                                    <option value="admin" id="adminOption" @if (!auth()->user()->isPrimary()) style="display:none;" @endif>
                                        Admin
                                    </option>
                                    <option value="user">User</option>
                                </select>
                                <div class="invalid-feedback" id="roleError"></div>
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
            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html();
            }
            function generateUserCells(user) {
                var userHtml = '<div class="d-flex flex-column">' +
                    '<div><span class="fw-bold">' + (user.full_name ? escapeHtml(user.full_name) : escapeHtml(user.username)) + '</span>' +
                    (user.id === {{ auth()->id() }} ? ' <span class="badge bg-label-primary ms-1">You</span>' : '') +
                    '</div>' +
                    '<small class="text-muted">@' + escapeHtml(user.username) + '</small>' +
                    '</div>';
                var contactHtml = '<div class="d-flex flex-column">';
                if (user.email) {
                    contactHtml += '<span class="text-truncate" style="max-width: 200px;" title="' + escapeHtml(user.email) + '">' +
                        '<i class="bx bx-envelope text-muted me-1"></i><small>' + escapeHtml(user.email) + '</small>' +
                        '</span>';
                }
                if (user.formatted_phone_number) {
                    contactHtml += '<span class="text-truncate" style="max-width: 200px;" title="' + escapeHtml(user.formatted_phone_number) + '">' +
                        '<i class="bx bx-phone text-muted me-1"></i><small>' + escapeHtml(user.formatted_phone_number) + '</small>' +
                        '</span>';
                }
                if (!user.email && !user.formatted_phone_number) {
                    contactHtml += '<span class="text-muted">—</span>';
                }
                contactHtml += '</div>';
                var roleHtml = '';
                if (user.role === 'admin') {
                    if (user.is_primary) {
                        roleHtml = '<span class="text-truncate d-flex align-items-center text-heading"><i class="bx bx-crown text-warning me-2"></i>Primary Admin</span>';
                    } else {
                        roleHtml = '<span class="text-truncate d-flex align-items-center text-heading"><i class="bx bx-desktop text-danger me-2"></i>Admin</span>';
                    }
                } else {
                    roleHtml = '<span class="text-truncate d-flex align-items-center text-heading"><i class="bx bx-user text-success me-2"></i>User</span>';
                }
                var actionsHtml = '';
                if (user.id !== {{ auth()->id() }}) {
                    actionsHtml = '<div class="d-flex gap-1 justify-content-center">';
                    if (!user.is_primary) {
                        actionsHtml += '<a href="' + user.profile_url + '" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" data-bs-placement="top" title="View" aria-label="View"><i class="bx bx-show"></i></a> ';
                    }
                    if (user.can_edit) {
                        actionsHtml += '<button type="button" class="btn btn-sm btn-outline-warning editBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" aria-label="Edit" data-id="' + user.id + '"><i class="bx bx-edit-alt"></i></button> ';
                        if (user.can_delete) {
                            actionsHtml += '<button type="button" class="btn btn-sm btn-outline-danger deleteBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" aria-label="Delete" data-id="' + user.id + '"><i class="bx bx-trash"></i></button>';
                        }
                    } else {
                        actionsHtml += '<button type="button" class="btn btn-sm btn-outline-secondary" disabled><i class="bx bx-lock-alt"></i></button>';
                    }
                    actionsHtml += '</div>';
                } else {
                    actionsHtml = '—';
                }
                return [userHtml, contactHtml, roleHtml, actionsHtml];
            }
            function resetForm() {
                $('#userForm')[0].reset();
                $('#user_id').val('');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                if (!{{ auth()->user()->isPrimary() ? 'true' : 'false' }}) {
                    $('#adminOption').hide();
                }
            }
            $('#createNewUser').click(function () {
                resetForm();
                $('#modalTitle').text('Add User');
                $('#passwordLabel').html('Password <span class="text-danger">*</span>');
                $('#password').attr('placeholder', '••••••••');
                $('#passwordEditHelp').addClass('d-none');
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
                $submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $closeBtns.prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function (data, textStatus, xhr) {
                        $submitBtn.html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
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
                        if (data && data.user) {
                            if (userId) {
                                var existingRow = table.row($('#user-row-' + userId));
                                if (existingRow.length) {
                                    existingRow.data(generateUserCells(data.user)).draw(false);
                                    var node = existingRow.node();
                                    $(node).find('td:last').addClass('text-center');
                                }
                            } else {
                                var newRowNode = table.row.add(generateUserCells(data.user)).draw(false).node();
                                $(newRowNode).attr('id', 'user-row-' + data.user.id).attr('data-id', data.user.id);
                                $(newRowNode).find('td:last').addClass('text-center');
                            }
                            initTooltips();
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'User Saved Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    },
                    error: function (xhr) {
                        $submitBtn.html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
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
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Process Request',
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
                    if (data.role === 'admin') {
                        $('#adminOption').show();
                    }
                    $('#role').val(data.role);
                    $('#passwordLabel').text('New Password (Optional)');
                    $('#password').attr('placeholder', 'Leave blank to retain current password');
                    $('#passwordEditHelp').removeClass('d-none');
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
                    title: 'Confirm User Deletion',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
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
                                var row = table.row($('#user-row-' + userId));
                                if (row.length) {
                                    row.remove().draw(false);
                                }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'User Deleted Successfully',
                                    showConfirmButton: false,
                                    timer: 1500
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