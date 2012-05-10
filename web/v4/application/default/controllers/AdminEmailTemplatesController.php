<?php

/*
 *
 *     Author: Alex Scott
 *      Email: alex@cgi-central.net
 *        Web: http://www.cgi-central.net
 *    Details: Admin Info / PHP
 *    FileName $RCSfile$
 *    Release: 4.1.10 ($Revision$)
 *
 * Please direct bug reports,suggestions or feedback to the cgi-central forums.
 * http://www.cgi-central.net/forum/
 *
 * aMember PRO is a commercial software. Any distribution is strictly prohibited.
 *
 */

class AdminEmailTemplatesController extends Am_Controller {

    public function checkAdminPermissions(Admin $admin) {
        return $admin->hasPermission(Am_Auth_Admin::PERM_EMAIL);
    }

    public function preDispatch() {
        if (!in_array($this->_request->getActionName(), array('export', 'import'))) {
            if (!$this->_request->get('name'))
                throw new Am_Exception_InputError('Name of template is undefined');
            $this->view->headScript()->appendScript($this->getJs());
        }
    }

    public function editAction() {
        $form = $this->createForm();
        $tpl = $this->getTpl($this->_request->get('copy_from', null));

        if ($form->isSubmitted()) {
            $form->setDataSources(array(
                $this->_request
            ));
        } else {
            $form->setDataSources(array(
                new HTML_QuickForm2_DataSource_Array(
                        array(
                            'attachments' => $this->prepareAttachments($tpl->attachments, $isReverse = true),
                        ) +
                        $tpl->toArray()
                )
            ));
        }

        if ($form->isSubmitted() && $form->validate()) {
            $vars = $form->getValue();
            unset($vars['label']);
            $tpl->isLoaded() ? $tpl->setForUpdate($vars) : $tpl->setForInsert($vars);
            $tpl->attachments = $this->prepareAttachments($this->_request->get('attachments'));
            $tpl->save();
        } else {
            echo $this->createActionsForm($tpl)
            . "\n"
            . $form
            . "\n"
            . $this->getJs(!$tpl->isLoaded());
        }
    }

    protected function getTpl($copy_from = null) {
        if ($copy_from)
            return $this->getCopiedTpl($copy_from);

        $tpl = $this->getDi()->emailTemplateTable->getExact(
                        $this->_request->get('name'), $this->_request->get('lang', $this->getDefaultLang()), $this->_request->get('product_id', null), $this->_request->get('day', null)
        );
        
        if (!$tpl) {
            $tpl = $this->getDi()->emailTemplateRecord;
            $tpl->name = $this->_request->get('name');
            $tpl->lang = $this->_request->get('lang', $this->getDefaultLang());
            $tpl->subject = $this->_request->get('name');
            $tpl->day = $this->_request->get('day', null);
            $tpl->product_id = $this->_request->get('product_id', null);
            $tpl->format = 'text';
            $tpl->plain_txt = null;
            $tpl->txt = null;
            $tpl->attachments = null;
        }

        return $tpl;
    }

    protected function getCopiedTpl($copy_from) {
        $sourceTpl = $this->getDi()->emailTemplateTable->getExact(
                        $this->_request->get('name'), $copy_from
        );

        if (!$sourceTpl) {
            throw new Am_Exception_InputError('Trying to copy from unexisting template : ' . $copy_from);
        }

        $sourceTpl->lang = $this->_request->get('lang', $this->getDefaultLang());

        return $sourceTpl;
    }

    protected function createForm() {
        $form = new Am_Form_Admin('EmailTemplate');

        $form->addElement(new Am_Form_Element_Html('info'))
                ->setLabel(___('Template'))
                ->setHtml(
                        sprintf('<div><strong>%s</strong><br /><small>%s</small></div>', $this->escape($this->_request->get('name')), $this->escape($this->_request->get('label'))
                        )
        );

        $form->addElement('hidden', 'name');

        $lang = $form->addElement('select', 'lang')
                ->setId('lang')
                ->setLabel(___('Language'))
                ->loadOptions(
                        $this->getLanguageOptions(
                                $this->getDi()->config->get('lang.enabled', 
                                    array($this->getDi()->config->get('lang.default', 'en')))
                        ));
        $lang->addRule('required');

        $body = $form->addElement(new Am_Form_Element_MailEditor($this->_request->get('name')));

        $form->addElement('hidden', 'label')
                ->setValue($this->_request->get('label'));

        return $form;
    }

    protected function createActionsForm(EmailTemplate $tpl) {
        $form = new Am_Form_Admin('EmailTemplate_Actions');

        $form->addElement('hidden', 'name')
                ->setValue($tpl->name);

        $langOptions = $this->getLanguageOptions(
                        $this->getDi()->emailTemplateTable->getLanguages(
                                $tpl->name, null, null, $tpl->lang
                        )
        );

        if (count($langOptions)) {
            $lang_from = $form->addElement('select', 'copy_from')
                    ->setId('another_lang')
                    ->setLabel(___('Copy from another language'))
                    ->loadOptions(array('0' => '--' . ___('Please choose') . ' --') + $langOptions)
                    ->setValue(0);
        }

        if (isset($tpl->lang) && $tpl->lang) {
            $form->addElement('hidden', 'lang')
                    ->setValue($tpl->lang);
        }

        $form->addElement('hidden', 'label')
                ->setValue($this->_request->get('label'));

        //we do not show action's form if there is not any avalable action
        if (!count($langOptions)) {
            $form = null;
        }

        return $form;
    }

    protected function prepareAttachments($att, $isReverse = false) {
        if ($isReverse) {
            return ($att ? explode(',', $att) : array());
        } else {
            return (is_array($att) ? implode(',', $att) : null);
        }
    }

    protected function getLanguageOptions($languageCodes) {
        $languageNames = $this->getDi()->languagesListUser;
        $options = array();
        foreach ($languageCodes as $k) {
            list($k,) = explode('_', $k);
            $options[$k] = "[$k] " . $languageNames[$k];
        }
        return $options;
    }
    
    protected function getDefaultLang() {
        list($k,) = explode('_', $this->getDi()->app->getDefaultLocale());
        return $k;
    } 

    protected function exportAction() {

        $this->_helper->sendFile->sendData(
                $this->getDi()->emailTemplateTable->exportReturnXml(array('email_template_id')), 'text/xml', 'amember-email-templates-' . $this->getDi()->sqlDate . '.xml');
    }

    function importAction() {
        $form = new Am_Form_Admin;

        $import = $form->addFile('import')
                ->setLabel('Upload file [email-templates.xml]');

        $form->addStatic('')->setContent('WARNING! All existing e-mail templates will be removed from database!');
        //$import->addRule('required', 'Please upload file');
        //$form->addAdvCheckbox('remove')->setLabel('Remove your existing templates?');
        $form->addSaveButton(___('Upload'));

        if ($form->isSubmitted() && $form->validate()) {
            $value = $form->getValue();

            $fn = DATA_DIR . '/import.email-templates.xml';

            if (!move_uploaded_file($value['import']['tmp_name'], $fn))
                throw new Am_Exception_InternalError("Could not move uploaded file");

            $xml = file_get_contents($fn);
            if (!$xml)
                throw new Am_Exception_InputError("Could not read XML");

            $count = $this->getDi()->emailTemplateTable->deleteBy(array())->importXml($xml);
            $this->view->content = "Import Finished. $count templates imported.";
        } else {
            $this->view->content = (string) $form;
        }
        $this->view->title = "Import E-Mail Templates from XML file";
        $this->view->display('admin/layout.phtml');
    }

    function getJs($showOffer = false) 
    {
    
        $offerText = json_encode((nl2br(___("This email template is empty in given language.\n".
            "Press [Copy] to copy template from default language [English]\n".
            "Press [Skip] to type it manually from scratch."))));
        $copy = ___("Copy");
        $skip = ___("Skip");
        if ($showOffer) {
            $jsOffer = <<<CUT
var div = $('<div><div>');
div.append($offerText+"<br />")
$('body').append(div);
div.dialog({
        autoOpen: true,
        modal : true,
        title : "",
        width : 350,
        position : ['center', 'center'],
        buttons: {
            "$copy" : function() {
                $("#another_lang").val('en');
                $("#another_lang").closest('form').ajaxSubmit({
                    success : function(data) {
                        $('#email-template-popup').empty().append(data);
                    }
                });
                $(this).dialog("close");
            },
            "$skip" : function() {
                $(this).dialog("close");
            }
        },
        close : function() {
            div.remove();
        }
    });          
CUT;
        } else {
            $jsOffer = '';
        }
    
        
        return <<<CUT
<script type="text/javascript">   
(function($){
setTimeout(function(){
    $("#lang").change(function(){
        var importantVars = new Array(
            'lang', 'name', 'label'
        );
        $.each(this.form, function() {
            if ($.inArray(this.name, importantVars) == -1) {
                if (this.name == 'format') {
                    this.selectedIndex = null;
                } else {
                    this.value='';
                }
            }
        })
        $(this.form).ajaxSubmit({
                        success : function(data) {
                            $('#email-template-popup').empty().append(data);
                        }
                    });
    });

    $("#another_lang").change(function(){
        if (this.selectedIndex == 0) return;
        $(this.form).ajaxSubmit({
                        success : function(data) {
                            $('#email-template-popup').empty().append(data);
                        }
                    });
    });
    
    $jsOffer
}, 100);

})(jQuery)
</script>
CUT;
    }

}
