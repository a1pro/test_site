<?php

class Am_Navigation_Admin_Item_Separator extends Zend_Navigation_Page {
    public function getHref() {   }
    function getClass(){
        return "menu-separator";
    }
    function xxx(){
        $url = htmlentities(REL_ROOT_URL . "/application/default/views/public/img/admin/menu-sep.gif");
        return "<div style='background-image: url(\"$url\"); background-repeat: repeat-x; height: 5px;'>&nbsp;</div>";
    }
}

class Am_Navigation_Admin extends Zend_Navigation_Container
{
    function addDefaultPages()
    {
        $separator = new Am_Navigation_Admin_Item_Separator;

        $this->addPage(array(
            'id' => 'dashboard',
            'controller' => 'admin',
            'label' => ___("Dashboard")
        ));

        $this->addPage(Zend_Navigation_Page::factory(array(
            'id' => 'users',
            'uri' => '#',
            'label' => ___("Users"),
            'resource' => 'grid_u',
            'privilege' => 'browse',
            'pages' =>
            array_merge(
            array(
                array(
                    'id' => 'users-browse',
                    'controller' => 'admin-users',
                    'label' => ___('Browse Users'),
                    'resource' => 'grid_u',
                    'privilege' => 'browse',
                    'class' => 'bold',
                ),
                array(
                    'id' => 'users-insert',
                    'uri' => REL_ROOT_URL . '/admin-users?_u_a=insert',
                    'label' => ___('Add User'),
                    'resource' => 'grid_u',
                    'privilege' => 'insert',
               ),
            ),
            !Am_Di::getInstance()->config->get('manually_approve') ? array() : array(array(
                    'id' => 'user-not-approved',
                    'controller' => 'admin-users',
                    'action'     => 'not-approved',
                    'label' => ___('Not Approved Users'),
                    'resource' => 'grid_u',
                    'privilege' => 'browse',
            )),
            array(
                array(
                    'id' => 'users-email',
                    'controller' => 'admin-email',
                    'label' => ___('E-Mail Users'),
                    'resource' => Am_Auth_Admin::PERM_EMAIL,
                ),
                clone $separator,
                array(
                    'id' => 'users-import',
                    'controller' => 'admin-import',
                    'label' => ___('Import Users'),
                    'resource' => Am_Auth_Admin::PERM_IMPORT,
                )
            ))
        )));

        $this->addPage(array(
            'id' => 'reports',
            'uri' => '#',
            'label' => ___("Reports"),
            'pages' => array(
                array(
                    'id' => 'reports-reports',
                    'controller' => 'admin-reports',
                    'label' => ___('Reports'),
                    'resource' => Am_Auth_Admin::PERM_REPORT,
                ),
                array(
                    'id' => 'reports-payments',
                    'controller' => 'admin-payments',
                    'label' => ___('Payments'),
                    'resource' => Am_Auth_Admin::PERM_REPORT,
                ),
            )
        ));

        $this->addPage(array(
            'id' => 'products',
            'uri' => '#',
            'label' => ___('Products'),
            'pages' => array_filter(array(
                array(
                    'id' => 'products-manage',
                    'controller' => 'admin-products',
                    'label' => ___('Manage Products'),
                    'resource' => 'grid_product',
                    'class' => 'bold',
                ),
                array(
                    'id' => 'products-protect',
                    'controller' => 'admin-content',
                    'label' => ___('Protect Content'),
                    'resource' => 'grid_content',
                    'class' => 'bold',
                ),
                array(
                    'id' => 'products-coupons',
                    'controller' => 'admin-coupons',
                    'label' => ___('Coupons'),
                    'resource' => 'grid_coupon',
                ),
            ))
        ));

        $this->addPage(array(
            'id' => 'configuration',
            'uri' => '#',
            'label' => ___('Configuration'),
            'pages' => array_filter(array(
                array(
                    'controller' => 'admin-setup',
                    'label' => ___('Setup/Configuration'),
                    'resource' => Am_Auth_Admin::PERM_SETUP,
                    'class' => 'bold',
                ),
                array(
                    'controller' => 'admin-tax',
                    'label' => ___('Tax Settings'),
                    'resource' => Am_Auth_Admin::PERM_SETUP,
                ),
                array(
                    'controller' => 'admin-fields',
                    'label' => ___('Add Fields'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                array(
                    'controller' => 'admin-admins',
                    'label' => ___('Admin Accounts'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                array(
                    'controller' => 'admin-saved-form',
                    'label' => ___('Forms Editor'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                array(
                    'controller' => 'admin-ban',
                    'label'      => ___('Blocking IP/E-Mail'),
                    'resource'   => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                array(
                    'controller' => 'admin-change-pass',
                    'label'      => ___('Change Password')
                ),
            )),
        ));

        $this->addPage(array(
            'id' => 'utilites',
            'uri' => '#',
            'label' => ___('Utilites'),
            'order' => 1000,
            'pages' => array_filter(array(
                array(
                    'controller' => 'admin-backup',
                    'label' => ___('Backup'),
                    'resource' => Am_Auth_Admin::PERM_BACKUP_RESTORE,
                ),
                array(
                    'controller' => 'admin-restore',
                    'label' => ___('Restore'),
                    'resource' => Am_Auth_Admin::PERM_BACKUP_RESTORE,
                ),
                array(
                    'controller' => 'admin-rebuild',
                    'label' => ___('Rebuild Db'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                clone $separator,
                array(
                    'controller' => 'admin-logs',
                    'label' => ___('Logs'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                array(
                    'controller' => 'admin-info',
                    'label' => ___('Version Info'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                clone $separator,
//                array(
//                    'controller' => 'admin-trans-global',
//                    'label' => ___('Global Translations'),
//                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
//                ),
//                (count(Am_Di::getInstance()->config->get('lang.enabled')) > 1) ? array(
//                    'controller' => 'admin-trans-local',
//                    'label' => ___('Local Translations'),
//                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
//                ) : null,
//                clone $separator,
                array(
                    'controller' => 'admin-clear',
                    'label' => ___('Delete Old Records'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
                array(
                    'controller' => 'admin-build-demo',
                    'label' => ___('Build Demo'),
                    'resource' => Am_Auth_Admin::PERM_SUPER_USER,
                ),
            )),
        ));
        $this->addPage(array(
            'id' => 'help',
            'uri' => '#',
            'label' => ___('Help & Support'),
            'pages' => array_filter(array(
                array(
                    'uri' => 'http://www.amember.com/docs/',
                    'label'      => ___('Documentation'),
                ),
                array(
                    'uri' => 'http://bt.amember.com/',
                    'label'      => ___('Report Bugs'),
                ),
             )
        )));
        
        Am_Di::getInstance()->hook->call(Am_Event::ADMIN_MENU, array('menu' => $this));
        
        /// workaround against using the current route for generating urls
        foreach (new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST) as $child)
            if ($child instanceof Zend_Navigation_Page_Mvc && $child->getRoute()===null)
                $child->setRoute('default');
    }
}