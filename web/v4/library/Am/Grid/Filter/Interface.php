<?php

/**
 */
interface Am_Grid_Filter_Interface
{
    /**
     * Init filter and apply it to $dataSource
     * Am_Request comes without 
     */
    function initFilter(Am_Grid_ReadOnly $grid);

    /**
     * @return array list of variables - without gridId_ !
     */
    function getVariablesList();
    
    /**
     * @return bool
     */
    function isFiltered();
    
    /**
     * render filter with surrounding DIV
     */
    function renderFilter();
    /**
     * @return string html/js/css that must not be reloaded between requests
     */
    function renderStatic();
}
