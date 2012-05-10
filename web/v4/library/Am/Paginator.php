<?php

/**
 * <div class="pagination">
<!-- Refer to previous page -->
  <span class="disabled">&lt; Previous</span> |
<!-- Numbered links to pages -->
        1 |
        <a href="/amember40/cart/index/index?p=2">
        2    </a> |
        <a href="/amember40/cart/index/index?p=3">
        3    </a> |
        <a href="/amember40/cart/index/index?p=4">
        4    </a> |
        <a href="/amember40/cart/index/index?p=5">
        5    </a> |
        <a href="/amember40/cart/index/index?p=6">
        6    </a> |
  
<!-- Link to next page -->
  <a href="/amember40/cart/index/index?p=2">
    Next &gt;
  </a>
</div>
 */
class Am_Paginator
{
    // configuration
    protected $cssClass = 'pagination';
    protected $countOnPage = 10; // number of links on the page
    protected $boundCount = 2; // number of fixed links in start and end 1,2  98,88
    protected $pageVar;
    // current template
    protected $totalPages = 0, $currentPage = 0;
    protected $urlTemplate;
    public function __construct($totalPages, $currentPage=null, $urlTemplate=null, $pageVar = "p", Am_Request $request = null)
    {
        $this->pageVar = $pageVar;
        $this->totalPages = $totalPages;
        $this->currentPage = $currentPage === null ? $this->_detectCurrentPage() : $currentPage;
        $this->urlTemplate = $urlTemplate === null ? $this->_detectUrlTemplate($request) : $urlTemplate;
    }
    public function setPageVar($pageVar)
    {
        $this->pageVar = $pageVar;
    }
    public function getCurrentPage()
    {
        return $this->currentPage;
    }
    public function setCurrentPage($p)
    {
        $this->currentPage = (int)$p;
    }
    public function setPagesCount($p)
    {
        $this->countOnPage = (int)$p;
    }
    public function _detectCurrentPage()
    {
        return (int)Zend_Controller_Front::getInstance()->getRequest()->getParam($this->pageVar);
    }
    protected function _detectUrlTemplate($request = null)
    {
        if ($request === null)
            $request = Zend_Controller_Front::getInstance()->getRequest();
        $uri = $request->assembleUrl(true);
        $uri = preg_replace('/([&?]'.  preg_quote($this->pageVar, '/') .')=(?:\d+)?(&|$)/'
            , '\\1=###PAGE###\\2', $uri, 99, $replaced);
        if (!$replaced)
        {
            $insert = urlencode($this->pageVar).'=###PAGE###' ;
            $uri .= (strpos($uri, '?')!==false) ? ('&'. $insert) : ('?'.$insert);
        }
        return $uri;
    }
    public function getLink($p)
    {
        
        return htmlentities(
            str_replace('###PAGE###', (int)$p, $this->urlTemplate)
            , ENT_QUOTES, 'UTF-8');
    }
    /**
     * @return array(int, int) - start and end page #
     */
    public function getRange()
    {
        $current = $this->currentPage;
        $total  = $this->totalPages;
        if ($current>($total-1)) $current = $total - 1;
        $countOnPage  = $this->countOnPage - $this->boundCount*2;
        if ($countOnPage <= 0)
            throw new Exception("Wrong Am_Paginator configuration, $countOnPage <= 0 in ".__METHOD__);
        
        $page = floor($current / $countOnPage);
        if ($page <= 0) $page = 0;
        $lastPage = floor(($total-1)/$countOnPage); // paginator page#

        $lower = $page * $countOnPage - $this->boundCount; // item #
        $upper = $lower + $countOnPage + 2 * $this->boundCount; // item #
        if ($lower < 0)
        {
            $upper += -$lower;
            $lower = 0;
        }
        if ($upper > ($total - 1))
        {
            $lower -= $upper - ($total - 1);
            if ($lower<0) $lower = 0;
            $upper = $total - 1;
        }
            
        
        $ret = range($lower, $upper);
        /// replace first 2 links
        $ret[0] = 0;
        if (max($ret)>1)
            $ret[1] = 1;
        // replace last 2 links
        $ret[ count($ret)-1   ] = $total-1;
        if (count($ret)>$this->boundCount)
            $ret[ count($ret)-2 ] = $total-2;
        
        sort($ret);
        return array_unique($ret);
        
    }
    public function render()
    {
        if ($this->totalPages<=1) return "";

        $range = $this->getRange();
        $next = min($this->currentPage + 1, $this->totalPages-1);
        $previous = max(0,$this->currentPage - 1);
        
        $out = sprintf('<div class="%s">'.PHP_EOL, $this->cssClass);
        // first link
        // previous link
        $disablePrev = $this->currentPage == $previous;
        $element = $disablePrev ? 'span' : 'a';
        $out .= sprintf('<%s%s class="prev%s">%s</%s>',
                $element,
                ($disablePrev ? '' : sprintf(' href="%s"', $this->getLink($previous))),
                $disablePrev ? " current" : "",
                ___("Prev"),
                $element);
        

        $p = null;
        foreach ($range as $i)
        {
            if (($p !== null) && ($i-$p>1))
            {
                $out .= "<span>...</span>";
            }
            $out .= sprintf('<a href="%s"%s>%s</a>', 
                $this->getLink($i), 
                $i == $this->currentPage ? ' class="current"' : "",
                $i + 1);
            $p = $i;
        }
        
        // next link
        $disableNext = $this->currentPage == $next;
        $element = $disableNext ? 'span' : 'a';
        $out .= sprintf('<%s%s class="next%s">%s</%s>',
                $element,
                ($disableNext ? '' : sprintf(' href="%s"', $this->getLink($next))),
                $disableNext ? " current" : "",
                ___("Next"),
                $element);
        
        $out .= "</div>".PHP_EOL;
        return $out;
    }
}