/**
 *
 * Modals
 *
 */
var Modal = {
    'Load' : function(title, content){
        require([
            'Magento_Ui/js/modal/alert'
        ], function(alert) {
            alert({
                title: title,
                content: content,
                clickableOverlay: true,

            });
        });
    }
}

/**
 *
 * Ajax call's
 *
 */
var WS = {

    'Ajax' : {

        /**
         * Refund method's
         */
        'Refund' : {
            'Search' : function(url)
            {
                jQuery.ajax( {
                    url: url + '/genpay/refund/request',
                    data: {form_key: window.FORM_KEY, days: jQuery('#refund-days').val()},
                    type: 'POST',
                    showLoader: true,
                }).success(function(response) {

                    if (response.success) {

                        var t = jQuery('#genpay-datatable').DataTable();

                        //Cleans up the table
                        t.clear().draw();

                        //Check the array for data, if not empty insert data else clear the table.
                        if (response.payload.data.length > 0) {
                            var i = 0;
                            // Create a new table row for all array positions
                            response.payload.data.forEach(function(item){
                                t.row.add( [
                                    item.date,
                                    item.magento_id,
                                    item.pagseguro_id,
                                    item.magento_status,
                                    '<a class="refund" data-target="refund_'+ i +'" data-block="'+item.details+'" data-id="'+item.magento_id+'" style="cursor:pointer;">Estorno total</a><br/>'+
                                    '<a class="partial-refund" data-target="refund_'+ i +'" data-block="'+item.details+'" data-value="'+item.value+'" data-id="'+item.magento_id+'" style="cursor:pointer;">Estorno parcial</a>', 
                                ] );
                                //Adjust column width
                                t.columns.adjust().draw(false);
                                i++;
                            });
                        } else {
                            //Alert
                            Modal.Load('Estorno', 'Sem resultados para o período solicitado.');
                        }
                    } else {
                        //Alert
                        Modal.Load('Estorno', 'Não foi possível executar esta ação. Tente novamente mais tarde.');
                    }
                });

            },
        },
    }
}
