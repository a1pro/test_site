<?php
/*
* 
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin Info / PHP
*    FileName $RCSfile$
*    Release: 4.1.10 ($Revision: 4883 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/

class Am_Grid_Editable_Uploads extends Am_Grid_Editable {
    public function getVariablesList() {
        return array_merge(parent::getVariablesList(), array('prefix'));
    }
}

class AdminUploadController extends Am_Controller {
    public function checkAdminPermissions(Admin $admin) {
        /// @todo fixme INSECURE access to all files
        return true;
    }

    public function gridAction() {
        $prefix = $this->getRequest()->getParam('prefix');
        if (!$prefix) {
            throw new Am_Exception_InputError('prefix is undefined');
        }   
        if (!$this->getDi()->uploadAcl->checkPermission($prefix, 
                    Am_Upload_Acl::ACCESS_LIST, 
                    $this->getDi()->authAdmin->getUser())) {
            throw new Am_Exception_AccessDenied();
        }
        
        $ds = new Am_Query($this->getDi()->uploadTable);
        $ds->addWhere('prefix=?', $prefix);
        $grid = new Am_Grid_Editable_Uploads('_files', 'Files',
                $ds, $this->getRequest(), $this->view);
        $grid->setPermissionId('grid_content');
        $grid->addField(new Am_Grid_Field('name', 'Name', true))
                ->setRenderFunction(array($this, 'renderName'));
        $grid->addField(new Am_Grid_Field('desc', 'Description', true));
        $grid->actionsClear();
        $grid->actionAdd(new Am_Grid_Action_LiveEdit('desc'));
        $grid->addCallback(Am_Grid_ReadOnly::CB_RENDER_STATIC, array($this, 'addJs'));
        $grid->isAjax(false);
        $response = $grid->run();
        $response->sendHeaders();
        $response->sendResponse();
    }

    public function addJs($out) {
        $js = <<<CUT
<script type="text/javascript">
    $('.filesmanager-file').die().live('click', function(){
        $(this).closest('.filesmanager-container').get(0).uploader.
        upload('addFile', $(this).data('info'));
        $(this).closest('.filesmanager-container').dialog('close');
    })
</script>
CUT;
        $out .= $js;
    }

    public function renderName($obj) {
        return file_exists($obj->getFullPath()) ?
            sprintf('<td>%s</td>', $this->renderNameStandart($obj)) :
            sprintf('<td><div class="reupload-conteiner">%s<br />%s</div></td>',
                $this->renderNameStandart($obj),
                $this->renderNameError($obj));
    }

    protected function renderNameStandart($obj) {
        $data = array (
                'name' => $obj->getName(),
                'size_readable' => $obj->getSizeReadable(),
                'upload_id' => $obj->pk(),
                'mime' => $obj->mime,
                'ok' => true
        );

        return sprintf('<a href="javascript:;" class="filesmanager-file" data-info="%s"><span class="upload-name">%s</span></a>',
                $this->escape(Am_Controller::getJson($data)),
                $this->escape($obj->name));
    }

    protected function renderNameError($obj) {
        $upload = $obj;
        return sprintf('<div class="reupload-conteiner-hide"><span class="error">%s</span>%s</td>',
                ___('File was removed from disk or corrupted. Please re-upload it.'),
            '<div><span class="reupload" data-upload_id="' . $upload->pk() . '" id="reupload-' . $upload->pk() . '"></span></div></div>');
    }

    public function getAction() {
        if ($id = $this->getParam('id')) {
            $file = $this->getDi()->uploadTable->load($id);
        } else {
            $file = $this->getDi()->uploadTable->findFirstByPath($this->getParam('path'));
        }
        if (!$file) {
            throw new Am_Exception_InputError(
            'Can not fetch file for id/path : ' . $this->getParam('id') . '/' . $this->getParam('path')
            );
        }
        
        if (!$this->getDi()->uploadAcl->checkPermission($file, 
                    Am_Upload_Acl::ACCESS_READ, 
                    $this->getDi()->authAdmin->getUser())) {
            throw new Am_Exception_AccessDenied();
        }
        
        $this->_helper->sendFile($file->getFullPath(), $file->getType(), array('filename'=>$file->getName()));
        exit;
    }

    protected function getUploadIds(Am_Upload $upload) {
        $upload_ids = array();
        foreach($upload->getUploads() as $upload) {
            $upload_ids[] = $upload->pk();
        }
        return $upload_ids;
    }

    public function reUploadAction() {
        $file = $this->getDi()->uploadTable->load($this->getParam('id'));
        if (!$this->getDi()->uploadAcl->checkPermission($file,
                    Am_Upload_Acl::ACCESS_WRITE,
                    $this->getDi()->authAdmin->getUser())) {
            throw new Am_Exception_AccessDenied();
        }

        $upload = new Am_Upload($this->getDi());

        try {
        $upload->processReSubmit('upload', $file);

            if ($file->isValid()) {
                $data = array (
                    'ok' => true,
                    'name' => $file->getName(),
                    'size_readable' => $file->getSizeReadable(),
                    'upload_id' => $file->pk(),
                    'mime' => $file->mime
                );
                echo $this->getJson($data);
            } else {
               echo $this->getJson(array(
                    'ok' => false,
                    'error' => 'No files uploaded',
                ));
            }
        } catch (Am_Exception $e) {
            echo $this->getJson(array(
                    'ok' => false,
                    'error' => 'No files uploaded',
                ));
        }

    }

    public function uploadAction() {
        if (!$this->getDi()->uploadAcl->checkPermission($this->getParam('prefix'), 
                    Am_Upload_Acl::ACCESS_WRITE, 
                    $this->getDi()->authAdmin->getUser())) {
            throw new Am_Exception_AccessDenied();
        }
              
        $upload = new Am_Upload($this->getDi());
        $upload->setPrefix($this->getParam('prefix'));
        $upload->loadFromStored();
        $ids_before = $this->getUploadIds($upload);
        $upload->processSubmit('upload');
        //find currently uploaded file
        $x = array_diff($this->getUploadIds($upload), $ids_before);
        $upload_id = array_pop($x);
        try {
            $upload = $this->getDi()->uploadTable->load($upload_id);

            $data = array (
                'ok' => true,
                'name' => $upload->getName(),
                'size_readable' => $upload->getSizeReadable(),
                'upload_id' => $upload->pk(),
                'mime' => $upload->mime
            );
            echo $this->getJson($data);

        } catch (Am_Exception $e) {
            echo $this->getJson(array(
                'ok' => false,
                'error' => 'No files uploaded',
            ));
        }
    }

    public function getSizeAction() {
        $file = $this->getDi()->uploadTable->load($this->getParam('id'));

        if (!$file) {
            throw new Am_Exception_InputError(
            'Can not fetch file for id : ' . $this->getParam('id')
            );
        }

        if (!$this->getDi()->uploadAcl->checkPermission($file, 
                    Am_Upload_Acl::ACCESS_READ, 
                    $this->getDi()->authAdmin->getUser())) {
            throw new Am_Exception_AccessDenied();
        }
        
        if ( $size = getimagesize($file->getFullPath()) ) {
            echo $this->getJson(
                array (
                    'width' => $size[0],
                    'height' => $size[1]
                )
            );
        } else {
            echo $this->getJson(false);
        }

    }
}

