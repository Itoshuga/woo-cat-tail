(function ($) {
    var $overlay, $title, $search, $results, $confirm, $cancel, $close;
    var currentTerm = null;
    var currentSlot = "bottom";
    var selectedTemplate = null;
    var loading = false;
    var lastQuery = "";

    function getSlotLabel(slot) {
        if (slot === "top") {
            return (CatTailAdmin.strings && CatTailAdmin.strings.slot_top) || "Top block";
        }
        return (CatTailAdmin.strings && CatTailAdmin.strings.slot_bottom) || "Bottom block";
    }

    function openModal(termId, slot) {
        currentTerm = parseInt(termId, 10) || null;
        currentSlot = slot === "top" ? "top" : "bottom";
        selectedTemplate = null;
        lastQuery = "";

        if (!$overlay || !$overlay.length) return;

        $results.empty();
        $confirm.prop("disabled", true);
        $search.val("");
        $search.attr("placeholder", (CatTailAdmin.strings && CatTailAdmin.strings.search_ph) || "Search a section...");

        var baseTitle = (CatTailAdmin.strings && CatTailAdmin.strings.modal_title) || "Link an Elementor section";
        $title.text(baseTitle + " - " + getSlotLabel(currentSlot));

        $overlay.show();
        $search.focus();

        fetchList("", 1, false);
    }

    function closeModal() {
        if (!$overlay) return;
        $overlay.hide();
        currentTerm = null;
        currentSlot = "bottom";
        selectedTemplate = null;
        lastQuery = "";
    }

    function renderRows(items) {
        var frag = $(document.createDocumentFragment());
        items.forEach(function (item) {
            var row = $('<button type="button" class="cat-tail-row"/>')
                .attr("data-id", item.id)
                .text("#" + item.id + " - " + item.title);
            frag.append(row);
        });
        return frag;
    }

    function showLoadMore(nextPage) {
        var moreLabel = (CatTailAdmin.strings && CatTailAdmin.strings.load_more) || "More...";
        var $more = $('<button type="button" class="button cat-tail-more" />')
            .attr("data-next", nextPage)
            .text(moreLabel);
        $results.append($more);
    }

    function fetchList(query, page, append) {
        if (loading) return;
        loading = true;

        if (!append) {
            $results
                .attr("aria-busy", "true")
                .html('<div class="cat-tail-loading">' + CatTailAdmin.strings.loading + "</div>");
        } else {
            $results.find(".cat-tail-more").remove();
        }

        $.post(CatTailAdmin.ajax_url, {
            action: "cat_tail_list_sections",
            nonce: CatTailAdmin.nonce,
            search: query || "",
            page: page || 1
        }).done(function (res) {
            if (!append) {
                $results.empty().attr("aria-busy", "false");
            }

            if (!res || !res.success || !res.data || !res.data.items || !res.data.items.length) {
                if (!append) {
                    $results.html('<div class="cat-tail-nores">' + CatTailAdmin.strings.no_results + "</div>");
                }
                return;
            }

            $results.append(renderRows(res.data.items));

            if (res.data.page < res.data.max_pages) {
                showLoadMore(res.data.page + 1);
            }
        }).always(function () {
            loading = false;
            $results.attr("aria-busy", "false");
        });
    }

    function assignSelected() {
        if (!currentTerm || !selectedTemplate) return;

        $confirm.prop("disabled", true);

        $.post(CatTailAdmin.ajax_url, {
            action: "cat_tail_assign_section",
            nonce: CatTailAdmin.nonce,
            term_id: currentTerm,
            template_id: selectedTemplate,
            slot: currentSlot
        }).done(function (res) {
            if (res && res.success) {
                window.location.reload();
            } else {
                alert((CatTailAdmin.strings && CatTailAdmin.strings.assign_error) || "An error occurred while linking.");
            }
        }).fail(function () {
            alert((CatTailAdmin.strings && CatTailAdmin.strings.assign_error) || "An error occurred while linking.");
        }).always(function () {
            $confirm.prop("disabled", false);
        });
    }

    $(document).on("click", ".ims-open-replace-modal", function (e) {
        e.preventDefault();
        var termId = $(this).data("term");
        var slot = $(this).data("slot") || "bottom";
        openModal(termId, slot);
    });

    $(function () {
        $overlay = $("#cat-tail-modal-overlay");
        $title = $("#cat-tail-modal-title");
        $search = $("#cat-tail-search");
        $results = $("#cat-tail-results");
        $confirm = $(".cat-tail-confirm");
        $cancel = $(".cat-tail-cancel");
        $close = $(".cat-tail-modal__close");

        $results.on("click", ".cat-tail-row", function () {
            $results.find(".cat-tail-row.is-active").removeClass("is-active");
            $(this).addClass("is-active");
            selectedTemplate = parseInt($(this).attr("data-id"), 10);
            $confirm.prop("disabled", !selectedTemplate);
        });

        $results.on("click", ".cat-tail-more", function (e) {
            e.preventDefault();
            var nextPage = parseInt($(this).attr("data-next"), 10) || 2;
            fetchList(lastQuery, nextPage, true);
        });

        var tId = null;
        $search.on("input", function () {
            var q = $(this).val() || "";
            lastQuery = q;

            if (tId) clearTimeout(tId);
            tId = setTimeout(function () {
                fetchList(q, 1, false);
            }, 250);
        });

        $confirm.on("click", function (e) {
            e.preventDefault();
            assignSelected();
        });

        function handleClose(e) {
            e.preventDefault();
            closeModal();
        }

        $cancel.on("click", handleClose);
        $close.on("click", handleClose);

        $(document).on("keydown", function (e) {
            if (e.key === "Escape" && $overlay.is(":visible")) closeModal();
        });

        $overlay.on("click", function (e) {
            if (e.target === $overlay[0]) closeModal();
        });
    });
})(jQuery);
