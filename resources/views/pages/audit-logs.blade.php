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
                <div class="rounded border bg-body-tertiary p-3 mb-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-body-secondary fw-medium d-inline-flex align-items-center">
                                <i class="bx bx-filter-alt me-1"></i>Filters
                            </span>
                            <small id="activeFilterText" class="text-body-secondary d-none"></small>
                        </div>
                        <button type="button" id="clearFilters"
                            class="btn btn-sm btn-outline-secondary d-none align-items-center gap-1">
                            <i class="bx bx-x"></i>
                            <span>Clear Filters</span>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select id="filterAction" class="form-select">
                                <option value="">All Actions</option>
                                @foreach ($actions as $action)
                                    <option value="{{ $action }}">{{ $action }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filterCauser" class="form-select">
                                <option value="">All Performers</option>
                                @foreach ($causers as $causer)
                                    <option value="{{ $causer }}">{{ $causer }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filterSubject" class="form-select">
                                <option value="">All Target Users</option>
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject }}">{{ $subject }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped table-borderless table-hover" id="auditlogTable">
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <small class="text-uppercase text-body-secondary">Before</small>
                        <pre id="oldValues" class="bg-light p-2 rounded"></pre>
                    </div>
                    <div class="mb-0">
                        <small class="text-uppercase text-body-secondary">After</small>
                        <pre id="newValues" class="bg-light p-2 rounded"></pre>
                    </div>
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
            function initTooltips() {
                $('[data-bs-toggle="tooltip"]').each(function () {
                    if (!bootstrap.Tooltip.getInstance(this)) {
                        new bootstrap.Tooltip(this);
                    }
                });
            }
            var table = $('#auditlogTable').DataTable({
                serverSide: true,
                ajax: function (data, callback, settings) {
                    data.filter_action = $('#filterAction').val();
                    data.filter_causer = $('#filterCauser').val();
                    data.filter_subject = $('#filterSubject').val();
                    $.ajax({
                        url: '{{ route("audit-logs.data") }}',
                        data: data,
                        success: callback
                    });
                },
                order: [[5, 'desc']],
                columns: [
                    { data: 'id', orderable: false },
                    { data: 'causer' },
                    {
                        data: 'action',
                        render: function (data) {
                            return '<span class="badge bg-label-primary">' + data + '</span>';
                        }
                    },
                    { data: 'subject' },
                    { data: 'ip_address' },
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
                                'data-old="' + encodeURIComponent(JSON.stringify(row.old_values)) + '" ' +
                                'data-new="' + encodeURIComponent(JSON.stringify(row.new_values)) + '">' +
                                '<i class="bx bx-show"></i></button>';
                        }
                    }
                ],
                language: {
                    lengthMenu: "_MENU_",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    search: "",
                    searchPlaceholder: "Search Log"
                }
            });
            table.on('draw', function () {
                initTooltips();
            });
            function toggleClearButton() {
                var filters = [
                    $('#filterAction').val(),
                    $('#filterCauser').val(),
                    $('#filterSubject').val()
                ];
                var activeCount = filters.filter(function (value) {
                    return value && value.trim() !== '';
                }).length;
                $('#clearFilters')
                    .toggleClass('d-none', activeCount === 0)
                    .toggleClass('d-inline-flex', activeCount > 0);
                $('#activeFilterText')
                    .toggleClass('d-none', activeCount === 0)
                    .text(activeCount > 0
                        ? activeCount + ' filter' + (activeCount > 1 ? 's' : '') + ' active'
                        : '');
            }
            $('#filterAction, #filterCauser, #filterSubject').on('change', function () {
                toggleClearButton();
                table.draw();
            });
            $('#clearFilters').on('click', function () {
                $('#filterAction, #filterCauser, #filterSubject').val('');
                toggleClearButton();
                table.draw();
            });
            $('body').on('click', '.viewBtn', function () {
                var oldVal = JSON.parse(decodeURIComponent($(this).data('old')));
                var newVal = JSON.parse(decodeURIComponent($(this).data('new')));
                $('#oldValues').text(oldVal ? JSON.stringify(oldVal, null, 2) : 'No data');
                $('#newValues').text(newVal ? JSON.stringify(newVal, null, 2) : 'No data');
                $('#detailModal').modal('show');
            });
            toggleClearButton();
            initTooltips();
        });
    </script>
@endpush