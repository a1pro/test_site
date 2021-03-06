<?php
require_once ("HTTPBase.php");
class NumberIdentification extends HTTPBase {
  var $server;
  var $numservers;
  var $API_VERSION;
  function NumberIdentification(){
    $this->HTTPBase();
    $this->isSecure = 1;    // use HTTPS by default

    //set the allowed_fields hash
    $this->allowed_fields["l"] = 1;
    $this->allowed_fields["phone"] = 1;
    $this->num_allowed_fields = count($this->allowed_fields);

    //set the url of the web service
    $this->url = "app/phonetype_http";
    $this->check_field = "refid";
    $this->server = array("www.maxmind.com", "www2.maxmind.com");
    $this->numservers = count($this->server);
    $this->API_VERSION = 'PHP/1.4';
    $this->timeout = 30;
  }
}
?>
