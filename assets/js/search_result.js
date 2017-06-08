jQuery(function () {
    jQuery('#product_list').DataTable({
        "searching": false,
        "ordering": false,
        "processing": true,
        "serverSide": true,
        "ajax": "scripts/server_processing.php"
    });
});
