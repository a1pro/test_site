
/*
 * Options Editor
 *
 */

;
(function($) {
    $.fn.optionsEditor = function(inParam) {
        return this.each(function(){
            var optionsEditor = this;
            var $optionsEditor = $(optionsEditor);
            var Options;
            var $input_key, $input_value, $input_default;
            var $tr = $('<tr></tr>');
            var $td = $('<td></td>');
            var $th = $('<th></th>');

            if ($(optionsEditor).data('initialized')) {
                return;
            } else {
                if (this.type != 'hidden') {
                    throw new Error('Element should be hidden in order to use optionsEditor for it. [' + this.type + '] given.');
                }
                $(optionsEditor).data('initialized', 1);
            }

            var param = $.extend({
                }, inParam)


            init();
            

            function exchangePositions(key1, key2) {
                var newOptions = new Object();
                for (var key in Options.options) {
                    if (key == key1) {
                        newOptions[key2] = Options.options[key2];
                    } else if(key == key2) {
                        newOptions[key1] = Options.options[key1];
                    } else {
                        newOptions[key] = Options.options[key];
                    }
                }
                Options.options = newOptions;
                $optionsEditor.val($.toJSON(Options));
            }

            function addNewOption(key, val, is_default) {
                Options.options[key] = val;
                if (is_default && $.inArray(key, Options['default']) == -1) {
                    Options['default'].push(key);
                }

                $optionsEditor.val($.toJSON(Options))

                var $uarr = $('<a href="javascript:;">&uarr;</a>').click(function(event) {
                    var $tr1 = $(this).parents('tr');
                    var $tr2 = $(this).parents('tr').prev();
                    if (!$tr2.hasClass('option')) return;
                    $tr1.find('td').css({
                        backgroundColor: ''
                    })
                    $tr1.find('.actions').hide();
                    $tr1.insertBefore($tr2);
                    exchangePositions($tr1.data('key'), $tr2.data('key'))
                    return false;

                });
                var $darr = $('<a href="javascript:;">&darr;</a>').click(function(event) {
                    var $tr1 = $(this).parents('tr');
                    var $tr2 = $(this).parents('tr').next();
                    if (!$tr2.hasClass('option')) return;
                    $tr1.find('td').css({
                        backgroundColor: ''
                    })
                    $tr1.find('.actions').hide();
                    $tr1.insertAfter($tr2);
                    exchangePositions($tr1.data('key'), $tr2.data('key'))
                    return false;

                });

                var $del = $('<a href="javascript:;">x</a>').click(function(event) {
                    var $tr = $(this).parents('tr');
                    var key = $tr.data('key');
                    delete Options.options[key];
                    var index = $.inArray(key, Options['default']);
                    if (index != -1) {
                        Options['default'].splice(index, 1);
                    }
                    $optionsEditor.val($.toJSON(Options))
                    $tr.remove();
                    return false;

                });


                var $actions = $('<div></div>').addClass('actions');
                var $last_td =  $td.clone().append(
                    $actions.append(
                        $uarr
                        ).append(
                        $darr
                        ).append(
                        $del
                        ).hide()
                    );

                var $checkbox = $('<input type="checkbox" />');
                $checkbox.get(0).checked = is_default;



                var $added_tr = $tr.clone().append(
                    $td.clone().append(
                        $checkbox
                        )
                    ).append(
                    $td.clone().append(key)
                    ).append(
                    $td.clone().append(
                        val
                        ).click(function(event){
                        if ($(this).hasClass('opened')) return;
                        $(this).addClass('opened');
                        var val = $(this).html();
                        $input = $('<input type="text" />').val(val);
                        $(this).empty().append(
                            $input
                            )
                        $input.get(0).focus();

                        //bind to 'outerClick' event with small delay
                        //to prevent trigger during current event
                        setTimeout(function(){
                            $input.bind("outerClick keydown", function(event){
                                //use this event only for Enter (0xD)
                                if (event.type == 'keydown' && event.keyCode != 0xD) return;
                                var _buffer = $(this).val();
                                $(this).parent().empty().append(_buffer).removeClass('opened');
                                Options.options[$added_tr.data('key')] = _buffer;
                                $optionsEditor.val($.toJSON(Options));
                            });
                        }, 5);
                    })
                    ).append(
                    $last_td
                    ).bind('mouseover mouseout', function(event){
                    switch (event.type) {
                        case 'mouseover' :
                            $added_tr.find('td').css({
                                backgroundColor: '#bed4e2'
                            });
                            $actions.show();
                            break;
                        case 'mouseout' :
                            $added_tr.find('td').css({
                                backgroundColor: ''
                            });
                            $actions.hide();
                            break;
                    }
                }).data('key', key).addClass('option');

                $checkbox.click(function(){
                    var index = $.inArray($added_tr.data('key'), Options['default']);
                    if (this.checked && index == -1) {
                        Options['default'].push($added_tr.data('key'));
                    }

                    if (!this.checked && index != -1) {
                        Options['default'].splice(index, 1);
                    }
                    $optionsEditor.val($.toJSON(Options));

                })

                $optionsEditor.parent().find('tr.new-option').before(
                    $added_tr
                    )

                $darr.before(' ');
                $del.before(' ');

                resetForm();

            }

            function validateForm(key, value, is_default) {
                if (!key) {
                    return 'Key is requred';
                }

                if (key in Options.options) {
                    return 'Key should be unique';
                }

                return '';
            }

            function resetForm() {
                $input_key.val('');
                $input_value.val('');
                $input_default.get(0).checked = false;

            }

            function init() {
                Options = $.parseJSON($(optionsEditor).val());
                if ($.isArray(Options.options)) {
                    temp = new Object();
                    for(var i=0; i<Options.options.length; i++)
                    	temp[i]=Options.options[i];
                    Options.options = temp;
                }

                $table = $('<table></table>');

                $new_tr = $tr.clone();

                $input_value = $('<input type="text" />');
                $input_key = $('<input type="text" />').attr('size', 5);
                $input_default = $('<input type="checkbox" />');

                $th_tr = $tr.clone();
                $th_tr.append(
                    $th.clone().append('Def').attr('title', 'Is Default?')
                    ).append(
                    $th.clone().append('Key')
                    ).append(
                    $th.clone().append('Value')
                    ).append(
                    $th.clone().append('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\
                              &nbsp;&nbsp;&nbsp;')
                    )


                $table.append(
                    $th_tr
                    ).append(
                    $new_tr.addClass('new-option').append(
                        $td.clone().append(
                            $input_default
                            )
                        ).append(
                        $td.clone().append(
                            $input_key
                            )
                        ).append(
                        $td.clone().append(
                            $input_value
                            )
                        ).append(
                        $td.clone().append(
                            $('<div></div>').addClass('actions').append(
                                $('<a href="javascript:;">+</a>').click(function(event) {
                                    if (error = validateForm($input_key.val(), $input_value.val(), $input_default.get(0).checked)) {
                                        alert(error);
                                    } else {
                                        addNewOption($input_key.val(), $input_value.val(), $input_default.get(0).checked)
                                    }
                                    return false;

                                })
                                )
                            )
                        )
                    )

                $optionsEditor.before($table);
                
                var $div = $('<div></div>').addClass('options-editor');
                $table.wrap($div);
                $optionsEditor.hide();
                for (var key in Options.options) {
                    addNewOption(key, Options.options[key], $.inArray(key, Options['default']) != -1);
                }
                
                $optionsEditor.val($.toJSON(Options));
            }
           
        })
    }
})(jQuery);