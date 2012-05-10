<?php
/*
*
*
*     Author: Alex Scott
*      Email: alex@cgi-central.net
*        Web: http://www.cgi-central.net
*    Details: Admin accounts
*    FileName $RCSfile$
*    Release: 4.1.10 ($Revision: 4649 $)
*
* Please direct bug reports,suggestions or feedback to the cgi-central forums.
* http://www.cgi-central.net/forum/
*
* aMember PRO is a commercial software. Any distribution is strictly prohibited.
*
*/


class Am_Form_Element_RegionalTaxes extends HTML_QuickForm2_Element {

    protected $regional_taxes = array();

    public function getRawValue()
    {
        return null;
    }

    public function setValue($value)
    {
        $this->regional_taxes = $value;
    }

    public function getType()
    {
        return 'custom_row';
    }

    public function __toString()
    {
        $output = sprintf('<div style="padding:0.5em"><h1>%s</h1><table %s><tr><th>%s</th>
	<th>%s</th>
        <th>%s</th>
	<th>%s</th>
	<th>&nbsp;</th></tr>',
            ___('Configured Tax Values'),
            'class="grid"',
            ___('Country'),
            ___('State'),
            ___('Zip'),
            ___('Tax Value') 
        );
        foreach ($this->regional_taxes as $id => $region) {
            $output .= '<tr>'
                . sprintf('<td>%s</td>', Am_Di::getInstance()->countryTable->getTitleByCode($region['country']))
                . sprintf('<td>%s</td>', ($region['state'] ? Am_Di::getInstance()->stateTable->getTitleByCode($region['country'], $region['state']) : '*'))
                . sprintf('<td>%s</td>', ($region['zip'] ? $region['zip'] : '*'))
                . sprintf('<td>%.2f%s</td>', $region['tax_value'], '%')
                . sprintf('<td><a class="remove" href="%s">%s</a></td>',
                        Am_Controller::makeUrl('admin-tax', 'removeregion', null, array('id'=>$id)), ___('Remove'))
                . '</tr>';
        }

        $output .= '</table></div>';
        $id = $this->getId();
        $output .= "
        <style type='text/css'>
            #row-$id .element-title { display: none;  }
            #row-$id .element { margin-left: 0 } 
        </style>
        ";
        return sprintf('<tr><td colspan="2" id="tax-regional-regions">%s</td></tr>', $output);
    }
}


class Am_Form_Admin_Tax extends Am_Form_Admin {

    public function init()
    {
        $this->addElement('advradio', 'tax_type')
            ->setLabel(___('Method'))
            ->loadOptions(array(
                ''=>___('Disabled'),
                '1'=>___('Global Tax Settings'),
                '2'=>___('Regional Tax Settings')
            ));

        $global = $this->addElement('fieldset', 'global')
            ->setLabel(___('Global Tax Configuration'))
            ->setId('tax-global');

        $global->addElement('text', 'tax_title')
            ->setLabel(array(___('Global Tax Title'),___('Sales Tax or VAT')))
            ->setId('tax-global-title');

        $global->addElement('text', 'tax_value')
            ->setLabel(___('Tax Value') . ', %')
            ->setId('tax-global-value');

        $regional = $this->addElement('fieldset', 'regional')
                ->setLabel(___('Regional Tax Configuration'))
                ->setId('tax-regional');


        $regional->addSelect('country')->setLabel('Country')
            ->setId('f_country')
            ->loadOptions(Am_Di::getInstance()->countryTable->getOptions(true));

        $group = $regional->addGroup()->setLabel('State');
        $group->addSelect('state')
            ->setId('f_state')
            ->loadOptions(Am_Di::getInstance()->stateTable->getOptions(null, true));
        $group->addText('state')->setId('t_state')->setAttribute('disabled', 'disabled');


        $regional->addTextarea('zip')
                ->setLabel(array(
                    ___('Zip Codes'),
                    nl2br(___("use ; as separator,\nalso can specify range:\n123312;123324;123325-123329"))
                ))
                ->setId('tax-regional-value');

        $regional->addText('regional_tax_value')
                ->setLabel(___('Tax Value').' %')
                ->setId('tax-regional-value');

        $regional->addElement(
            new Am_Form_Element_RegionalTaxes('regional_taxes')
        );

        $this->addSaveButton();
        
    }


}

class AdminTaxController extends Am_Controller 
{
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission(Am_Auth_Admin::PERM_SETUP);
    }
    public function indexAction()
    {

        $form = new Am_Form_Admin_Tax();
        if ($form->isSubmitted()) {
            Am_Config::saveValue('tax_type', $this->getRequest()->getParam('tax_type'));
            switch($tax_type = $this->getRequest()->getInt('tax_type')) {
                case 0 : 
                    Am_Config::saveValue('use_tax', 0);
                    break;
                case 1 :
                    Am_Config::saveValue('use_tax', 1);
                    Am_Config::saveValue('tax_title', $this->getRequest()->getParam('tax_title'));
                    Am_Config::saveValue('tax_value', $this->getRequest()->getParam('tax_value'));
                    break;
                case 2 :
                    Am_Config::saveValue('use_tax', 1);
                    $this->addRegion();
                    break;
                default:
                    throw new Am_Exception_InputError('Unknown tax_type : ' . $tax_type);
            }

            //use redirect in order to allow App to reload updated Config
            return $this->redirectLocation($this->getUrl());
        }

        $form->setDataSources(array(new HTML_QuickForm2_DataSource_Array($this->getStoredValues())));

        $this->view->assign('form', $form);
        $this->view->display('admin/tax.phtml');

    }

    public function removeregionAction()
    {
        $this->removeRegion($this->getRequest()->getParam('id'));
        if (!$this->isAjax()) {
            $this->redirectLocation($this->getUrl(null, 'index'));
        }
    }

    protected function getStoredValues()
    {
        return array(
            'tax_type' => $this->getDi()->config->get('tax_type'),
            'tax_title' => $this->getDi()->config->get('tax_title'),
            'tax_value' => $this->getDi()->config->get('tax_value'),
            'regional_taxes'=> $this->getDi()->config->get('regional_taxes')
        );
    }

    protected function addRegion()
    {

        //do not add region if needed data was not submited
        if (!$this->getRequest()->getParam('country') ||
                !$this->getRequest()->getParam('regional_tax_value')) {

            return;
        }

        $region['country'] = $this->getRequest()->getParam('country');
        $region['state'] = $this->getRequest()->getParam('state');
        $region['zip'] = $this->getRequest()->getParam('zip');
        $region['tax_value'] = $this->getRequest()->getParam('regional_tax_value');

        $regional_taxes = $this->getDi()->config->get('regional_taxes');
        $regional_taxes[] = $region;

        Am_Config::saveValue('regional_taxes', $regional_taxes);
    }

    protected function removeRegion($regionId)
    {
        $regional_taxes = $this->getDi()->config->get('regional_taxes');
        unset($regional_taxes[$regionId]);
        Am_Config::saveValue('regional_taxes', $regional_taxes);
    }
}
