<?php
/*
=======================================================================
 File      : class.mollie-micropayment.php
 Author    : Mollie B.V.
 Version   : 1.2 (Nov 2007)
 More information? Go to www.mollie.nl
========================================================================
*/

class micropayment {
    # default vars
	var $partnerid	  	= null;
	var $amount 	    	= 1.30;
	var $report 	      = null;
	var $country      	= 31;
  var $servicenumber 	= null;
  var $paycode     	  = null;
  var $duration     	= null;
  var $mode        	  = null;
  var $costperminute  = null;
  var $costpercall    = null;
  var $currency       = null;
  
  # after a paycheck is done, we can use these vars
  var $payed          = false;
  var $durationdone   = 0;
  var $durationleft   = null;
  var $paystatus      = null;
    
	function setPartnerID($partnerid) {
		$this->partnerid = $partnerid;
	}
	
	function setAmount($amount) {
		if (is_numeric($amount) or $amount == 'endless') {
		    $this->amount = $amount;
		    return true;
		} else return false;
	}
	
	function setCountry($country) {
		if (is_numeric($country)) {
		    $this->country = $country;
		    return true;
		} else return false;
	}
	
	function setReportURL($report) {
		$this->report = $report;
	}
	
	function setServicenumber($servicenumber) {
		$this->servicenumber = $servicenumber;
	}
	
	function setPaycode($paycode) {
		$this->paycode = $paycode;
	}
	
	function getPayInfo() {
		$result = $this->sendToHost('www.mollie.nl', '/xml/micropayment/',
							 		'a=fetch'.
							 		'&partnerid='.urlencode($this->partnerid).
							 		'&amount='.urlencode($this->amount).
							 		'&servicenumber='.urlencode($this->servicenumber).
							 		'&country='.urlencode($this->country).
							 		'&report='.urlencode($this->report));
							 		
		if (!$result) return false;
		
		list($headers, $xml) = preg_split("/(\r?\n){2}/", $result, 2);
		$data = XML_unserialize($xml);
		
		$this->servicenumber    = $data['response']['item']['servicenumber'];
		$this->paycode          = $data['response']['item']['paycode'];
		$this->amount           = $data['response']['item']['amount'];
		$this->duration         = (isset($data['response']['item']['duration'])) ? $data['response']['item']['duration'] : '';
		$this->mode             = $data['response']['item']['mode'];
		$this->costperminute    = (isset($data['response']['item']['costperminute'])) ? $data['response']['item']['costperminute'] : '';
		$this->costpercall      = (isset($data['response']['item']['costpercall'])) ? $data['response']['item']['costpercall'] : '';
		$this->currency         = $data['response']['item']['currency'];
		return true;
	}
	
	function checkPayment() {
		$result = $this->sendToHost('www.mollie.nl', '/xml/micropayment/',
							 		'a=check'.
							 		'&servicenumber='.urlencode($this->servicenumber).
							 		'&paycode='.urlencode($this->paycode));
		if (!$result) return false;
		
		list($headers, $xml) = preg_split("/(\r?\n){2}/", $result, 2);
		$data = XML_unserialize($xml);
		
		$this->payed            = ($data['response']['item']['payed'] == 'true');
		$this->durationdone     = (isset($data['response']['item']['durationdone'])) ? $data['response']['item']['durationdone'] : '';
		$this->durationleft     = (isset($data['response']['item']['durationleft'])) ? $data['response']['item']['durationleft'] : '';
		$this->paystatus        = $data['response']['item']['paystatus'];
		$this->amount           = $data['response']['item']['amount'];
		$this->duration         = (isset($data['response']['item']['duration'])) ? $data['response']['item']['duration'] : ''; 
		$this->mode             = $data['response']['item']['mode'];
		$this->costperminute    = (isset($data['response']['item']['costperminute'])) ? $data['response']['item']['costperminute'] : '';
		$this->costpercall      = (isset($data['response']['item']['costpercall'])) ? $data['response']['item']['costpercall'] : '';
		$this->currency         = $data['response']['item']['currency'];
		return $this->payed;
	}
	
	function sendToHost($host,$path,$data) {
		$fp = @fsockopen($host,80);
		if ($fp) {
			@fputs($fp, "POST $path HTTP/1.0\n");
			@fputs($fp, "Host: $host\n");
			@fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
			@fputs($fp, "Content-length: " . strlen($data) . "\n");
			@fputs($fp, "Connection: close\n\n");
			@fputs($fp, $data);
			$buf = '';
			while (!feof($fp))
			$buf .= fgets($fp,128);
			fclose($fp);
		}
		return $buf;
	}
}




###################################################################################
#
# XML Library, by Keith Devens, version 1.2b
# http://keithdevens.com/software/phpxml
#
# This code is Open Source, released under terms similar to the Artistic License.
# Read the license at http://keithdevens.com/software/license
#
###################################################################################

class XML {
	var $parser;   #a reference to the XML parser
	var $document; #the entire XML structure built up so far
	var $parent;   #a pointer to the current parent - the parent will be an array
	var $stack;    #a stack of the most recent parent at each nesting level
	var $last_opened_tag; #keeps track of the last tag opened.

	function XML () {
 		$this->parser = xml_parser_create();
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'open','close');
		xml_set_character_data_handler($this->parser, 'data');
	}
	function destruct () { xml_parser_free($this->parser); }
	function parse (&$data) {
		$this->document = array();
		$this->stack    = array();
		$this->parent   = &$this->document;
		return xml_parse($this->parser, $data, true) ? $this->document : NULL;
	}
	function open (&$parser, $tag, $attributes) {
		$this->data = ''; #stores temporary cdata
		$this->last_opened_tag = $tag;
		if (is_array($this->parent) and array_key_exists($tag,$this->parent)) { #if you've seen this tag before
			if (is_array($this->parent[$tag]) and array_key_exists(0,$this->parent[$tag])) { #if the keys are numeric
				#this is the third or later instance of $tag we've come across
				$key = count_numeric_items($this->parent[$tag]);
			}
			else {
				#this is the second instance of $tag that we've seen. shift around
				if (array_key_exists("$tag attr",$this->parent)) {
					$arr = array('0 attr'=>&$this->parent["$tag attr"], &$this->parent[$tag]);
					unset($this->parent["$tag attr"]);
				}
				else {
					$arr = array(&$this->parent[$tag]);
				}
				$this->parent[$tag] = &$arr;
				$key = 1;
			}
			$this->parent = &$this->parent[$tag];
		}
		else {
			$key = $tag;
		}
		if ($attributes) $this->parent["$key attr"] = $attributes;
		$this->parent  = &$this->parent[$key];
		$this->stack[] = &$this->parent;
	}
	function data (&$parser, $data) {
		if ($this->last_opened_tag != NULL) #you don't need to store whitespace in between tags
			$this->data .= $data;
	}
	function close (&$parser, $tag) {
		if ($this->last_opened_tag == $tag){
			$this->parent = $this->data;
			$this->last_opened_tag = NULL;
		}
		array_pop($this->stack);
		if ($this->stack) $this->parent = &$this->stack[count($this->stack)-1];
	}
}

function & XML_unserialize (&$xml) {
	$xml_parser = new XML();
	$data = $xml_parser->parse($xml);
	$xml_parser->destruct();
	return $data;
}

function count_numeric_items(&$array){
	return is_array($array) ? count(array_filter(array_keys($array), 'is_numeric')) : 0;
}

?>