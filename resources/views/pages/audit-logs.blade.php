@extends('layouts.app')
@section('title', 'Audit Logs')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header text-md-start text-center">
                <h5 class="mb-0">Audit Log History</h5>
            </div>
            <div class="card-body">
                <div class="row mx-0">
                    <div class="col-md-4 mb-2">
                        <select id="filterAction" class="form-select">
                            <option value="">Select Action</option>
                            @foreach ($actions as $action)
                                <option value="{{ $action }}">{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <select id="filterCauser" class="form-select">
                            <option value="">Performed By</option>
                            @foreach ($causers as $causer)
                                <option value="{{ $causer }}">{{ $causer }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <select id="filterSubject" class="form-select">
                            <option value="">Target User</option>
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject }}">{{ $subject }}</option>
                            @endforeach
                        </select>
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
                                <th>Detail</th>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                        render: function (data, type, row) {
                            if (!row.has_detail) return '-';
                            return '<button type="button" class="btn btn-sm btn-outline-primary viewBtn" ' +
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
            $('#filterAction, #filterCauser, #filterSubject').on('change', function () {
                table.draw();
            });
            $('body').on('click', '.viewBtn', function () {
                var oldVal = JSON.parse(decodeURIComponent($(this).data('old')));
                var newVal = JSON.parse(decodeURIComponent($(this).data('new')));
                $('#oldValues').text(oldVal ? JSON.stringify(oldVal, null, 2) : 'No data');
                $('#newValues').text(newVal ? JSON.stringify(newVal, null, 2) : 'No data');
                $('#detailModal').modal('show');
            });
        });
    </script>
@endpush