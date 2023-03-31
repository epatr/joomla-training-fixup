<?php
/* SVN FILE:       $Id: payauthorize.php 1880 2011-06-17 07:20:33Z dima $ */
/**
* Project Name :    Plugin Authorize.Net for Digistore for Joomla 1.5.x
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

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.application.menu' );

include('authorize.files/language.php');

class plgGurupaymentPayauthorize extends JPlugin{
	var $_db = null;

	function plgPaymentPayauthorize(&$subject, $config){
		$this->_db = JFactory :: getDBO();
		parent :: __construct($subject, $config);
	}
	
	function onSendPayment(&$post){
		if($post['processor'] != 'payauthorize'){
			return false;
		}	

		if($post['params']){
			$params = new JRegistry($post['params']);
		}
		else{
			$params = $this->params;
		}

		$db =& JFactory::getDBO();
		$customer_id = $post["customer_id"];
		$sql = "select gc.*, u.email from #__guru_customer gc, #__users u where u.id=".intval($customer_id)." and gc.id=u.id";
		$db->setQuery($sql);
		$db->query();
		$customer_details = $db->loadAssocList();
 
		$firstname = $customer_details["0"][''];
		$lastname = $customer_details["0"]['lastname'];
		$email = $customer_details["0"]['email'];
		$company = $customer_details["0"]['company'];
	
		$amount = "0.00";

		if(isset($post["products"])){
			$products = $post["products"];
			if(isset($products) && count($products) > 0){
				foreach($products as $key=>$value){
					if(trim($value["value"]) != ""){
						$amount += trim($value["value"]);
					}
				}
			}
			$discount = $post["order_amount"];
			if(isset($discount)){
				$amount -= $discount;
			}
			if($amount < 0){
				$amount = "0.00";
			}
		}
		$currency = trim($post["order_currency"]);
		$sign = JText::_("GURU_CURRENCY_".$currency);
		$amount_with_sign = $sign.$amount;

		include(JPATH_COMPONENT_SITE.DS.'helpers'.DS.'form.creator.php');

		$url = "index.php?option=com_guru&controller=guruBuy&task=payment";
		
		$form = new guruFormCreator('PayAutorizePayment', $url);
		$form->addHeader(OSDCS_AUTHORIZE_HEADER);
		$form->setDivID('PayAutorizePaymentMainDiv');
		$form->setDivStyle('border-radius: 4px;  box-shadow: 0 0 1px 0 rgba(0, 0, 0, 0.4);   padding: 15px;');
		$form->setTableID('PayAutorizePaymentMainTable');
		$form->setTableStyle('width:100%');
		
		$card_nr_value = "";
		if(isset($_SESSION["creditCardNumber"])){
			$card_nr_value = trim($_SESSION["creditCardNumber"]);
		}
		
		$form->addCardNumberField($card_nr_value);
		
		$form->addExpireDateField();
		
		$cvv2Number = "";
		if(isset($_SESSION["cvv2Number"])){
			$cvv2Number = trim($_SESSION["cvv2Number"]);
		}
		$form->addCvvField($cvv2Number, 'cvv2Number', OSDCS_AUTHORIZE_CVV2);
		$form->addTextField('Name On Card', $firstname, OSDCS_AUTHORIZE_FIRST_NAME);
		//$form->addTextField('lastName', $lastname, OSDCS_AUTHORIZE_LAST_NAME);
		$form->addEmailField($email, 'email', OSDCS_AUTHORIZE_EMAIL);
		
		if($params->get($post['processor'].'_company' ) > 0){
			$form->addTextField('company', $company, OSDCS_AUTHORIZE_COMPANY, false);
		}	

		if(($params->get($post['processor'] . '_country' ) > 0) || ($params->get( $post['processor'] . '_state' ) > 0)){
			$foundCoutntry = $form->findCountryByName($country);
			$foundState = $form->findStateByName($foundCoutntry, $state);
			$form->addSelectCountryField($foundCoutntry, $foundState, true, false, 'country', 'state');
		}
		
		
		$req_street = ($params->get($post['processor'] . '_street') == 2 );
		
		if($params->get( $post['processor'] . '_street' ) > 0){
			$form->addTextField( 'street', '', OSDCS_AUTHORIZE_STREET, $req_street );
		}	

		if($params->get( $post['processor'] . '_city' ) > 0){
			$form->addTextField('city', $city, OSDCS_AUTHORIZE_CITY, false);
		}	

		$req_zip = ($params->get($post['processor'] . '_zip') == 2);
		
		if($params->get( $post['processor'] . '_zip' ) > 0){
			$form->addTextField('zip', $zipcode, OSDCS_AUTHORIZE_ZIP, $req_zip);
		}

		if($params->get( $post['processor'] . '_phone' ) > 0){
			$form->addPhoneField( '', 'phone', OSDCS_AUTHORIZE_PHONE, false );
		}
			
		if($params->get( $post['processor'] . '_fax' ) > 0){
			$form->addPhoneField('', 'fax', OSDCS_AUTHORIZE_FAX, false);
		}
			
		$form->addCustomText($amount_with_sign, OSDCS_AUTHORIZE_AMOUNT);
		$form->addHiddenField( 'amount', $amount );
		$form->addHiddenField( 'processor', $post['processor'] );
		$form->addHiddenField( 'pay', 'direct' );
		$form->addHiddenField( 'currency', $currency );
		$form->addHiddenField( 'order_id', $post['order_id'] );
		$form->addHiddenField( 'sid', $post['sid'] );
		$form->addHiddenField( 'user_id', $post['user_id'] );
		$form->addHiddenField( 'hash', md5( $post['order_id'] . ":" . $post['user_id'] . ":$amount:$currency:" . $params->get( $post['processor'] . '_secret' ) ) );
		$form->addSubmitButton(OSDCS_AUTHORIZE_SUBMIT);
		
		$return = $form->toString();
		return $return;
	}

	function onReceivePayment(&$post){
		$processor = $post['processor'];
		if($processor != 'payauthorize'){
			return false;
		}

		if($post['params']){
			$params = new JRegistry( $post['params'] );
		}
		else{
			$params = $this->params;
		}

		$pay = $post['pay'];
		
		switch($pay){
			case "direct":
				if(md5($post['order_id'].":".$post['user_id'].":".$post['amount'].":".$post['currency'].":".$params->get($post['processor'].'_secret')) == $post['hash']){
					$txn_id = date("Y-m-D H:i:s")."_".$post['order_id'];
					$order['price'] = $post['amount'];
					$order['user_id'] = $post['user_id'];
					$order['sid'] = $post['order_id'];
					$order['processor'] = $processor;
					$order['gateway_id'] = $txn_id;

					$ccnum = $post['creditCardNumber'];
					$ccnum = str_replace(" ", "", $ccnum);
					$ccnum = str_replace("-", "", $ccnum);
					$ccnum = str_replace(",", "", $ccnum);
					$params2 = "x_login=" . urlencode( $params->get( $post['processor'] . '_login' ) )
					. "&x_tran_key=" . urlencode( $params->get( $post['processor'] . '_key' ) )
					. "&x_version=3.1"
					. "&x_type=AUTH_CAPTURE"
					. "&x_method=CC"
					. "&x_recurring_billing=FALSE"
					. "&x_amount=" . urlencode( trim( $post['amount'] ) )
					. "&x_card_num=" . urlencode( $ccnum )
					. "&x_exp_date=" . urlencode( $post['expDateMonth'] . $post['expDateYear'] )
					. "&x_card_code=" . $post['cvv2Number']
					. "&x_invoice_num=" . urlencode( $txn_id )
					. "&x_description=" . urlencode( "Payment to " )
					. "&x_first_name=" . urlencode( $post['firstName'] )
					. "&x_last_name=" . urlencode( $post['lastName'] )
					. "&x_email=" . ($post['email'])
					. "&x_address=" . urlencode( (isset( $post['street'] ) ? $post['street'] : '' ) )
					. "&x_zip=" . urlencode( (isset( $post['zip'] ) ? $post['zip'] : '' ) )
					. "&x_company=" . urlencode( (isset( $post['company'] ) ? $post['company'] : '' ) )
					. "&x_state=" . urlencode( (isset( $post['state'] ) ? $post['state'] : '' ) )
					. "&x_country=" . urlencode( (isset( $post['country'] ) ? $post['country'] : '' ) )
					. "&x_city=" . urlencode( (isset( $post['city'] ) ? $post['city'] : '' ) )
					. "&x_phone=" . urlencode( (isset( $post['phone'] ) ? $post['phone'] : '' ) )
					. "&x_fax=" . urlencode( (isset( $post['fax'] ) ? $post['fax'] : '' ) )
					. "&x_delim_data=TRUE"
					. "&x_delim_char=|";

					if($params->get($post['processor'] . '_test') == "2"){
						$params2.='&x_test_request=TRUE';
					}

					$host = 'secure.authorize.net';
					if($params->get( $post['processor'] . '_test') == "1"){
						$host = 'test.authorize.net';
					}

					$port = 443;
					$path = "/gateway/transact.dll";

					if(function_exists("curl_init")){
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_URL, "https://" . $host . $path );
						curl_setopt( $ch, CURLOPT_HEADER, 0 );
						curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
						curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
						curl_setopt( $ch, CURLOPT_POST, 1 );
						curl_setopt( $ch, CURLOPT_POSTFIELDS, $params2 );
						if(_OSDCS_AUTHORIZE_GODADDY == '1'){
							curl_setopt( $ch, CURLOPT_HTTPPROXYTUNNEL, TRUE );
							curl_setopt( $ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP );
							curl_setopt( $ch, CURLOPT_PROXY, "http://proxy.shr.secureserver.net:3128" );
							curl_setopt( $ch, CURLOPT_TIMEOUT, 120 );
						}

						$result = curl_exec($ch);

						if(_OSDCS_DEBUG_MODE == '1'){
							echo "stage 1 debug. Params:<pre>";
							print_r( $params2 );
							echo "</pre><br />";
							echo "Result:" . $result . "<hr>";
						}
						$error = curl_error( $ch );
						if(_OSDCS_DEBUG_MODE == '1'){
							echo "stage 2 debug. curl error:<pre>";
							print_r( $error );
							echo "</pre><br />";
							echo "remote Host:https://" . $host . $path;
							echo "<hr>";
							die();
						}

						curl_close($ch);
						if(!empty($error)){
							$order['pay'] = "fail";
							$order['sid'] = -1;
							$order['msg'] = $error;
						}
					}
					else{
						$fp = fsockopen( "ssl://" . $host, $port, $errno, $errstr, $timeout = 60 );
						if(!$fp){
							$order['sid'] = "-1";
							$order['pay'] = "fail";
							$order['sid'] = -1;
							return $order;
						}
						else{
							fputs( $fp, "POST $path HTTP/1.1\r\n" );
							fputs( $fp, "Host: $host\r\n" );
							fputs( $fp, "Content-type: application/x-www-form-urlencoded\r\n" );
							fputs( $fp, "Content-length: " . strlen( $params2 ) . "\r\n" );
							fputs( $fp, "Connection: close\r\n\r\n" );
							fputs( $fp, $params2 . "\r\n\r\n" );
							$str = '';
							while(!feof($fp) && !stristr($str, 'content-length')){
								$str = fgets($fp, 4096);
							}
							
							if(!stristr($str, 'content-length')){
								$order['pay'] = "fail";
								$order['sid'] = -1;
								return $order;
							}
							$data = "";
							while(!feof($fp)){
								$data .= fgets( $fp, 1024 );
							}
							$result = trim( $data );
							fclose( $fp );
						}
					}
					
			/* Custom code starting */
					
			$db = JFactory::getDbo();
			$and = "";
			$sql = "SELECT DISTINCT t1.`id` , t1.`name` , t2.`course_id`, t2.`price`, t2.`order_id` FROM #__guru_program t1 ,#__guru_buy_courses t2, #__guru_order t3  WHERE t2.`order_id` = '".$order['sid']."' and t1.`id` = t2.`course_id`";
			$db->setQuery($sql);
			$db->execute();
			$orders = $db->loadAssocList();
			$testme = array();
			foreach ($orders as $xxx) 
			 {
				 $deviceIds[] = $xxx;
				 $cprice[] = $xxx['price'];
				 $sum = array_sum($cprice);
				 $dis = $sum - $post['amount'];			 
               }	
					 
                        $data = array(
                            'results' =>  $result,
                           'ProductName' => $deviceIds,
                            'discount_total' => round($dis),
                            'finalprice' =>  $post['amount']												
                            );
                        
                        $payload = json_encode($data);
                        
                        // Prepare new cURL resource
                        
                        $ch = curl_init('http://paperlesswebapi.paperlessinspectors.com/api/PostIDDotNetData');
                        //$ch = curl_init('http://api.webhookinbox.com/i/9wJmM1YB/in/');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                        
                        // Set HTTP Header for POST request 
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($payload))
                        );
                        
                        // Submit the POST request
                        $data = curl_exec($ch);
                        
                        // Close cURL session handle
                        curl_close($ch);
						

			         /* Custom code ending */
			
					$response = explode("|", $result);
					
					

					if($response[0] == "1"){
						$order['pay'] = "success";
						
						//$order['msg'] = $result;

						
						
					}
					else{
						switch($response[5]){
							case 'B':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_B;
								break;
							case 'E':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_E;
								break;
							case 'R':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_R;
								break;
							case 'G':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_G;
								break;
							case 'U':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_U;
								break;
							case 'S':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_S;
								break;
							case 'N':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_N;
								break;
							case 'A':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_A;
								break;
							case 'Z':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_Z;
								break;
							case 'W':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_W;
								break;
							case 'Y':
								$order['msg'] = OSDCS_AUTH_AIM_FAIL_Y;
								break;
							default :
								$order['msg'] = $response[3];
								break;
						}
					}
				}
				else{
				}
				break;
			default:
				break;
		}
		return $order;
	}

	
}
?>