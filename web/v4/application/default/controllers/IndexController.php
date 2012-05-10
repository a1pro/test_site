<?php

class IndexController extends Am_Controller
{
    function indexAction()
    {
        $this->view->display("index.phtml");
    }
}
