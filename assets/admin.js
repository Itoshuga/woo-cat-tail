(function($) {
    var $overlay, $modal, $title, $search, $results, $confirm, $cancel, $close;
    var currentTerm = null;
    var selectedTemplate = null;
    var loading = false;
    var lastQuery = '';

    function openModal(termId) {
        currentTerm = parseInt(termId, 10) || null;
        selectedTemplate = null;
        lastQuery = '';

        if (!$overlay || !$overlay.length) return;
        $results.empty();
        $confirm.prop('disabled', true);
        $search.val('');
        $overlay.show();
        $search.focus();

        fetchList('');
    }

    function closeModal() {
        if (!$overlay) return;
        $overlay.hide();
        currentTerm = null;
        selectedTemplate = null;
        lastQuery = '';
    }

    function renderRows(items) {
        var frag = $(document.createDocumentFragment());
        items.forEach(function(item) {
            var row = $('<button type="button" class="cat-tail-row"/>')
                .attr('data-id', item.id)
                .text('#' + item.id + ' – ' + item.title);
            frag.append(row);
        });
        return frag;
    }

    function fetchList(q, page) {
        if (loading) return;
        loading = true;

        $results.attr('aria-busy', 'true').html('<div class="cat-tail-loading">' + CatTailAdmin.strings.loading + '</div>');

        $.post(CatTailAdmin.ajax_url, {
            action: 'cat_tail_list_sections',
            nonce: CatTailAdmin.nonce,
            search: q || '',
            page: page || 1
        }).done(function(res) {
            $results.empty().attr('aria-busy', 'false');

            if (!res || !res.success || !res.data || !res.data.items || !res.data.items.length) {
                $results.html('<div class="cat-tail-nores">' + CatTailAdmin.strings.no_results + '</div>');
                return;
            }

            $results.append(renderRows(res.data.items));

            // Simple "load more" if more pages
            if (res.data.page < res.data.max_pages) {
                var nextPage = res.data.page + 1;
                var $more = $('<button type="button" class="button cat-tail-more" />').text('Plus…');
                $more.on('click', function() {
                    if (loading) return;
                    loading = true;
                    $.post(CatTailAdmin.ajax_url, {
                        action: 'cat_tail_list_sections',
                        nonce: CatTailAdmin.nonce,
                        search: lastQuery,
                        page: nextPage
                    }).done(function(res2) {
                        if (res2 && res2.success && res2.data && res2.data.items && res2.data.items.length) {
                            $results.find('.cat-tail-more').remove();
                            $results.append(renderRows(res2.data.items));
                            if (res2.data.page < res2.data.max_pages) {
                                nextPage = res2.data.page + 1;
                                var $more2 = $('<button type="button" class="button cat-tail-more" />').text('Plus…');
                                $results.append($more2);
                                $more2.on('click', function() { $more2.trigger('click'); });
                            }
                        }
                    }).always(function() { loading = false; });
                });
                $results.append($more);
            }
        }).always(function() { loading = false; });
    }

    function assignSelected() {
        if (!currentTerm || !selectedTemplate) return;

        $confirm.prop('disabled', true);

        $.post(CatTailAdmin.ajax_url, {
            action: 'cat_tail_assign_section',
            nonce: CatTailAdmin.nonce,
            term_id: currentTerm,
            template_id: selectedTemplate
        }).done(function(res) {
            if (res && res.success) {
                window.location.reload();
            } else {
                alert('Erreur lors de la liaison.');
            }
        }).fail(function() {
            alert('Erreur lors de la liaison.');
        }).always(function() {
            $confirm.prop('disabled', false);
        });
    }

    // Open modal from buttons
    $(document).on('click', '.ims-open-replace-modal', function(e) {
        e.preventDefault();
        var termId = $(this).data('term');
        openModal(termId);
    });

    $(function() {
        $overlay = $('#cat-tail-modal-overlay');
        $modal = $('#cat-tail-modal');
        $title = $('#cat-tail-modal-title');
        $search = $('#cat-tail-search');
        $results = $('#cat-tail-results');
        $confirm = $('.cat-tail-confirm');
        $cancel = $('.cat-tail-cancel');
        $close = $('.cat-tail-modal__close');

        // Row selection
        $results.on('click', '.cat-tail-row', function() {
            $results.find('.cat-tail-row.is-active').removeClass('is-active');
            $(this).addClass('is-active');
            selectedTemplate = parseInt($(this).attr('data-id'), 10);
            $confirm.prop('disabled', !selectedTemplate);
        });

        // Search (debounce)
        var tId = null;
        $search.on('input', function() {
            var q = $(this).val() || '';
            lastQuery = q;
            if (tId) clearTimeout(tId);
            tId = setTimeout(function() {
                fetchList(q, 1);
            }, 250);
        });

        $confirm.on('click', function(e) {
            e.preventDefault();
            assignSelected();
        });

        function handleClose(e) {
            e.preventDefault();
            closeModal();
        }
        $cancel.on('click', handleClose);
        $close.on('click', handleClose);

        // ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $overlay.is(':visible')) closeModal();
        });

        // Click outside modal
        $overlay.on('click', function(e) {
            if (e.target === $overlay[0]) closeModal();
        });
    });

})(jQuery);