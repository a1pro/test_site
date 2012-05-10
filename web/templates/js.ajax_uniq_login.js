{* This javascript is included into signup.html to provide
   real-time Javascript validation of username  *}
<script type="text/javascript">

jQuery.validator.addMethod("remoteUniqLogin", function(value, element, params) { 
  	var previous = this.previousValue(element);

  	if (!this.settings.messages[element.name] )
  		this.settings.messages[element.name] = {};
  	this.settings.messages[element.name].remoteUniqLogin = 
      	this.settings.messages[element.name].remote = 
        typeof previous.message == "function" ? previous.message(value) : previous.message;
  	
  	if ( previous.old !== value ) {
  		previous.old = value;
  		var validator = this;
  		this.startRequest(element);
  		var data = {
            'do'    : "check_uniq_login",
            'login' : $("#f_login").val(),
            'email' : $("#f_email").val(),
            'pass'  : $("#f_pass0").val()
        };
  		jQuery.ajax({
            type: "POST",
  			url: params,
  			mode: "abort",
  			port: "validate" + element.name,
  			dataType: "json",
  			data: data,
  			success: function(response) {
        		if ( !response || response.errorCode>0 ) {
    				var errors = {};
                    validator.settings.messages[element.name]['remote'] = 
                    validator.settings.messages[element.name]['remoteUniqLogin'] = 
                            response.msg;
  					errors[element.name] =  response.msg || validator.defaultMessage( element, "remoteUniqLogin" );
                    previous.message = response.msg;
                    jQuery.data(element, "previousValue", previous);
  					validator.showErrors(errors);
  				} else {
  					var submitted = validator.formSubmitted;
  					validator.prepareElement(element);
  					validator.formSubmitted = submitted;
  					validator.successList.push(element);
  					validator.showErrors();
  				}
  				previous.valid = response && (response.errorCode == 0);
  				validator.stopRequest(element, response);
  			}
  		});
  		return "pending";
  	} else if( this.pending[element.name] ) {
  		return "pending";
  	}
  	return previous.valid;
  }, "Incorect value"); 

</script>