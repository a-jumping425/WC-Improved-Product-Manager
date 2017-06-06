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

    /**
     * Add attribute term to selected list
     */
    jQuery('#search_form .fa-plus-square-o').click(function (e) {
        var data = jQuery('#search_form #attribute_terms').select2('data');
        var attribute = data[0].element.attributes[0].nodeValue;
        var id = data[0].id;
        var text = data[0].text;

        var li = '<li data-id="'+ id +'"><span class="label"><strong>'+ attribute +'</strong> => '+ text +'Attribute</span><span><i class="fa fa-minus-square-o"></i></span></li>';
        jQuery('#search_form #selected_attributes').append(li);
    });

    /**
     * Remove attribute term from selected list
     */
    jQuery('body').on('click', '#search_form #selected_attributes .fa-minus-square-o', function (e) {
        jQuery(this).parents('#selected_attributes li').remove();
    });

    jQuery('#search_form #attribute_terms').select2({
        placeholder: "Select a attribute"
    });

    // jQuery.jstree.reference('#search_form #category').get_checked();
});
