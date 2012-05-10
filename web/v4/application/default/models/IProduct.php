<?php
/**
 * Universal interface for products that can be added to an Invoice
 * Standart products will have type == 'product'
 * Methods will be called once when added to the Invoice, no warranty
 * that it will be called agains
 * @package Billing
 */
interface IProduct
{
    const RECURRING_REBILLS = 99999;
    /**
     * Returned Id must be unique within a given Invoice for this Type of products
     */
    function getProductId();
    /**
     * Return short type of the item, ex. for Product returns "product"
     */
    function getType();
    function getTitle();
    function getDescription();
    function getFirstPrice();
    function getFirstPeriod();
    /**
     * Rebilling mode
     * @return int 0:"No Rebilling", 1:"Charge Second Price Once", a number:"Rebill x Times: ", RECURRING_REBILLS:"Unlimited Recurring Billing"
     */
    function getRebillTimes();
    function getSecondPrice();
    function getSecondPeriod();
    /** @return 3-letter ISO code, for example 'USD' */
    function getCurrencyCode();
    function getNoTax();
    function getTrialGroup();
    function getRenewalGroup();
    /**
     * Can the item be shipped? Should we calculate shipping
     * charges for it?
     */
    function getIsTangible();
    /**
     * Can qty of the item in the Invoice be not equal to 1?
     * For subscriptions this must be almost always "false"
     * For deliverable goods like cups this must be "true"
     */
    function getIsCountable();
    
    /** @return int */
    function getBillingPlanId();
    /** @return array */
    function getBillingPlanData(); 

    /** @return array<mixed> */
    function getOptions();
    /** @param array<mixed> $options */
    function setOptions(array $options);
    
    /** @return string date Y-m-d start of subscription */
    function calculateStartDate($paymentDate, Invoice $invoice);
    
}