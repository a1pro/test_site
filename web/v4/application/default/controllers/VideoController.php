<?php
/*
*   Members page. Used to renew subscription.
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Member display page
*    FileName $RCSfile$
*    Release: 4.1.10 ($Revision: 5371 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


class VideoController extends Am_Controller 
{
    protected $id;
    protected $video;
    /** @return Video or throw exception */
    function getVideo()
    {
        if (!$this->video)
        {
            $this->id = $this->_request->getInt('id');
            if (!$this->id)
                throw new Am_Exception_InputError("Wrong URL - no video id passed");
            $this->video = $this->getDi()->videoTable->load($this->id, false);
            if (!$this->video)
                throw new Am_Exception_InputError("This video has been removed");
        }
        return $this->video;
    }
    function dAction()
    {
        $id = $this->_request->get('id');
        $this->validateSignedLink($id);
        $id = intval($id);
        $video = $this->getDi()->videoTable->load($id);
        set_time_limit(600);
        // @todo use X-SendFile where possible
        readfile($video->getFullPath());
    }
    function pAction()
    {
        $this->view->title = $this->getVideo()->title;
        $this->view->content = 
            "<script type='text/javascript' id='am-video-{$this->id}'>" . 
            $this->renderJs() . 
            "\n</script>";
        $this->view->display('layout.phtml');
    }
    function getSignedLink(Video $video)
    {
        $rel = $video->pk().'-'. ($this->getDi()->time + 3600*24);
        return ROOT_URL . '/video/d/id/'.
            $rel . '-' .
            $this->getDi()->app->hash('am-video-'.$rel, 10);
    }
    function validateSignedLink($id)
    {
        @list($rec_id, $time, $hash) = explode('-', $id, 3);
        if ($rec_id<=0)
            throw new Am_Exception_InputError("Wrong video id#");
        if ($time < Am_Di::getInstance()->time)
            throw new Am_Exception_InputError("Video Link Expired");
        if ($hash != $this->getDi()->app->hash("am-video-$rec_id-$time", 10))
            throw new Am_Exception_InputError("Video Link Error - Wrong Sign");
    }
    function renderJs()
    {
        $this->view->id = $this->id;
        $this->view->width = $this->_request->getInt('width', 520);
        $this->view->height = $this->_request->getInt('height', 330);
        if (!$this->getDi()->auth->getUserId())
        {
            $this->view->error = ___("You must be logged-in to open this video");
            $this->view->link  = ROOT_SURL . "/login";
        } elseif (!$this->getVideo()->hasAccess($this->getDi()->user)) {
            $this->view->error = ___("Your subscription does not allow access to this video");
            $this->view->link  = ROOT_SURL . "/member";
        } else {
            $this->view->flowPlayerParams = array(
                'key' => $this->getDi()->config->get('flowplayer_license'), 
                'clip' => array ( 'autoPlay' => false, ),
            );
            $this->view->video = $this->getSignedLink($this->getVideo());
        }
        return $this->view->render('_video.flowplayer.phtml');
    }
    function jsAction()
    {
        $this->_response->setHeader('Content-type', 'text/javascript');
        $this->getVideo();
        echo $this->renderJs();
    }
} 