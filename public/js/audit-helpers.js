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

function renderDiffTable(res, hideMetadata = false) {
    var oldVal = res.old_values || {};
    var newVal = res.new_values || {};
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
    var metaHtml = '';
    var footerHtml = '';
    if (!hideMetadata) {
        if (res.method && res.url) {
            var methodBadgeClass = 'bg-label-primary';
            if (res.method === 'POST') methodBadgeClass = 'bg-label-success';
            if (res.method === 'PUT' || res.method === 'PATCH') methodBadgeClass = 'bg-label-info';
            if (res.method === 'DELETE') methodBadgeClass = 'bg-label-danger';
            metaHtml = '<div class="d-flex align-items-center mb-3">' +
                '<span class="badge ' + methodBadgeClass + ' me-2">' + escapeHtml(res.method) + '</span>' +
                '<span class="text-muted text-break" style="font-size: 0.9em;">' + escapeHtml(res.url) + '</span>' +
                '</div>';
        }
        if (res.user_agent) {
            footerHtml = '<div class="mt-3 text-muted" style="font-size: 0.85em;">' +
                '<i class="bx bx-devices me-1"></i>' + escapeHtml(res.user_agent) +
                '</div>';
        }
    }
    var tableHtml = '<div class="table-responsive">' +
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
    return metaHtml + tableHtml + footerHtml;
}