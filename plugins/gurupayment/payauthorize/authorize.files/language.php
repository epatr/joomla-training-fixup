<?php
/* SVN FILE:       $Id: language.php 1880 2011-06-17 07:20:33Z dima $ */
/**
* Project Name :    Digistore for Joomla 1.5.x
*
* @package          DigistoreJ15_1.6.x
* @author           $Author: dima $
* @version          $Revision: 1880 $
* @lastmodified     $LastChangedDate: 2011-06-17 14:20:33 +0700 (Пт, 17 июн 2011) $
* @copyright        Copyright (c) 2003-2010 iJoomla.com  All rights reserved.
* @license          
* iJoomla DigiStore is not a free software. You may make changes but
* you may not sell it or distribute it License is for ONE domain only.
*/

//Front End / Processing
define('OSDCS_AUTHORIZE_FIRST_NAME','First name:');
define('OSDCS_AUTHORIZE_LAST_NAME','Last name:');
define('OSDCS_AUTHORIZE_CARD_NUMBER','Card number:');
define('OSDCS_AUTHORIZE_EXP_DATE','Card expiration date (mmyy):');
define('OSDCS_AUTHORIZE_CVV2','Card verification number:');
define('OSDCS_AUTHORIZE_EMAIL','E-mail:');
define('OSDCS_AUTHORIZE_AMOUNT','Amount:');
define('OSDCS_AUTHORIZE_SUBMIT','Make Payment >>');
define('OSDCS_AUTHORIZE_PROCESSING','Processing... Wait');
define('OSDCS_AUTHORIZE_HEADER','<h1>Enter your information below:</h1><h3>This form is secure</h3>');
define('OSDCS_AUTHORIZE_STREET','Street');
define('OSDCS_AUTHORIZE_ZIP','Zip Code');

define('OSDCS_AUTHORIZE_STATE','State');
define('OSDCS_AUTHORIZE_PHONE','Phone');
define('OSDCS_AUTHORIZE_FAX','Fax');
define('OSDCS_AUTHORIZE_COMPANY','Company');
define('OSDCS_AUTHORIZE_COUNTRY','Country');
define('OSDCS_AUTHORIZE_CITY','City');




define('OSDCS_AUTH_AIM_FAIL_B','Transaction was submitted without a billing address.');
define('OSDCS_AUTH_AIM_FAIL_E','AVS data provided is invalid or AVS is not allowed for the card type that was used.');
define('OSDCS_AUTH_AIM_FAIL_R','The AVS system was unavailable at the time of processing.');
define('OSDCS_AUTH_AIM_FAIL_G','The card issuing bank is of non-U.S. origin and does not support AVS.');
define('OSDCS_AUTH_AIM_FAIL_U','The address information for the cardholder is unavailable.');
define('OSDCS_AUTH_AIM_FAIL_S','The U.S. card issuing bank does not support AVS.');
define('OSDCS_AUTH_AIM_FAIL_N','Street Address, ZIP Code and Extended ZIP do not match the information held by the bank');
define('OSDCS_AUTH_AIM_FAIL_A','ZIP Code and Extended ZIP do not match the information held by the bank');
define('OSDCS_AUTH_AIM_FAIL_Z','ZIP Code does not match the information held by the bank');
define('OSDCS_AUTH_AIM_FAIL_W','Street Address does not match the information held by the bank');
define('OSDCS_AUTH_AIM_FAIL_Y','Extended ZIP does not match the information held by the bank');

//use for debugging problems
define('_OSDCS_DEBUG_MODE','0');
define('_OSDCS_AUTHORIZE_GODADDY','0');


?>
