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

    jQuery('#search_form .fa-plus-square-o').click(function (e) {

    })

    jQuery('#search_form #attribute_terms').select2();

    // jQuery.jstree.reference('#search_form #category').get_checked();
});
