<?php
if (!defined('INCLUDED_AMEMBER_CONFIG'))
    die("Direct access to this location is not allowed");

/*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Members handling functions
*    FileName $RCSfile$
*    Release: 3.2.3PRO ($Revision: 3782 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

class TabMenu_Tab {
    var $file, $tab, $title;
    var $items = array();
    
    function TabMenu_Tab($title, $file, $tab)
    {
        $this->file  = $file;
        $this->tab   = $tab;
        $this->title = $title;
    }
    
    function hasItems()
    {
        return (boolean)count($this->items);
    }
    
    function addItem($item)
    {
        $this->items[] = $item;
        return $this;
    }
    
    function isAvalable($member_id=null)
    {
        return true;
    }
    
    function isActive()
    {
        if ($this->file) {
            return (strpos($_SERVER['REQUEST_URI'], '/' . $this->file)!==false && !isset($_GET['tab']));
        } else {
            return (isset($_GET['tab']) && $_GET['tab'] == $this->tab);
        }
    }
    
    function getURL()
    {
        $url = $GLOBALS['config']['root_surl']
            . '/'
            . ( ($this->file) ? $this->file : 'member.php' )
            . ( ($this->tab) ? '?tab=' . $this->tab : '' );
        return $url;
    }
    
    function render($member_id=null)
    {
        $output = '';
        if ($this->isAvalable($member_id)) {
            $subItemsRendered = '';
            if ($this->hasItems()) {
                $subItemsRendered = '<div class="submenu"><ul>';
                foreach ($this->items as $item) {
                    $subItemsRendered .= $item->render();
                }
                $subItemsRendered .= '</ul></div>';
            }
            $output = '<li'
                . ( $this->isActive() ? ' class="active"' : '' )
                . '><div class="tab">'
                . ( $this->hasItems() ? '<div class="arrow"></div>': '' )
                . '<a href="'
                . $this->getURL()
                . '"'
                . ( $this->hasItems() ? ' class="expandable"' : '' )
                .'>'
                . $this->title
                . '</a></div>'
                . $subItemsRendered
                . '</li>';
        }
        return $output;
    }
}

class NewsletterArchiveTab extends TabMenu_Tab {

    function isAvalable($member_id=null)
    {
        return (boolean)$GLOBALS['db']->get_archive_list_c(null, $member_id);
    }
}

class AffiliateTab extends TabMenu_Tab {

    function isAvalable($member_id=null)
    {

        $member = $GLOBALS['db']->get_user($member_id);
        return (boolean)($member['is_affiliate'] && $GLOBALS['config']['use_affiliates']);
    }
}

class PaymentHistoryTab extends TabMenu_Tab {

    function isAvalable($member_id=null)
    {
        $payments = $GLOBALS['db']->get_user_payments($member_id, $only_completed=1);
        return (boolean)count($payments);
    }
}

class TabMenu_Tab_Item {
    var $title, $file;

    function TabMenu_Tab_Item($title, $file)
    {
        $this->title = $title;
        $this->file  = $file;
    }
    
    function getURL()
    {
        $url = $GLOBALS['config']['root_surl']
            . '/'
            . $this->file;
        return $url;
    }

    function render()
    {
        $result = '<li><a href="'
            . $this->getURL()
            . '">'
            . $this->title
            . '</a></li>';
        return $result;
    }
}


class TabMenu {
    var $member_id = null;
    var $tabs      = array();
    static $_instance = null;

    function TabMenu($member_id = null)
    {
        $this->member_id = $member_id;
        $this->init();
    }

    /**
     *
     * @return TabMenu
     *
     */
    static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($_SESSION['_amember_user']['member_id']);
        }
        
        return self::$_instance;
    }

    function add($tab)
    {
        $this->tabs[] = $tab;
        return $this;
    }
    
    function render()
    {
        if (!$this->member_id) return '';
        
        $output = '';
        $output .= '<div class="menu-tabs"><ul>';
        
        foreach ($this->tabs as $tab) {
            $output .= $tab->render($this->member_id);
        }
        
        $output .= '<li class="last"></li></ul></div>';
        
        return $output;
    }
    
    function init()
    {
        $affiliateTab = new AffiliateTab(_TPL_AFFILIATE_TITLE, 'aff_member.php', null);
        $affiliateTab->addItem(new TabMenu_Tab_Item(_AFF_GET_BANS_LINKS, 'aff.php?action=links'));
        $affiliateTab->addItem(new TabMenu_Tab_Item(_AFF_REVIEW_STAT, 'aff.php?action=stats'));
        $affiliateTab->addItem(new TabMenu_Tab_Item(_AFF_UPDATE_PAYOUT, 'aff.php?action=payout_info'));
    
        $this->add(new TabMenu_Tab(_TPL_MEMBER_MAIN_PAGE, 'member.php', null));
        $this->add(new TabMenu_Tab(_TPL_MEMBER_ADD_RENEW, null, 'add_renew'));
        $this->add(new PaymentHistoryTab(_TPL_MEMBER_PYMNT_HIST, null, 'payment_history'));
        $this->add(new NewsletterArchiveTab(_TPL_NEWSLETTER_ARCHIVE, 'newsletter.php?a=archive', null));
        $this->add(new TabMenu_Tab(_TPL_PROFILE_TITLE, 'profile.php', null));
        $this->add($affiliateTab);
    }
}