<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     function
 * Name:     span
 * Version:  1.0
 * Author:   Alex <alex@cgi-central.net>
 * Purpose:  display links in form 1-20 21-40 41-55 (usually for display db)
 * Input: 
 *           all_count (default is $GLOBALS['all_count']) - total count of records
 *           url (default - REQUEST_URI, 
 *             in url auto substituted: 
 *                  start=\d+ -> start=34
 *                  count=\d+ -> count=20
 *
 *           start (default is $GLOBALS[start],  0)
 *           count (default -  20)
 *           show_always - default is don't show if $all_count < $count
 * Examples: {span} - $GLOBALS['all_count'] must be defined
 *           {span all_count=$all_count start=$start count=10}
 * -----------------------------------------------------------
 */
function _smarty_function_span_url($url_str, $start, $count){
    $url_str = preg_replace('/start=\d*/', 'start='.intval($start), $url_str);
    $url_str = preg_replace('/count=\d*/', 'count='.intval($count), $url_str);
    // check for not was found - need add
    if (!preg_match('/start=\d+/', $url_str)) 
        if (preg_match('/\?/', $url_str)) 
            $url_str .= '&' . 'start='.intval($start);
        else
            $url_str .= '?' . 'start='.intval($start);
    return htmlspecialchars($url_str, ENT_QUOTES);
}

function smarty_function_span($params, & $this)
{
    extract($params);
   
    if (!$all_count) {
        $all_count = $GLOBALS['all_count'];
        if (!is_numeric($all_count)) {
            $this->trigger_error('All_Count Empty!');
            return;
        } else if (!$all_count)
            return;
    }
//    print_r($HTTP_SERVER_VARS);
    if (!strlen($url)) 
        $url = $_SERVER['REQUEST_URI'];
    if (!strlen($url))
        $url = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
    if (!$start) 
        $start = intval($GLOBALS['start']);
    if (!$start) 
        $start = intval($_POST['start']);
    if (!$start) 
        $start = intval($_GET['start']);
    if (!$count) 
        $count = $GLOBALS['count'];
    if (!$count) 
        $count = 20;
    $pages = 10; // maximum pages 10 20 30 40 .. 100

    if (($all_count <= $count) && !$show_always) return;


    //max_pages * max_count
    $span_records_all = $pages * $count;

    $span_start = floor($start / $span_records_all) * $span_records_all; //first record in span
    $span_end   = $span_start + $span_records_all - 1; //number of last record in span
    if ($span_end > $all_count) $span_end = $all_count - 1;
    //
    if ($span_start > 0) {
        $beg = $span_start - $count;
        if ($beg < 0) $beg = 0;
        $url_str = _smarty_function_span_url($url, $beg, $count);
        echo "<A HREF=\"$url_str\">[&lt;&lt;]</A>&nbsp;";
    }
    //
    for ($beg=$span_start;$beg<=$span_end;$beg+=$count){
        // beg,end is actually beg and end record number in mysql
        // beg_show, end_show is for show to customer
        $end = $beg + $count - 1;
        if ($end >= $all_count) $end = $all_count-1;
        $beg_show = $beg + 1; $end_show = $end + 1;
        $url_str = _smarty_function_span_url($url, $beg, $count);
        if ($beg == $end) $text = "$beg_show"; else $text = "$beg_show-$end_show";
        if (($beg <= $start) && ($end >= $start)) 
            echo $text.'&nbsp;';
        else
            echo "<A HREF=\"$url_str\">$text</A>&nbsp;";
    }
    if ($span_end < ($all_count - 1)) {
        //$beg = $span_end + $count;
        $beg = $span_end + 1;
        if ($beg > ($all_count-1)) $beg = $all_count - 1;
        $url_str = _smarty_function_span_url($url, $beg, $count);
        $text = "[&gt;&gt;]";
        echo "<A HREF=\"$url_str\">$text</A>";
    }
}

?>
