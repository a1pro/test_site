jQuery(document).ready(function($) {
    // scroll to error message if any
    var errors = $(".errors:visible:first,.error:visible:first");
    if (errors.length) 
        $("html, body").scrollTop(Math.floor(errors.offset().top));
    
    
    /////
    // make form act as ajax login form
    // it will just submit form to aMember's login url
    // and handle login response
    // options - 
    // success: callback to be called on succes
    //    by default - redirect or page reload
    // failure: callback to be called on failure
    //    by default - display error to $("ul.errors")    
    /////
    $.fn.amAjaxLoginForm = function(options)
    {
	if (typeof options == 'function') {
		options = {success: options};
	}
        options = $.extend(true, {
            success: function(response, frm) { 
                if (response.url) window.location = response.url;
                else if (response.reload) window.location.reload(true);
            },
            error: function(response, frm) {
                var errUl = $("ul.errors.am-login-errors");
                if (!errUl.length)
                    frm.before(errUl = $("<ul class='errors am-login-errors'></ul>"));
                else 
                    errUl.empty();
                for (i in response.error)
                    errUl.append("<li>"+response.error[i]+"</li>");
                // show recaptcha if enabled
                if (response.recaptcha_key)
                {
                    $("#recaptcha-row").show();
                    if (typeof Recaptcha == "undefined")
                    {
                        $.getScript('http://www.google.com/recaptcha/api/js/recaptcha_ajax.js', function(){
                            frm.data('recaptcha', Recaptcha.create(response.recaptcha_key, 'recaptcha-element'));
                        });
                    } else {
                        if (!frm.data('recaptcha'))
                        {
                            frm.data('recaptcha', Recaptcha.create(response.recaptcha_key, 'recaptcha-element'));
                        } else 
                            frm.data('recaptcha').reload();
                    }
                } else {
                    $("#recaptcha-row").hide();
                }
            }
	}, options);
        this.each(function() {
            $(this).submit(function(){
                var frm = $(this);
                $.post(frm.attr("action"), frm.serialize(), function(response, status, request){
                    if ((request.status != '200') && (request.status != 200))
                        response = {ok: false, error: ["ajax request error: " + request.status + ': ' + request.statusText ]};
                    if (!response)
                        response = {ok: false, error: ["ajax request error: empty response"]};
                    if (!response || !response.ok)
                    {
                        if (!response.error) response.error = ["Login failed"];
                        options.error(response, frm);
                    } else {
                        options.success(response, frm);
                    }
                });
                return false;
            })
        });
    }
    $(".am-login-form form").amAjaxLoginForm();
    
    /////
    // make form act as ajax login form
    // it will just submit form to aMember's login url
    // and handle login response
    // options - 
    // success: callback to be called on succes
    //    by default - redirect or page reload
    // failure: callback to be called on failure
    //    by default - display error to $("ul.errors")    
    /////
    $.fn.amAjaxSendPassForm = function(options)
    {
	if (typeof options == 'function') {
		options = {success: options};
	}
        options = $.extend(true, {
            successContainer: $("success", this),
            success: function(response, frm) { 
                if (response.url) window.location = response.url;
                else if (response.reload) window.location.reload(true);
                else {
                    if (!options.successContainer.length)
                    {
                        frm.before(options.successContainer = $("<div class='success-message'></div>"));
                    }
                    $("ul.errors.am-sendpass-errors").remove();
                    options.successContainer.html(response.error[0]);
                    $(":submit", frm).prop("disabled", "disabled");
                }
            },
            error: function(response, frm) {
                var errUl = $("ul.errors.am-sendpass-errors");
                if (!errUl.length)
                    frm.before(errUl = $("<ul class='errors am-sendpass-errors'></ul"));
                else 
                    errUl.empty();
                for (i in response.error)
                    errUl.append("<li>"+response.error[i]+"</li>");
            }
	}, options);
        this.each(function() {
            $(this).submit(function(){
                var frm = $(this);
                $.post(frm.attr("action"), frm.serialize(), function(response, status, request){
                    if ((request.status != '200') && (request.status != 200))
                        response = {ok: false, error: ["ajax request error: " + request.status + ': ' + request.statusText ]};
                    if (!response)
                        response = {ok: false, error: ["ajax request error: empty response"]};
                    if (!response || !response.ok)
                    {
                        if (!response.error) response.error = ["Error while e-mailing lost password"];
                        options.error(response, frm);
                    } else {
                        options.success(response, frm);
                    }
                });
                return false;
            })
        });
    }
    $(".am-sendpass-form form").amAjaxSendPassForm();

    // cancel form support hooks (member/payment-history)
    $("a.cancel-subscription").click(function(event){
        event.stopPropagation();
        $(".cancel-subscription-popup").show(500).data('href', this.href);
        return false;
    });
    $("#cancel-subscription-yes").click(function(){
        window.location.href = $(".cancel-subscription-popup").data('href');
    });
    $("#cancel-subscription-no").click(function(){
        $(".cancel-subscription-popup").hide(300);
    });
    // end of cancel form
    // upgrade form
    $("a.upgrade-subscription").click(function(event){
        event.stopPropagation();
        $(".upgrade-subscription-popup-"+$(this).data('invoice_id')).show(500).data('href', this.href);
        return false;
    });
    $(".upgrade-subscription-no").click(function(){
        $(".upgrade-subscription-popup").hide(300);
    });
    // end of upgrade 
    
    $.fn.ajaxLink = function() {
        $(this).each(function(){
            $(this).click(function(){
                var $link = $(this);
                $.get($(this).attr('href'), {}, function(html){
                    $("body").append("<div id='mask'></div>");
                    $("#popup").find("#popup-title").empty().append($link.prop('title'));
                    
                    $('#popup').css({
                        top: $(window).scrollTop()+ 100,
                        left: $('body').width()/2 - $('#popup').outerWidth(true)/2
                    });
                    
                    $("#popup-content").empty().append(html);
                    $("#popup").show(300);
                    $("#popup-close").click(function(){
                        $("#mask").remove();
                        $("#popup").hide(300, function(){
                            $("#popup-title, #popup-content").empty();
                        });
                    })
                })
                return false;
            })
        })
    }
    
    $('.ajax-link').ajaxLink();
    
});
