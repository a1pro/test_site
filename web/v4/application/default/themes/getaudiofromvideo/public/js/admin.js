$(document).ready(function(){
    $("input#user-lookup").autocomplete({
        minLength: 2,
        source: window.rootUrl + "/admin-users/autocomplete"
    });
    
   
    $("._collapsible_ ._head_").live("click", function(){
        $(this).closest("._item_").toggleClass('_open_');
    });
    $(".grid-wrap").ngrid();
    
    $('input.datepicker').datepicker({
        dateFormat:window.uiDateFormat,
        changeMonth: true,
        changeYear: true
    });
    
    $('#admin-login').submit(function(){
        //$('#admin-login').hide();
        $.ajax({
            global: false,
            type : 'POST',
            url: $('#admin-login form').attr('action'), 
            data: $('#admin-login input').serializeArray(),
            complete: function (response)
            {
                data = $.parseJSON(response.responseText);
                if (!data) // bad response, redirect to login page
                {
                    window.location.href = window.rootUrl + '/admin'
                    return;
                }
                if (data.ok)
                {
                    $('#admin-login').dialog('destroy');
                } else {
                    $("#admin-login .error").text(data.err);
                }
            }
            });
        $('#admin-login input[name="passwd"]').val('');
        return false;
    });

    function displayLoginForm()
    {
        $('#admin-login').dialog({
            modal: true,
            title: "Administrator Login",
            width: '500',
            height: '400',
        });
    }

    $('body').ajaxComplete(function(event,request, settings){
        if (request.status == 402) 
        {
            var vars = $.parseJSON(request.responseText);
            $('#admin-login .error').text(vars['err'] ? vars['err'] : null);
            displayLoginForm();
        }

        $('div#flash-message').ajaxStart(function(){
            var div = this;
            div.ajaxActive = true;
            setTimeout(function(){
                if (div.ajaxActive) $('p.loading', div).show()
                    },
            200);
        })
        $('div#flash-message').ajaxStop(function(){
            this.ajaxActive = false;
            $('p.loading', this).hide();
        })
    })


    $("a.email-template").live('click', function() {
        var $div = $('<div style="display:none;" id="email-template-popup"></div>');
        $('body').append($div);
        
        var url = this.href;
        var actionUrl = url.replace(/\?.*$/, '');
        var getParams = url.replace(/^.*?\?/, '').split('&');
        var data = {};
        for ( var i in getParams ) {
            var nameValue = getParams[i].split('=');
            data[decodeURIComponent(nameValue[0])] = decodeURIComponent(nameValue[1]);
        }
        
        $div.dialog({
            autoOpen: false,
            modal : true,
            title : "Email Template",
            width : 800,
            position : ['center', 100],
            buttons: {
                "Save" : function() {
                    $div.find('form#EmailTemplate').ajaxSubmit({
                        success : function() {
                            $div.dialog('close');
                        },
                        beforeSerialize : function(){
                            if(CKEDITOR && CKEDITOR != 'undefined')
                                for ( instance in CKEDITOR.instances )
                                    CKEDITOR.instances[instance].updateElement();

                        }
                    });
                },
                "Cancel" : function() {
                    $(this).dialog("close");
                }
            },
            close : function() {
                $div.remove();
            }
        });
            
        $.ajax({
            type: 'post',
            'data' : data,
            'url' : actionUrl,
            dataType : 'html',
            success : function(data, textStatus, XMLHttpRequest) {
                $div.empty().append(data);
                $div.dialog("open");
            }
        });
        
        return false;
    })

    $(".admin-menu").adminMenu(window.amActiveMenuID);
    $(".magicselect").magicSelect();
    if (window.amLangCount>1) {
        $('.translate').translate();
    }
    $('input.options-editor').optionsEditor();
    $('.upload').upload();
    $('.reupload').reupload();
    $('input[type=file].styled').fileStyle();
    $('body').ajaxComplete(function(){
        //allow ajax handler to do needed tasks before convert elements
        setTimeout(function(){
            $(".magicselect").magicSelect();
            if (window.amLangCount>1) {
                $('.translate').translate();
            }
            $('input.options-editor').optionsEditor();
            $('.upload').upload();
            $('.reupload').reupload();
            $('input[type=file].styled').fileStyle();
        }, 100);
    })
    
    // scroll to error message if any
    var errors = $(".errors:visible:first,.error:visible:first");
    if (errors.length) 
        $("html, body").scrollTop(Math.floor(errors.offset().top));
});

function flashError(msg){
    return flash(msg, 'error', 5000);
};
function flashMessage(msg){
    return flash(msg, 'message', 1000);
};
function flash(msg, msgClass, timeout)
{
    if (!$('#flash-message').length)
        $('body').append('<div id="flash-message"></div>');
    lastId = Math.ceil(10000*Math.random());
    $('#flash-message').append("<p id='flashMsg-"+lastId+"' class='"+msgClass+"'>"+msg+"</p>");
    if (timeout)
        setTimeout(function(id){
            $('#flashMsg-'+id).remove();
        }, timeout, lastId);
}

$.fn.serializeAssoc = function()
{
    var res = {};
    var arr = $(this).serializeArray();
    for (i in arr)
        res[ arr[i]['name'] ] = arr[i]['value'];
    return res;
}

function filterHtml(source)
{
    HTMLReg.disablePositioning = true;
    HTMLReg.validateHTML = false;
    return HTMLReg.parse(source);
}

function initCkeditor(textareaId, options)
{
    var placeholderToolbar = null;
    if (!options) options = {};
    if (options.placeholder_items)
    {
        placeholderToolbar = {
            name: 'amember', 
            items: ['CreatePlaceholder']
        };
    }
    var defaultOptions = {
        autoGrow_maxHeight : 800
        ,
        baseHref: window.rootUrl
        ,
        extraPlugins : 'autogrow,placeholder'
        ,
        removePlugins : 'resize'       
        ,
        customConfig : false
        ,
        contentsCss: []
        ,
        toolbar: "Am"
        ,
        toolbar_Am: 
        [
        ,{
            name: 'basicstyles', 
            items : [ 'Bold','Italic','Strike','-','RemoveFormat' ]
        }
        ,placeholderToolbar
        ,{
            name: 'paragraph', 
            items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight']
        }
        ,{
            name: 'insert', 
            items : [ 'Link','Unlink','Image','Flash','Table','HorizontalRule','PageBreak' ]
        }
        ,{
            name: 'tools', 
            items : [ 'Maximize', 'Source', 'Templates', 'SpellChecker' ]
        }       
        ,{
            name: 'clipboard', 
            items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ]
        }
        ,'/'
        ,{
            name: 'styles', 
            items : [ 'Styles','Format','Font','FontSize','TextColor','BGColor'  ]
        }
        ]
    };
    
    return CKEDITOR.replace(textareaId, $.extend(defaultOptions, options));
}