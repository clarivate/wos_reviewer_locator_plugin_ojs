/**
 * plugins/generic/wosReviewerLocator/js/wosrl.js
 *
 * Copyright (c) 2025 Clarivate
 * Distributed under the GNU GPL v3.
 *
 * wosReviewerLocator JS functionality
 *
 */

(function() {
    
    // Track injection state and current submission ID
    let injectionSuccessful = false;
    let currentSubmissionId = null;
    let observer = null;

    // Wait for DOM to be ready and inject
    function wosRLInit() {
        // Check if we're on the editorial workflow page
        const currentUrl = window.location.href;
        
        if (!currentUrl.includes('dashboard/editorial')) {
            return false;
        }
        
        // Extract submission ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const workflowSubmissionId = urlParams.get('workflowSubmissionId');
        
        if (!workflowSubmissionId) {
            return false; // Not on a submission workflow page
        }
        
        // Check if already injected for this submission
        if (injectionSuccessful && currentSubmissionId === workflowSubmissionId) {
            return true;
        }
        
        // Reset state if this is a different submission
        if (currentSubmissionId !== workflowSubmissionId) {
            injectionSuccessful = false;
            currentSubmissionId = workflowSubmissionId;
            // Remove any existing container from previous submission
            const existingContainer = document.getElementById('wosReviewerLocator');
            if (existingContainer) {
                existingContainer.remove();
            }
        }
        
        // Find reviewer-manager element
        const reviewerManagerElement = document.querySelector('[data-cy="reviewer-manager"]');
        if (!reviewerManagerElement) {
            return false; // Not ready yet
        }
        
        // Check if config is available
        if (!window.wosReviewerLocatorConfig || !window.wosReviewerLocatorConfig.ready) {
            return false; // Config not loaded yet
        }

        // If we have config but no templateUrl, build it dynamically
        if (!window.wosReviewerLocatorConfig.templateUrl) {
            const urlParams = new URLSearchParams(window.location.search);
            const submissionId = urlParams.get('workflowSubmissionId');
            const workflowMenuKey = urlParams.get('workflowMenuKey');

            if (submissionId) {
                // Extract stage ID from workflowMenuKey (format: workflow_3_1)
                let stageId = null;
                if (workflowMenuKey) {
                    const match = workflowMenuKey.match(/workflow_(\d+)_(\d+)/);
                    if (match) {
                        stageId = match[1]; // First number is usually stage ID
                    }
                }

                // Use the base URL provided by PHP if available
                if (window.wosReviewerLocatorConfig.baseTemplateUrl) {
                    const separator = window.wosReviewerLocatorConfig.baseTemplateUrl.includes('?') ? '&' : '?';
                    const templateUrl = window.wosReviewerLocatorConfig.baseTemplateUrl +
                        separator + 'submissionId=' + submissionId +
                        (stageId ? '&stageId=' + stageId : '');
                    window.wosReviewerLocatorConfig.templateUrl = templateUrl;
                } else {
                    // Fallback: try to construct URL (might not work correctly)
                    const baseUrl = window.location.origin + window.location.pathname.replace(/\/dashboard\/editorial.*$/, '');
                    const templateUrl = baseUrl + '/wosrl/getTemplate?submissionId=' + submissionId + (stageId ? '&stageId=' + stageId : '');
                    window.wosReviewerLocatorConfig.templateUrl = templateUrl;
                }
            } else {
                return false; // No submission ID in URL
            }
        }
        
        // Inject the plugin UI
        const wosContainer = document.createElement('div');
        wosContainer.id = 'wosReviewerLocator';
        
        // Insert after reviewer-manager
        if (reviewerManagerElement.nextSibling) {
            reviewerManagerElement.parentNode.insertBefore(wosContainer, reviewerManagerElement.nextSibling);
        } else {
            reviewerManagerElement.parentNode.appendChild(wosContainer);
        }
        
        wosRLTemplate();
        
        // Mark as successfully injected
        injectionSuccessful = true;
        return true;
    }

    function wosRLTemplate() {
        const contentDiv = document.getElementById('wosReviewerLocator');
        // Load grid.tpl template content directly
        fetch(window.wosReviewerLocatorConfig.templateUrl, {
            method: 'GET',
            credentials: 'same-origin'
        }).then(response => response.text()).then(htmlContent => {
            // Just load the HTML content directly without any processing
            contentDiv.innerHTML = htmlContent;
            
            // Initialize the toggle functionality after template is loaded
            const wrapper = $('#wosRLHeader');
            $('a.wosrl-toggle', wrapper).on('click', function() {
                $(this).toggleClass('closed');
                $('a#wosRLSearch', wrapper).toggleClass('pkp_helpers_display_none');
                $('#wosRLGrid').toggleClass('pkp_helpers_display_none');
                return false;
            });
            
            // Check if we have a token and should load the list immediately
            const gridContainer = $('.pkp_controllers_grid', contentDiv);
            const hasToken = gridContainer.data('has-token') === 'true' || gridContainer.data('has-token') === true;
            const pageUrl = gridContainer.data('page-url');
            
            if (hasToken && pageUrl) {
                wosRLList(pageUrl);
            }
        }).catch(error => {
            contentDiv.innerHTML = '<div class="wosrl-error">Error loading template</div>';
        });
    }
    
    // Simple and efficient monitoring approach
    function wosRLMonitoring() {
        // Single MutationObserver to watch for DOM changes
        if (window.MutationObserver) {
            observer = new MutationObserver(function(mutations) {
                // Only check if we haven't already injected successfully
                if (!injectionSuccessful) {
                    wosRLInit();
                }
            });
            
            // Start observing when body is ready
            if (document.body) {
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            } else {
                // Wait for body
                setTimeout(wosRLMonitoring, 100);
                return;
            }
        }
        
        // Simple URL monitoring for navigation
        let lastUrl = window.location.href;
        setInterval(function() {
            const currentUrl = window.location.href;
            if (currentUrl !== lastUrl) {
                lastUrl = currentUrl;
                // Reset state and try injection
                injectionSuccessful = false;
                currentSubmissionId = null;
                // Clear the cached template URL so it gets rebuilt for new submission
                if (window.wosReviewerLocatorConfig) {
                    delete window.wosReviewerLocatorConfig.templateUrl;
                }
                setTimeout(wosRLInit, 100);
            }
        }, 250);
    }
    
    // Initial setup
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            wosRLInit();
            wosRLMonitoring();
        });
    } else {
        wosRLInit();
        wosRLMonitoring();
    }

})();

function wosRLList(page_url) {
    const wrapper = $('#wosRLGrid');
    $('a#wosRLSearch', wrapper.parent()).addClass('wosrl-hidden');
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
                $('a#wosRLSearch', wrapper.parent()).removeClass('wosrl-hidden');
                wrapper.html('<div class="wosrl-placeholder">' + data.content + '</div>');
            }
        },
        error: function() {
            $('a#wosRLSearch', wrapper.parent()).removeClass('wosrl-hidden');
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