@extends('layouts.app')
@section('title', 'Profile')
@section('content')
    @php
        $profileUser = $profileUser ?? auth()->user();
        $isAdminView = $isAdminView ?? false;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-xl-4 col-lg-5 col-md-5">
                <div class="card mb-4">
                    <div class="card-body">
                        <small class="card-text text-uppercase text-body-secondary small">About</small>
                        <ul class="list-unstyled my-3 py-1">
                            <li class="d-flex align-items-center mb-4">
                                <i class="bx bx-at"></i><span class="fw-medium mx-2">Username:</span>
                                <span>{{ $profileUser->username }}</span>
                            </li>
                            <li class="d-flex align-items-center mb-4">
                                <i class="bx bx-user"></i><span class="fw-medium mx-2">Full Name:</span>
                                <span>{{ $profileUser->full_name ?? '—' }}</span>
                            </li>
                            <li class="d-flex align-items-start mb-4">
                                <i class="bx bx-home"></i>
                                <div class="mx-2 flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-medium">Address:</span>
                                        @if ($profileUser->address)
                                            <a href="javascript:;" data-bs-toggle="collapse" data-bs-target="#addressCollapse"
                                                class="medium">
                                                View address
                                            </a>
                                        @else
                                            <span>—</span>
                                        @endif
                                    </div>
                                    <div class="collapse mt-1" id="addressCollapse">
                                        <span style="word-break: break-word;">{{ $profileUser->address }}</span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <small class="card-text text-uppercase text-body-secondary small">Contacts</small>
                        <ul class="list-unstyled my-3 py-1">
                            <li class="d-flex align-items-center mb-4">
                                <i class="bx bx-envelope"></i><span class="fw-medium mx-2">Email:</span>
                                <span>{{ $profileUser->email ?? '—' }}</span>
                            </li>
                            <li class="d-flex align-items-center mb-4">
                                <i class="bx bx-phone"></i><span class="fw-medium mx-2">Phone:</span>
                                <span>{{ $profileUser->phone_number ?? '—' }}</span>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-center">
                            @if (!$isAdminView)
                                <a href="javascript:;" class="btn btn-primary me-3" id="profileBtn"
                                    data-bs-target="#profileModal" data-bs-toggle="modal">
                                    <i class="bx bx-edit-alt me-1"></i>Edit
                                </a>
                                <a href="javascript:;" class="btn btn-outline-secondary" id="securityBtn"
                                    data-bs-target="#securityModal" data-bs-toggle="modal">
                                    <i class="bx bx-lock-alt me-1"></i>Security
                                </a>
                            @else
                                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>Back to User List
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <small class="card-text text-uppercase text-body-secondary small">Overview</small>
                        <ul class="list-unstyled mb-0 mt-3 pt-1">
                            <li class="d-flex align-items-center"><i class="icon-base bx bx-history"></i>
                                <span class="fw-medium mx-2">Activities Recorded:</span> <span>{{ $totalActivities }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 col-lg-7 col-md-7">
                <div class="card mb-4">
                    <h5 class="card-header text-md-start text-center">
                        {{ $isAdminView ? $profileUser->username . "'s Activity History" : 'My Activity History' }}
                    </h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-striped table-borderless table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Performed By</th>
                                    <th>Action</th>
                                    <th>Target User</th>
                                    <th>Date</th>
                                    <th class="text-center">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse ($activities as $index => $activity)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span class="fw-medium">{{ $activity['causer'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $activity['action_badge'] }}">
                                                {{ $activity['action_label'] }}
                                            </span>
                                        </td>
                                        <td>{{ $activity['subject'] }}</td>
                                        <td>{{ $activity['date'] }}</td>
                                        <td class="text-center">
                                            @if (!empty($activity['has_detail']))
                                                <button type="button" class="btn btn-sm btn-outline-primary viewActivityBtn"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="View Detail"
                                                    aria-label="View Detail" data-log-id="{{ $activity['log_id'] }}">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No activity found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if (!$isAdminView)
        <div class="modal fade" id="profileModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-hidden="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <form id="profileForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username" class="form-control"
                                        placeholder="e.g. johndoe" value="{{ auth()->user()->username }}">
                                    <div class="invalid-feedback" id="usernameError"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="bx bx-user"></i></span>
                                        <input type="text" name="full_name" id="full_name" class="form-control"
                                            placeholder="e.g. John Doe" value="{{ auth()->user()->full_name }}">
                                    </div>
                                    <div class="invalid-feedback" id="full_nameError"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                        <input type="email" name="email" id="email" class="form-control"
                                            placeholder="e.g. john.doe@example.com" value="{{ auth()->user()->email }}">
                                    </div>
                                    <div class="invalid-feedback" id="emailError"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                        <input type="text" name="phone_number" id="phone_number" class="form-control"
                                            placeholder="e.g. +62 812-3456-7890" value="{{ auth()->user()->phone_number }}">
                                    </div>
                                    <div class="invalid-feedback" id="phone_numberError"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col mb-3">
                                    <label class="form-label">Address</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="bx bx-home"></i></span>
                                        <textarea name="address" id="address" class="form-control" rows="3"
                                            placeholder="Street name, house number, city, postal code">{{ auth()->user()->address }}</textarea>
                                    </div>
                                    <div class="invalid-feedback" id="addressError"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="submit" id="saveProfileBtn" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="securityModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-hidden="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form id="securityForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Change Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3 form-password-toggle">
                                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="current_password" id="current_password" class="form-control"
                                        placeholder="Enter your current password">
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback" id="current_passwordError"></div>
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="password" id="new_password" class="form-control"
                                        placeholder="Min. 8 characters, letters & numbers">
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback" id="passwordError"></div>
                            </div>
                            <div class="mb-3 form-password-toggle">
                                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <div class="input-group input-group-merge">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="form-control" placeholder="Re-enter your new password">
                                    <span class="input-group-text cursor-pointer" id="togglePassword">
                                        <i class="bx bx-hide"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="submit" id="saveSecurityBtn" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    <div class="modal fade" id="myActivityDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="detailContent"></div>
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            function resetForm(formId) {
                $('#' + formId)[0].reset();
                clearErrors(formId);
            }
            function clearErrors(formId) {
                $('#' + formId + ' .is-invalid').removeClass('is-invalid');
                $('#' + formId + ' .invalid-feedback').text('').removeClass('d-block');
            }
            function escapeHtml(value) {
                return $('<div>').text(value == null ? '' : String(value)).html();
            }
            function normalizeValue(value) {
                if (value === undefined) return '—';
                if (value === null) return null;
                if (typeof value === 'boolean') return value ? 'true' : 'false';
                if (typeof value === 'object') return JSON.stringify(value, null, 2);
                return String(value);
            }
            function getDiffType(before, after) {
                var hasBefore = before !== undefined && before !== null && before !== '—';
                var hasAfter = after !== undefined && after !== null && after !== '—';
                if (!hasBefore && hasAfter) return 'added';
                if (hasBefore && !hasAfter) return 'removed';
                if (hasBefore && hasAfter && before !== after) return 'changed';
                return 'same';
            }
            function getDiffBadge(type) {
                switch (type) {
                    case 'added':
                        return '<span class="badge bg-label-success">Added</span>';
                    case 'removed':
                        return '<span class="badge bg-label-danger">Removed</span>';
                    case 'changed':
                        return '<span class="badge bg-label-warning">Changed</span>';
                    default:
                        return '';
                }
            }
            function renderDiffTable(oldVal, newVal) {
                oldVal = oldVal || {};
                newVal = newVal || {};
                var keys = [];
                var seen = {};
                $.each(oldVal, function (k) {
                    if (!seen[k]) {
                        seen[k] = true;
                        keys.push(k);
                    }
                });
                $.each(newVal, function (k) {
                    if (!seen[k]) {
                        seen[k] = true;
                        keys.push(k);
                    }
                });
                if (!keys.length) {
                    return '<p class="text-body-secondary mb-0">No field changes recorded.</p>';
                }
                var rows = keys.map(function (key) {
                    var beforeRaw = oldVal[key];
                    var afterRaw = newVal[key];
                    var before = normalizeValue(beforeRaw);
                    var after = normalizeValue(afterRaw);
                    var diffType = getDiffType(beforeRaw, afterRaw);
                    var rowClass = '';
                    if (diffType === 'added') rowClass = 'table-success';
                    if (diffType === 'removed') rowClass = 'table-danger';
                    if (diffType === 'changed') rowClass = 'table-warning';
                    var badge = getDiffBadge(diffType);
                    return '<tr class="' + rowClass + '">' +
                        '<td class="fw-medium align-top">' +
                        escapeHtml(key) + (badge ? ' ' + badge : '') +
                        '</td>' +
                        '<td class="align-top text-danger">' +
                        escapeHtml(before === null ? '—' : before) +
                        '</td>' +
                        '<td class="align-top text-success">' +
                        escapeHtml(after === null ? '—' : after) +
                        '</td>' +
                        '</tr>';
                }).join('');
                return '<div class="table-responsive">' +
                    '<table class="table table-sm table-bordered align-middle mb-0">' +
                    '<thead class="table-light">' +
                    '<tr>' +
                    '<th style="width: 28%;">Field</th>' +
                    '<th style="width: 36%;">Before</th>' +
                    '<th style="width: 36%;">After</th>' +
                    '</tr>' +
                    '</thead>' +
                    '<tbody>' + rows + '</tbody>' +
                    '</table>' +
                    '</div>';
            }
            $('#profileModal').on('hidden.bs.modal', function () {
                resetForm('profileForm');
            });
            $('#securityModal').on('hidden.bs.modal', function () {
                resetForm('securityForm');
            });
            $('#profileForm').on('submit', function (e) {
                e.preventDefault();
                clearErrors('profileForm');
                $('#saveProfileBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: '{{ route('profile.update') }}',
                    data: $(this).serialize(),
                    success: function (data, textStatus, xhr) {
                        $('#saveProfileBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 204) {
                            $('#profileModal').modal('hide');
                            Swal.fire({
                                icon: 'info',
                                title: 'No Changes Detected',
                                text: 'There were no changes to save.',
                                confirmButtonColor: '#696cff'
                            });
                            return;
                        }
                        $('#profileModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Your profile has been updated successfully.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        $('#saveProfileBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                $('[name="' + field + '"]').addClass('is-invalid');
                                $('#' + field + 'Error').text(messages[0]).addClass('d-block');
                            });
                        } else {
                            $('#profileModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Unable to Process Request',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('#securityForm').on('submit', function (e) {
                e.preventDefault();
                clearErrors('securityForm');
                $('#saveSecurityBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...').prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: '{{ route('profile.password') }}',
                    data: $(this).serialize(),
                    success: function () {
                        $('#saveSecurityBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        $('#securityModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Your password has been changed successfully.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        $('#saveSecurityBtn').html('<i class="bx bx-save me-1"></i>Save').prop('disabled', false);
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, value) {
                                var message;
                                if (field === 'current_password' && value === true) {
                                    message = 'The current password you entered is incorrect.';
                                } else {
                                    message = Array.isArray(value) ? value[0] : value;
                                }
                                var inputName = field === 'password' ? 'new_password' : field;
                                $('#' + inputName).addClass('is-invalid');
                                $('#' + field + 'Error').text(message).addClass('d-block');
                            });
                        } else {
                            $('#securityModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Unable to Process Request',
                                confirmButtonColor: '#696cff'
                            });
                        }
                    }
                });
            });
            $('body').on('click', '.viewActivityBtn', function () {
                var logId = $(this).data('log-id');
                Swal.fire({
                    title: 'Loading Detail...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.getJSON('{{ url('profile/audit-logs') }}/' + logId + '/detail')
                    .done(function (res) {
                        Swal.close();
                        $('#detailContent').html(renderDiffTable(res.old_values, res.new_values));
                        $('#myActivityDetailModal').modal('show');
                    })
                    .fail(function () {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Load Detail',
                            text: 'Please try again.'
                        });
                    });
            });
        });
    </script>
@endpush