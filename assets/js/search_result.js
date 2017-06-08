jQuery(function () {
    jQuery('#product_list').DataTable({
        "searching": false,
        "ordering": false,
        "processing": true,
        "serverSide": true,
        "ajax": ajaxurl + '?action=wc_ipm_searched_products'
    });
});
