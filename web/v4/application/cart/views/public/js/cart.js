/* Shopping cart javascript code */

var cart = {
    items: {}
    ,getUrl: function(action)
    {
        return window.rootUrl + '/cart/index/' + action;
    }
    ,getBillingPlanId: function(item_id)
    {
        return jQuery(":input[name='plan["+item_id+"]']").val();
    }
    ,add: function(item_id, qty, item_type)
    {
        var block = jQuery("#block-cart-basket").parents(".block");
        if (!block.length)
            block = jQuery("<div id='am-cart-basket-container'/>");
        block.load(
            this.getUrl('ajax-add'),
            {
              item_id : item_id,
              billing_plan_id : this.getBillingPlanId(item_id),
              qty : qty ? qty : 1,
              item_type: item_type ? item_type: 'product'
            },
            function()
            {
            }
        );
        return this;
    }
    ,addAndCheckout: function(item_id, qty, item_type)
    {
        window.location =
            this.getUrl('add-and-checkout') +
            '?item_id=' + encodeURIComponent(item_id) +
            '&billing_plan_id=' + this.getBillingPlanId(item_id) +
            '&qty=' + encodeURIComponent(qty ? qty : 1) +
            '&item_type=' + encodeURIComponent(item_type ? item_type : 'product') +
            '&b=' + encodeURIComponent(window.location.pathname + window.location.search);
        return this;
    }
    ,goCategory: function(category_id)
    {
        window.location = 
            this.getUrl('index') + '?c=' + category_id;
    }
    ,detectRootUrl : function()
    {
        var t = document.getElementsByTagName("script");
        var js = t[ t.length - 1 ];
        if (js && js.src)
        {
            return js.src.replace(new RegExp('/application/cart/views/public/js/cart.js'), '');
        }
    }
    , init : function() 
    {
        if (!window.rootUrl) window.rootUrl = cart.detectRootUrl();
        var loadRunned = 0;
        if (typeof(jQuery) == 'undefined') {
            var jqueryUrl = window.rootUrl + "/application/default/views/public/js/jquery/jquery.js";
            if (! loadRunned++) 
            {
                document.write(x = "<scr" + "ipt type=\"text/javascript\" src=\""+jqueryUrl+"\"></scr" + "ipt>");
            }
            setTimeout("cart.init()", 200);
        } else {
            jQuery(function() {  
                cart.initCategorySelect();
            });
        }
    }
    ,initCategorySelect : function() {
        jQuery("select#product-category").change(function(){
           cart.goCategory(jQuery(this).val());
        });
    }
};

cart.init();