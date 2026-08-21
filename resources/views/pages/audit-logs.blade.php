@extends('layouts.app')
@section('title', 'Audit Logs')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center text-md-start text-center gap-2">
                <h5 class="mb-0">Audit Log History</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="text-body-secondary fw-medium d-inline-flex align-items-center me-1 ps-3">
                        <i class="bx bx-filter-alt me-1"></i>Filters
                    </span>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterDate" data-filter-label="Date">
                            Date
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterDate">
                            <li><a class="dropdown-item filterDateOption" href="#" data-range="all" data-label="All Time">All Time</a></li>
                            <li><a class="dropdown-item filterDateOption" href="#" data-range="today" data-label="Today">Today</a></li>
                            <li><a class="dropdown-item filterDateOption" href="#" data-range="yesterday" data-label="Yesterday">Yesterday</a></li>
                            <li><a class="dropdown-item filterDateOption" href="#" data-range="7days" data-label="Last 7 Days">Last 7 Days</a></li>
                            <li><a class="dropdown-item filterDateOption" href="#" data-range="30days" data-label="Last 30 Days">Last 30 Days</a></li>
                            <li><a class="dropdown-item filterDateOption" href="#" data-range="this_month" data-label="This Month">This Month</a></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterAction" data-filter-label="Action">
                            Action
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterAction">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Actions</a></li>
                            @foreach ($actions as $action)
                                <li><a class="dropdown-item filterOption" href="#" data-value="{{ $action }}">{{ strtoupper(str_replace('_', ' ', $action)) }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterCauser" data-filter-label="Performer">
                            Performer
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterCauser">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Performers</a></li>
                            <li><a class="dropdown-item filterOption" href="#" data-value="System">SYSTEM</a></li>
                            @foreach ($causers as $causer)
                                @if ($causer !== 'System')
                                    <li><a class="dropdown-item filterOption" href="#" data-value="{{ $causer }}">{{ $causer }}</a></li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill dropdown-toggle filterDropdownBtn" data-bs-toggle="dropdown" data-filter-target="filterSubject" data-filter-label="Target">
                            Target
                        </button>
                        <ul class="dropdown-menu filterMenu" data-filter-target="filterSubject">
                            <li><a class="dropdown-item filterOption" href="#" data-value="">All Target Users</a></li>
                            @foreach ($subjects as $subject)
                                <li><a class="dropdown-item filterOption" href="#" data-value="{{ $subject }}">{{ $subject }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" id="clearFilters" class="btn btn-sm btn-link text-danger d-none align-items-center gap-1 text-decoration-none ms-1">
                        <i class="bx bx-x-circle"></i>
                        <span>Clear all</span>
                    </button>
                </div>
                <div id="activeFilterChips" class="d-flex flex-wrap gap-2 mb-1"></div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped" id="auditlogTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Performed By</th>
                                <th>Action</th>
                                <th>Target User</th>
                                <th>IP Address</th>
                                <th class="text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true" role="dialog">
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
            var filterState = { filterDate: '', startDate: '', endDate: '', filterAction: '', filterCauser: '', filterSubject: '' };
            var filterLabels = { filterDate: 'Date', filterAction: 'Action', filterCauser: 'Performer', filterSubject: 'Target' };
            function getDateRange(range) {
                var today = new Date();
                function formatDate(d) {
                    var year = d.getFullYear();
                    var month = String(d.getMonth() + 1).padStart(2, '0');
                    var day = String(d.getDate()).padStart(2, '0');
                    return year + '-' + month + '-' + day;
                }
                if (range === 'today') {
                    var str = formatDate(today);
                    return { start: str, end: str };
                }
                if (range === 'yesterday') {
                    var yest = new Date(today);
                    yest.setDate(yest.getDate() - 1);
                    var str = formatDate(yest);
                    return { start: str, end: str };
                }
                if (range === '7days') {
                    var past = new Date(today);
                    past.setDate(past.getDate() - 6);
                    return { start: formatDate(past), end: formatDate(today) };
                }
                if (range === '30days') {
                    var past = new Date(today);
                    past.setDate(past.getDate() - 29);
                    return { start: formatDate(past), end: formatDate(today) };
                }
                if (range === 'this_month') {
                    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    return { start: formatDate(firstDay), end: formatDate(today) };
                }
                return { start: '', end: '' };
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
                        data.start_date = filterState.startDate;
                        data.end_date = filterState.endDate;
                    }
                },
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [4, 5] }
                ],
                columns: [
                    { data: 'date' },
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
                    { data: 'ip_address' },
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: function (data, type, row) {
                            if (!row.has_detail) return '—';
                            return '<button type="button" class="btn btn-sm btn-outline-info viewBtn" ' +
                                'data-bs-toggle="tooltip" data-bs-placement="top" title="View Audit Log" ' +
                                'aria-label="View Audit Log" ' +
                                'data-log-id="' + row.log_id + '">' +
                                '<i class="bx bx-show"></i></button>';
                        }
                    }
                ],
                language: {
                    emptyTable: "No audit logs available.",
                    zeroRecords: "No matching audit logs found.",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    search: "Search:",
                    searchPlaceholder: "Search Audit Log",
                    processing: "Fetching Audit Logs...",
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
            function setDropdownState(target, hasValue) {
                $('.filterDropdownBtn[data-filter-target="' + target + '"]')
                    .toggleClass('btn-primary', hasValue)
                    .toggleClass('text-white', hasValue)
                    .toggleClass('btn-outline-secondary', !hasValue);
            }
            function renderFilterChips() {
                var chipsHtml = '';
                var activeCount = 0;
                $.each(filterLabels, function (key, label) {
                    var value = filterState[key];
                    if (value) {
                        activeCount++;
                        chipsHtml += '<span class="badge rounded-pill bg-primary-subtle text-primary d-inline-flex align-items-center gap-1 py-2 px-3">' +
                            '<span class="fw-semibold">' + escapeHtml(label) + ':</span>' +
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
            $('body').on('click', '.filterDateOption', function (e) {
                e.preventDefault();
                var range = $(this).data('range') || 'all';
                var label = $(this).data('label') || 'All Time';
                var rangeDates = getDateRange(range);
                if (range === 'all') {
                    filterState.filterDate = '';
                    filterState.startDate = '';
                    filterState.endDate = '';
                    $('.filterDropdownBtn[data-filter-target="filterDate"]').text('Date');
                    setDropdownState('filterDate', false);
                } else {
                    filterState.filterDate = label;
                    filterState.startDate = rangeDates.start;
                    filterState.endDate = rangeDates.end;
                    $('.filterDropdownBtn[data-filter-target="filterDate"]').text(label);
                    setDropdownState('filterDate', true);
                }
                renderFilterChips();
                table.draw();
            });
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
                if (target === 'filterDate') {
                    filterState.startDate = '';
                    filterState.endDate = '';
                }
                $('.filterDropdownBtn[data-filter-target="' + target + '"]').text(label);
                setDropdownState(target, false);
                renderFilterChips();
                table.draw();
            });
            $('#clearFilters').on('click', function () {
                $.each(filterLabels, function (key, label) {
                    filterState[key] = '';
                    $('.filterDropdownBtn[data-filter-target="' + key + '"]').text(label);
                    setDropdownState(key, false);
                });
                filterState.startDate = '';
                filterState.endDate = '';
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
                        $('#detailContent').html(renderDiffTable(res));
                        $('#detailModal').modal('show');
                    })
                    .fail(function () {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Load Detail'
                        });
                    });
            });
            renderFilterChips();
            initTooltips();
        });
    </script>
@endpush