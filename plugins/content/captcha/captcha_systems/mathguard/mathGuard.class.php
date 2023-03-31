<?php
// no direct access
defined ( '_JEXEC' ) or die ( 'Restricted access' ); 
use Joomla\Utilities\ArrayHelper as JArrayHelper;

if(!class_exists('mathGuardImproved')) {
	class mathGuardImproved extends JObject{
	
		/** A main hashing function: concat of user's answer, hour and the additional prime number (default 37) */
		function encode($input, $prime) {
			return md5($input.date("H").$prime);
		}
	
		/** This function generates the hash code from the two numbers 
		 * @param $a 	first number
		 * @param $b	second sumber
		 * @param $prime	additional number to encode with
		 * */
		function generateCode($a, $b, $prime) {
			$code = mathGuardImproved::encode($a + $b, $prime);
			return $code;
		}
	
		/** This function checks whether the answer and generated security code match 
		 * @param $mathguard_answer		answer the user has entered
		 * @param $mathguard_code		hashcode the mathguard has generated
		 */
		function checkResult($mathguard_answer, $suffix= '', $prime = 37) {
	
	//		echo("prime; $prime, $mathguard_answer");
			//saving in session, there is no need to encode the code anymore
			//$result_encoded = mathGuardImproved::encode($mathguard_answer, $prime);
			
			$session = JFactory::getSession();
			if ($mathguard_answer == $session->get( 'mathguard_code'.$suffix, null ))
				return true;
			else
				return false;
	
		}
	
		/** this function inserts the two math term into your form, the parameter is optional */
		function insertQuestion($attributes=array(),$prime = 37) { //default prime is 37, you can change it when specifying the different parameter
			echo mathGuardImproved::returnQuestion($attributes,$prime);
		}
	
		/** this function returns math expression into your form, the parameter is optional 
		 * quite simmilar to insertQuestion, but returns the output as a text instead of echoing
		 */
		function returnQuestion($attributes=array(),$suffix='', $prime = 37) { //default prime is 37, you can change it when specifying the different parameter
			$a = rand() % 10; // generates the random number
			$b = rand() % 10; // generates the random number
			//saving in session, there is no need to encode the code anymore
			//$code = mathGuardImproved :: generateCode($a, $b, $prime);
			$code = $a + $b;
			if(!is_array($attributes)){
				$attributes = array();
			}
			if(!isset($attributes['name'])){
				$attributes['name']	= 'captcha_code';
			}
			if(!isset($attributes['id'])){
				$attributes['id']	= $attributes['name'];
			}
			if(!isset($attributes['size'])){
				$attributes['size']	= '2';
			}
			if(!isset($attributes['class'])){
				$attributes['class']	= 'inputbox mathguard-answer required';
			}
			$attributes	= JArrayHelper::toString($attributes);
			
			$session = JFactory::getSession();
			$session->set( 'mathguard_time'.$suffix, time());
			$session->set( 'mathguard_code'.$suffix, $code);
			
			return $a .' + '. $b .' = '
				 . '<input type="text" '.$attributes.' />';
				 //. '<input type="hidden" name="mathguard_code" value="'.$code.'" />';
				 
		}
	
	}
}
?>