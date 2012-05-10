<?php

class Bootstrap_Cart extends Am_Module 
{
    function onSavedFormTypes(Am_Event $event)
    {
        $event->getTable()->addTypeDef(array(
            'type' => SavedForm::T_CART,
            'title' => 'Shopping Cart Signup',
            'class' => 'Am_Form_Signup_Cart',
            'defaultTitle' => 'Create Customer Profile',
            'defaultComment' => 'shopping cart signup form',
            'isSingle' => true,
            'noDelete' => true,
            'urlTemplate' => 'signup/index/c/cart',
        ));
    }
}