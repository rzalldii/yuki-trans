@extends('layouts.app')
@section('title')
    Users | Yuki Trans
@endsection
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
                    <table class="table table-striped table-borderless table-hover" id="userTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $user->username }}</strong>
                                        @if ($user->id === auth()->id())
                                            <span class="badge bg-label-primary ms-1">You</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($user->role === 'admin')
                                            @if ($user->isPrimary())
                                                <i class="bx bx-crown text-warning me-1"></i>
                                            @else
                                                <i class="bx bx-desktop text-danger me-1"></i>
                                            @endif
                                            <span>Admin</span>
                                        @else
                                            <i class="bx bx-user text-success me-1"></i>
                                            <span>User</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $canEdit = auth()->user()->canEdit($user);
                                            $canDelete = auth()->user()->canDelete($user);
                                            $isSelf = auth()->user()->isSelf($user);
                                        @endphp
                                        @if (!$isSelf)
                                            <div class="d-flex gap-1">
                                                @if (!$user->isPrimary())
                                                    <a href="{{ route('users.profile', $user) }}" class="btn btn-sm btn-outline-info"
                                                        data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top"
                                                        title="View Profile">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                @endif
                                                @if ($canEdit)
                                                    <button type="button" class="btn btn-sm btn-outline-warning editBtn"
                                                        data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top"
                                                        title="Edit User" data-id="{{ $user->id }}">
                                                        <i class="bx bx-edit-alt"></i>
                                                    </button>
                                                    @if ($canDelete)
                                                        <button type="button" class="btn btn-sm btn-outline-danger deleteBtn"
                                                            data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top"
                                                            title="Delete User" data-id="{{ $user->id }}">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    @endif
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="bx bx-lock-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="userModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-hidden="true" role="dialog">
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
                                <input type="text" name="username" id="username" class="form-control" placeholder="johndoe">
                                <div class="invalid-feedback" id="usernameError"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3 form-password-toggle">
                                <label class="form-label" id="passwordLabel">Password <span
                                        class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="password" id="password" class="form-control"
                                        placeholder="Min. 8 characters, letters & numbers">
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback" id="passwordError"></div>
                                <div class="form-text d-none" id="passwordHelp">Leave this field blank to retain the current
                                    password</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" id="role" class="form-select">
                                    <option value="" selected disabled>Choose a role for this user</option>
                                    <option value="admin" id="adminOption" @if (!auth()->user()->isPrimary())
                                    style="display:none;" @endif>
                                        Admin
                                    </option>
                                    <option value="user">User</option>
                                </select>
                                <div class="invalid-feedback" id="roleError"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="saveBtn" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                            <i class="bx bx-x me-1"></i>Close
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
            $('#userTable').DataTable({
                order: [[1, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [0, 3] }
                ],
                pageLength: 10,
                language: {
                    lengthMenu: "_MENU_",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    search: "",
                    searchPlaceholder: "Search User"
                }
            });
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
                $('#passwordHelp').addClass('d-none');
                $('#userModal').modal('show');
            });
            $('body').on('click', '.editBtn', function () {
                var userId = $(this).data('id');
                $.get('/users/' + userId + '/edit', function (data) {
                    resetForm();
                    $('#modalTitle').text('Edit User');
                    $('#user_id').val(data.id);
                    $('#username').val(data.username);
                    if (data.role === 'admin') {
                        $('#adminOption').show();
                    }
                    $('#role').val(data.role);
                    $('#passwordLabel').text('New Password');
                    $('#passwordHelp').removeClass('d-none');
                    $('#userModal').modal('show');
                }).fail(function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Load User Data',
                        confirmButtonColor: '#696cff'
                    });
                });
            });
            $('#userForm').on('submit', function (e) {
                e.preventDefault();
                var userId = $('#user_id').val();
                var url = userId ? '/users/' + userId : '/users';
                var formData = $(this).serialize();
                if (userId) {
                    formData += '&_method=PUT';
                }
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').removeClass('d-block');
                $('#saveBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: formData,
                    success: function () {
                        $('#saveBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        $('#userModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'The user data has been saved successfully.',
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
                            Swal.fire({
                                icon: 'error',
                                title: xhr.status === 403 ? 'Action Not Permitted' : 'Unable to Process Request',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.deleteBtn', function () {
                var userId = $(this).data('id');
                Swal.fire({
                    title: 'Confirm User Deletion',
                    text: 'This action is permanent and cannot be reversed.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#dc3545'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'DELETE',
                            url: '/users/' + userId,
                            success: function () {
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