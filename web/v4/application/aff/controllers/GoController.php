<?php 

class Aff_GoController extends Am_Controller
{
    /** @var User */
    protected $aff;
    /** @var Banner */
    protected $banner;
    
    /** @return User|null */
    function findAff()
    {
        $id = $this->getFiltered('r');
        if ($id > 0)
        {
            $aff = $this->getDi()->userTable->load($id, false);
            if ($aff) return $aff;
        }
        if (strlen($id))
        {
            $aff = $this->getDi()->userTable->findFirstByLogin($id);
            if ($aff) return $aff;
        }
        return null;
    }
    function findUrl()
    {
        $link = $this->getInt('i');
        if ($link > 0 )
        {
            $this->banner = $this->getDi()->affBannerTable->load($link, false);
            return $this->banner->url;
        }
    }
    function indexAction()
    {
        $this->aff = $this->findAff();
        $this->link = $this->findUrl();
        /// log click
        if ($this->aff)
        {
            $aff_click_id = $this->getDi()->affClickTable->log($this->aff, $this->banner);
            $this->getModule()->setCookie($this->aff, $this->banner ? $this->banner : null, $aff_click_id);
        }
        $this->_redirect($this->link ? $this->link : '/', array('prependBase'=>false));
    }
}