<?php

/**
 * Represents action that is necessary to finish payment
 */
interface Am_Paysystem_Action
{
    public function process(Am_Controller $action = null);
    public function toXml(XMLWriter $x);
} 