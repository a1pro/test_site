
/*
 * translate
 *
 * allow to translate text in input
 *
 */

;
(function($) {
    $.fn.translate = function(inParam) {
        return this.each(function(){
            var translate = this;
            var $translate = $(translate);
            if ($(translate).data('initialized')) {
                return;
            } else {
                if (this.type != 'text' && this.type != 'textarea') {
                    throw new Error('Element should be text or \
textarea in order to use translator for it. [' + this.type + '] given.');
                }
                $(translate).data('initialized', 1);
            }

            var param = $.extend({
                }, inParam)


            var $div = $('<div style="display:none;"></div>');

            var $a = $('<a href="javascript:;"></a>');
            $a.click(function(){
                $div.dialog('open');
            })


            $(translate).after($a);
            $a.before(' ');
            $('body').append($div);


            $translate.bind('change', function(){
                init();
            })

            init();

            function init()
            {
                synchronize($translate.val());
            }
            
            function synchronize(text) {
                $.ajax({
                    type: 'post',
                    data : {
                        'text' : text.replace(/\r?\n/g, "\r\n")
                    },
                    url : window.rootUrl + '/admin-trans-local/synchronize',
                    dataType : 'json',
                    success : function(data, textStatus, XMLHttpRequest) {
                        updateStat(data.stat);
                        updateForm(data.form);
                    }
                });
                
            }

            function updateStat(data)
            {
                data.total && $a.empty().append('Translate (' + data.translated + '/' + data.total + ')');
            }

            function updateForm(data)
            {
                $div.empty().append(data);
            }

            $div.dialog({
                autoOpen: false,
                modal : true,
                title : "Translations",
                width : 600,
                position : ['center', 100],
                buttons: {
                    "Save" : function() {
                        $div.find('form').ajaxSubmit({
                            success : function() {
                                $div.dialog('close');
                                init();
                            }
                        });
                    },
                    "Cancel" : function() {
                        $(this).dialog("close");
                    }
                }
            });
        

        })
    }
})(jQuery);