jQuery(function() {
    var $sortable=jQuery("#tblSortable .table-body");
    $sortable.sortable({
        tolerance:'intersect'
        , cursor:'move'
        , items:'tr'
        , placeholder:'ui-state-highlight'
        , nested: 'tbody'
        , update: function(event, ui) {
            var orders=$sortable.sortable('serialize');
            var data={action: 'TCMP_changeOrder', nonce: ajax_vars.nonce, order: orders};
            jQuery.post(ajaxurl, data, function(result) {
                console.log(result);
            });
        }
    });
    $sortable.disableSelection();
});

jQuery(function() {
    var href = jQuery("#tcmpRedirect").attr("href") || [];
    if (href.length > 0) {
        window.location.replace(href);
    }
});
