<?php                          
/**
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Affiliate management routines
*    FileName $RCSfile$
*    Release: 4.1.10 ($Revision$)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*                                                                          
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*                                                                                 
*/

class Am_BannerRenderer {
    protected $affBanner = null;

    public function __construct(AffBanner $affBanner)
    {
        $this->affBanner = $affBanner;
    }

    public static function create(AffBanner $affBanner)
    {
        switch ($affBanner->type) {
            case AffBanner::TYPE_TEXTLINK :
                return new Am_BannerRenderer_TextLink($affBanner);
                break;
            case AffBanner::TYPE_BANNER :
                return new Am_BannerRenderer_Banner($affBanner);
                break;
            case AffBanner::TYPE_LIGHTBOX :
                return new Am_BannerRenderer_Lightbox($affBanner);
                break;
            case AffBanner::TYPE_PAGEPEEL :
                return new Am_BannerRenderer_Pagepeel($affBanner);
                break;
            default:
                throw new Am_Exception_InternalError(
                    'Can not instantiate banner with type : ' . $affBanner->type
                );
        }
    }

    /**
     * Should be overriden in subclasses
     */
    public function getCode()
    {
        throw new Am_Exception_NotImplemented();
    }
    /**
     * Should be overriden in subclasses
     */
    public function getPreview()
    {
        return $this->getCode();
    }

    public function getNote()
    {
        return false;
    }
    
    public function getUrl()
    {
        return sprintf('%s/aff/go?r=%d&i=%d', ROOT_URL, Am_Di::getInstance()->auth->getUserId(), $this->affBanner->pk());
    }

}

class Am_BannerRenderer_TextLink extends Am_BannerRenderer {

    public function getCode()
    {
        return sprintf('<a href="%s">%s</a>',
            $this->getUrl(),
            $this->affBanner->title
        );
    }
}

class Am_BannerRenderer_Banner extends Am_BannerRenderer {

    public function getCode()
    {
        $upload = Am_Di::getInstance()->uploadTable->load($this->affBanner->upload_id);

        return sprintf('<a href="%s"><img src="%s" border=0 alt="%s" %s %s></a>',
            $this->getUrl(),
            ROOT_URL . '/file/get/path/' . $upload->getPath() . '/i/' . Am_Di::getInstance()->auth->getUserId(),
            Am_Controller::escape($this->affBanner->title),
            ($this->affBanner->width ? sprintf('width="%s"', $this->affBanner->width) : ''),
            ($this->affBanner->height ? sprintf('height="%s"', $this->affBanner->height) : '')
        );
    }
}

class Am_BannerRenderer_Lightbox extends Am_BannerRenderer {

    public function getCode()
    {
        $upload = Am_Di::getInstance()->uploadTable->load($this->affBanner->upload_id);
        $upload_big = Am_Di::getInstance()->uploadTable->load($this->affBanner->upload_big_id);
        return sprintf('<a href="%s" rel="lightbox" rev="%s" title="%s"><img src="%s" border=0 alt="%s"></a>',
            ROOT_URL . '/file/get/path/' . $upload_big->getPath(). '/i/' . Am_Di::getInstance()->auth->getUserId(),
            $this->getUrl(),
            $this->affBanner->title,
            ROOT_URL . '/file/get/path/' . $upload->getPath(). '/i/' . Am_Di::getInstance()->auth->getUserId(),
            Am_Controller::escape($this->affBanner->title)
        );
    }

    public function getNote()
    {
        return sprintf('Paste the following code into the section &lt;head&gt;
            of your html document.<br />
            <textarea rows="6"%s><link rel="stylesheet" type="text/css" href="%s/application/aff/views/public/css/jquery.lightbox.css" media="screen" />
<script type="text/javascript" src="%s/application/aff/views/public/js/jquery.lightbox.js"></script>
<script type="text/javascript">
var am_url = "%s";
var am_lightbox = {
    imageLoading : am_url + "lightbox-ico-loading.gif",
    imageBtnClose : am_url + "lightbox-btn-close.gif",
    imageBlank : am_url + "lightbox-blank.gif"
};
</script></textarea><br />You only need to place this code one time.',
            ' style="width:90%; overflow:scroll"',
            ROOT_URL, ROOT_URL, ROOT_URL,
            ROOT_URL . '/application/aff/views/public/img/'
            );
    }
}

class Am_BannerRenderer_Pagepeel extends Am_BannerRenderer {

    public function getCode()
    {
        throw new Am_Exception_NotImplemented();
    }
}

class Aff_AffController extends Am_Controller {
    
    public function indexAction() 
    {
        return $this->linksAction();
    }
    
    public function linksAction()
    {
        $this->getView()->headLink()->appendStylesheet(
            REL_ROOT_URL . '/application/aff/views/public/css/jquery.lightbox.css'
        );
        $this->getView()->headScript()->appendFile(
                REL_ROOT_URL . '/application/aff/views/public/js/jquery.lightbox.js'
            )->appendScript(
                sprintf('var am_url = "%s";
var am_lightbox = {
imageLoading : am_url + "lightbox-ico-loading.gif",
imageBtnPrev : am_url + "lightbox-btn-prev.gif",
imageBtnNext : am_url + "lightbox-btn-next.gif",
imageBtnClose : am_url + "lightbox-btn-close.gif",
imageBlank : am_url + "lightbox-blank.gif"
};', ROOT_URL . '/application/aff/views/public/img/')
            );


        $affBanners = $this->getDi()->affBannerTable->findActive();
        $affDownloads = $this->getDi()->uploadTable->findByPrefix('affiliate');

        $this->view->assign('affDownloads', $affDownloads);
        $this->view->assign('affBanners', $affBanners);
        $this->view->display('aff/links.phtml');
    }
    
    public function enableAffAction()
    {
        if ($this->getDi()->config->get('aff.signup_type') == 2) {
            throw new Am_Exception_AccessDenied('Signup disabled in config');
        }

        $user = $this->getDi()->user;
        if (!$user->is_affiliate)
        {
            $user->is_affiliate = 1;
            $user->update();
        }

        return $this->linksAction();
    }

    public function statsAction()
    {
    }

    public function payoutInfoAction()
    {
    }

    public function preDispatch()
    {
        $this->getDi()->auth->requireLogin(ROOT_URL . '/aff/aff');
    }
}
