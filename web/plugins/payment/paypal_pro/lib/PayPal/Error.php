<?php
/**
 * @package PayPal
 *
 * $Id: Error.php 2081 2006-09-20 09:18:54Z aser $
 */

/**
 * Load parent class.
 */
if (!class_exists ('PEAR')) require_once('PEAR.php');

/**
 * A standard PayPal Error object
 *
 * @package  PayPal
 */
class PayPal_Error extends PEAR_Error {

	/**
	 * Standard error constructor
	 *
     * @param string The error message
     * @param int An optional integer error code
	 */
	function PayPal_Error($message, $errorcode = null)
	{
		parent::PEAR_error($message, $errorcode);
	}

}
