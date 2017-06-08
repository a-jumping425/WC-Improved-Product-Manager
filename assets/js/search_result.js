jQuery(function () {
    jQuery('#product_list').DataTable({
        "searching": false,
        "ordering": false,
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": ajaxurl + '?action=wc_ipm_searched_products',
            "data": {}
        },
        "columns": [
            {
                data: 'thumbnail',
                className: 'thumbnail',
                width: '60px'
            },
            {
                data: 'name',
                className: 'name',
                width: '20%'
            },
            {
                data: 'sku',
                className: 'sku'
            },
            // {
            //     data: 'stock',
            //     className: 'stock'
            // },
            {
                data: 'price',
                className: 'price'
            },
            {
                data: 'categories',
                className: 'categories'
            },
            {
                data: 'attributes',
                className: 'attributes'
            },
            {
                data: 'date',
                className: 'date'
            }
        ]
    });
});
