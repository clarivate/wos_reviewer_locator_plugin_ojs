/**
 * plugins/generic/wosReviewerLocator/js/wosrl.js
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * wosReviewerLocator JS functionality
 *
 */

function wosRLList(page_url) {
    const wrapper = $('#wosRLGrid');
    $('ul.actions', wrapper.parent()).addClass('pkp_helpers_display_none');
    wrapper.html('<div class="wosrl-loader"><span class="pkp_spinner"></span></div>');
    $.ajax({
        url: page_url,
        dataType: 'json',
        success: function(data) {
            if (data.status === true && data.content) {
                wrapper.html(data.content);
                if($('#wosRLToolbar', wrapper).length) {
                    wosRLPagination();
                }
            } else {
                $('ul.actions', wrapper.parent()).removeClass('pkp_helpers_display_none');
                wrapper.html('<div class="wosrl-placeholder">' + data.content + '</div>');
            }
        },
        error: function() {
            $('ul.actions', wrapper.parent()).removeClass('pkp_helpers_display_none');
            wrapper.html('<div class="wosrl-placeholder">Failed request or invalid JSON returned.</div>');
        }
    });
    return false;
}

function wosRLPagination() {
    let prev = { start: 0, stop: 10 };
    let content = $('#wosRLList > table > tbody > tr').not('.wosrl-conflict');
    $('#wosRLPagination').paging(content.length, {
        format: '- [<>]',
        perpage: $('#wosRLToolbar #wosRLItems select[name=wosrl-item-count]').val(),
        lapping: 0,
        page: 1,
        onSelect: function (page) {
            let data = this.slice;
            content.slice(prev[0], prev[1]).addClass('pkp_helpers_display_none');
            content.slice(data[0], data[1]).removeClass('pkp_helpers_display_none');
            prev = data;
            return false;
        },
        onFormat: function (type) {
            switch (type) {
                case 'block': // n and c
                    return '<a>' + this.value + '</a>';
                case 'next': // >
                    const _next = '<svg viewBox="0 0 24 24" focusable="false"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path></svg>';
                    return (this.active || (this.page === 1 && this.page !== this.pages)) ? '<a>' + _next + '</a>' : _next;
                case 'prev': // <
                    const _prev = '<svg viewBox="0 0 24 24" focusable="false"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path></svg>';
                    return (this.active || (this.page === this.pages && this.page !== 1)) ? '<a>' + _prev + '</a>' : _prev;
                case 'first': // [
                    const _first = '<svg viewBox="0 0 24 24" focusable="false"><path d="M18.41 16.59L13.82 12l4.59-4.59L17 6l-6 6 6 6zM6 6h2v12H6z"></path></svg>';
                    return this.active ? '<a>' + _first + '</a>' : _first;
                case 'last': // ]
                    const _last = '<svg viewBox="0 0 24 24" focusable="false"><path d="M5.59 7.41L10.18 12l-4.59 4.59L7 18l6-6-6-6zM16 6h2v12h-2z"></path></svg>';
                    return this.active ? '<a>' + _last + '</a>' : _last;
                case 'fill':
                    const data = this.slice;
                    return '<span>' + (data[0] + 1) + ' - ' + data[1] + ' of ' + content.length + '</span>';
            }
        }
    });
}

function wosRLConflictToggle(element, id) {
    const target = $('tr[data-name=wosrl-conflict-' + id + ']', $(element).closest('#wosRLList'));
    $.when(target.toggle('display')).then(function() {
        const text = target.is(':hidden') ? $(element).data('view') : $(element).data('hide');
        $(element).html('<b>!</b> ' + text);
    });
    return false;
}