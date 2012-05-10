<script language="JavaScript">
    function recalculate_tax(){
                // Empty field;
                if(!jQuery("#amount").val()) return;
                data = {'do'            :   'calculate_tax',
                        'product_id'    :   jQuery("#pid").val(),
                        'member_id'     :   jQuery("#member_id").val(),
                        'amount'        :   jQuery("#amount").val(),
                        'incl_tax'      :   jQuery("#incl_tax:checked").val()
                        }
                jQuery.ajax({
                    url         :   "ajax_cnt.php",
                    cache       :   false,
                    type        :   "POST",
                    dataType    :   "json",
                    data        :   data,
                    success     :   function(resp, textStatus){
                        // alert(resp.tax);
                        // Got Value Now set tax;
                        jQuery("#tax_amount").val(resp.tax);
                    }
                }
                );
    }

    $(document).ready(function () {
        var timeout = null;
        jQuery("#amount").keyup(
                function (event){
                    // Make ajax call to calculate tax;
                clearTimeout(timeout);
                timeout = setTimeout(recalculate_tax, 1*1000);

                });
         jQuery("#amount").keydown(
                function (event){
                    clearTimeout(timeout);
                });
         jQuery("#amount").change(function (event){
                recalculate_tax();
         });
         jQuery("#incl_tax").change(function (event){
                recalculate_tax();
         });
    });


</script>