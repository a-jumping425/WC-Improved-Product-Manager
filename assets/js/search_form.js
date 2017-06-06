jQuery(function () {
    jQuery('#search_form #category').jstree({
        "core": {
            "themes": {
                "icons": false
            }
        },
        "checkbox": {
            "keep_selected_style": false
            // "three_state": false     // Disable sub nodes selection when checked parent
        },
        'plugins': ["checkbox"]
    });
    // jQuery.jstree.reference('#search_form #category').get_checked();
});
