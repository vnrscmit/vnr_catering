$(window).on('load', function () {

    console.log("jQuery:", $.fn.jquery);
    console.log("Select2:", typeof $.fn.select2);
    console.log("Elements:", $('.select2').length);

    if (typeof $.fn.select2 === 'function') {
        $('.select2').select2({
            width: '100%'
        });
        console.log("Select2 initialized");
    } else {
        console.error("Select2 plugin not found");
    }

});