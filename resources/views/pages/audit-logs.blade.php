@extends('layouts.app')
@section('title', 'Audit Logs')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div
                class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center text-md-start text-center gap-2">
                <h5 class="mb-0">Audit Log History</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="text-body-secondary fw-medium d-inline-flex align-items-center me-1 ps-3">
                        <i class="bx bx-filter-alt me-1"></i>Filters
                    </span>
                    <div class="dropdown">
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn"
                            data-bs-toggle="dropdown" data-filter-target="filterAction" data-filter-label="Action">
                            Action
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterAction">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Actions</a></li>
                            @foreach ($actions as $action)
                                <li><a class="dropdown-item filterOption" href="#"
                                        data-value="{{ $action }}">{{ strtoupper(str_replace('_', ' ', $action)) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn"
                            data-bs-toggle="dropdown" data-filter-target="filterCauser" data-filter-label="Performer">
                            Performer
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterCauser">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Performers</a></li>
                            <li><a class="dropdown-item filterOption" href="#" data-value="System">SYSTEM</a></li>
                            @foreach ($causers as $causer)
                                @if ($causer !== 'System')
                                    <li><a class="dropdown-item filterOption" href="#" data-value="{{ $causer }}">{{ $causer }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn"
                            data-bs-toggle="dropdown" data-filter-target="filterSubject" data-filter-label="Target">
                            Target
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterSubject">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Target Users</a></li>
                            @foreach ($subjects as $subject)
                                <li><a class="dropdown-item filterOption" href="#"
                                        data-value="{{ $subject }}">{{ $subject }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" id="clearFilters"
                        class="btn btn-sm btn-link text-danger d-none align-items-center gap-1 text-decoration-none ms-1">
                        <i class="bx bx-x-circle"></i>
                        <span>Clear all</span>
                    </button>
                </div>
                <div id="activeFilterChips" class="d-flex flex-wrap gap-2 mb-1"></div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="auditlogTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Performed By</th>
                                <th>Action</th>
                                <th>Target User</th>
                                <th>IP Address</th>
                                <th>Date</th>
                                <th class="text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
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
            $.extend(true, DataTable.ext.classes, {
                search: { input: 'form-control' },
                length: { select: 'form-select' }
            });
            var detailBaseUrl = '{{ url("audit-logs") }}';
            var filterState = { filterAction: '', filterCauser: '', filterSubject: '' };
            var filterLabels = { filterAction: 'Action', filterCauser: 'Performer', filterSubject: 'Target' };
            function escapeHtml(value) {
                return $('<div>').text(value == null ? '' : String(value)).html();
            }
            function initTooltips() {
                $('[data-bs-toggle="tooltip"]').each(function () {
                    var existingTooltip = bootstrap.Tooltip.getInstance(this);
                    if (existingTooltip) {
                        existingTooltip.dispose();
                    }
                    new bootstrap.Tooltip(this);
                });
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
            var table = $('#auditlogTable').DataTable({
                serverSide: true,
                processing: true,
                searchDelay: 600,
                ajax: {
                    url: '{{ route("audit-logs.data") }}',
                    data: function (data) {
                        data.filter_action = filterState.filterAction;
                        data.filter_causer = filterState.filterCauser;
                        data.filter_subject = filterState.filterSubject;
                    }
                },
                order: [[5, 'desc']],
                columns: [
                    { data: 'id', orderable: false },
                    {
                        data: 'causer',
                        render: function (data) {
                            if (data === 'System') {
                                return '<span class="badge bg-label-secondary">System</span>';
                            }
                            return '<span class="fw-medium">' + escapeHtml(data) + '</span>';
                        }
                    },
                    {
                        data: 'action',
                        render: function (data, type, row) {
                            return '<span class="badge ' + row.action_badge + '">' + row.action_label + '</span>';
                        }
                    },
                    { data: 'subject' },
                    { data: 'ip_address', orderable: false },
                    { data: 'date' },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (!row.has_detail) return '—';
                            return '<button type="button" class="btn btn-sm btn-outline-primary viewBtn" ' +
                                'data-bs-toggle="tooltip" data-bs-placement="top" title="View Detail" ' +
                                'aria-label="View Detail" ' +
                                'data-log-id="' + row.log_id + '">' +
                                '<i class="bx bx-show"></i></button>';
                        }
                    }
                ],
                language: {
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No data available",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search Log",
                    processing: 'Fetching Audit Logs...' +
                        '</div>',
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
                            '<span class="fw-semibold">' + escapeHtml(filterLabels[key]) + ':</span>' +
                            '<span>' + escapeHtml(value) + '</span>' +
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
            $('body').on('click', '.viewBtn', function () {
                var logId = $(this).data('log-id');
                Swal.fire({
                    title: 'Loading Detail...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    }
                });
                $.getJSON(detailBaseUrl + '/' + logId + '/detail')
                    .done(function (res) {
                        Swal.close();
                        $('#detailContent').html(renderDiffTable(res.old_values, res.new_values));
                        $('#detailModal').modal('show');
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
            renderFilterChips();
            initTooltips();
        });
    </script>
@endpush