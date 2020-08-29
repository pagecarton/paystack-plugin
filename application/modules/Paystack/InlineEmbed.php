<?php
/**
 * PageCarton Content Management System
 *
 * LICENSE
 *
 * @category   PageCarton CMS
 * @package    Paystack_InlineEmbed
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: InlineEmbed.php 5.7.2012 11.53 ayoola $
 */

/**
 * @see Application_Subscription_Abstract
 */
 
require_once 'Application/Subscription/Abstract.php';


/**
 * @category   PageCarton CMS
 * @package    Paystack_InlineEmbed
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

class Paystack_InlineEmbed extends Paystack
{
		
    /**
     * Whitelist and blacklist of currencies
     * 
     * @var array
     */
	protected static $_currency= array( 'whitelist' => '₦,NGN', 'blacklist' => 'ALL' ); 

    /**
     * Form Action
     * 
     * @var string
     */
	protected static $_formAction = '';
	
    /**
     * The method does the whole Class Process
     * 
     */
	protected function init()
    {		
		
		self::$_apiName = $this->getParameter( 'checkoutoption_name' ) ? : array_pop( explode( '_', get_class( $this ) ) );
		if( ! $cart = self::getStorage()->retrieve() ){ return; }
		$values = $cart['cart'];

		$parameters = static::getDefaultParameters();
	
        $parameters['email'] = Ayoola_Form::getGlobalValue( 'email' ) ? : ( Ayoola_Form::getGlobalValue( 'email_address' ) ? : Ayoola_Application::getUserInfo( 'email' ) );
        if( ! empty( $cart['checkout_info']['email_address'] ) )
        {
            $parameters['email'] = $cart['checkout_info']['email_address'];
        }
        if( empty( $parameters['email'] ) )
        {
            $form = new Ayoola_Form();
            $form->submitValue = 'Continue to Paystack';
            $fieldset = new Ayoola_Form_Element();
            $fieldset->addElement( array(
                'name' => 'email',
                'label' => 'Billing E-mail Address',
                'placeholder' => 'e.g. example@email.com',
                'type' => 'InputText'
    
            ) );
            $form->addFieldset( $fieldset );


                $this->setViewContent( $form->view() );
            if( ! $em = $form->getValues() )
            {
                return false;
            }
            $parameters['email'] = $em['email'];
        }
		$parameters['reference'] = $this->getParameter( 'reference' ) ? : $parameters['order_number'];
		$parameters['key'] = Paystack_Settings::retrieve( 'public_key' );
		$parameters['currency'] = Paystack_Settings::retrieve( 'currency' );
		$counter = 1;
		$parameters['price'] = 0.00;
		foreach( $values as $name => $value )
		{
			if( ! isset( $value['price'] ) )
			{
				$value = array_merge( self::getPriceInfo( $value['price_id'] ), $value );
			}  
			@@$parameters['prod'] .= ' ' . $value['multiple'] . ' x ' . $value['subscription_label'];
			@$parameters['price'] += floatval( $value['price'] * $value['multiple'] );
			$counter++;
		}
		$parameters['amount'] = ( $this->getParameter( 'amount' ) ? : $parameters['price'] ) * 100;
		$parameters['plan'] = $this->getParameter( 'plan' );

		$this->setViewContent( '<form action="' . $parameters['success_url'] . '" method="POST" >
								<script src="https://js.paystack.co/v1/inline.js"></script>
								<div id="paystackEmbedContainer"><img style="margin: 0 auto; max-width: 500px; padding: 2em;" src="' . Ayoola_Application::getUrlPrefix() . '/img/loading.gif" alt="Loading Credit Card Payment with Paystack"></div>

								<script>
								  PaystackPop.setup({
								   key: "' . $parameters['key'] . '",
								   email: "' . $parameters['email'] . '",
								   amount: ' . $parameters['amount'] . ',
								   plan: "' . $parameters['plan'] . '",
								   currency: "' . $parameters['currency'] . '",
								   reference: "' . $parameters['reference'] . '",
								   container: "paystackEmbedContainer",
								   callback: function(response){
										location.href = "' . $parameters['success_url'] . '?ref=" + response.reference;
									},
									}
								  );
								</script>
								</form>
		' );
    } 	
	
    /**
     * Returns _formAction
     * 
     */
	static function checkStatus( $orderNumber )
    {		
		$table = new Application_Subscription_Checkout_Order();
		if( ! $orderInfo = $table->selectOne( null, array( 'order_id' => $orderNumber ) ) )
		{
			return false;
		}
		if( ! is_array( $orderInfo['order'] ) )
		{
			//	compatibility
			$orderInfo['order'] = unserialize( $orderInfo['order'] );			
		}
		$orderInfo['total'] = 0;

		foreach( $orderInfo['order']['cart'] as $name => $value )
		{
			if( ! isset( $value['price'] ) )
			{
				$value = array_merge( self::getPriceInfo( $value['price_id'] ), $value );
			}  
			$orderInfo['total'] += $value['price'] * $value['multiple'];
		}
		
		$secretKey = Paystack_Settings::retrieve( 'secret_key' );
		$result = array();
		
		//The parameter after verify/ is the transaction reference to be verified
		$url = 'https://api.paystack.co/transaction/verify/' . $_REQUEST['ref']; 

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt(
		  $ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey]
		);
		$request = curl_exec($ch);
		curl_close($ch);

		if ($request) {
		  $result = json_decode($request, true);   
        }
		if( empty( $result['status'] ) )
		{
			//	Payment was not successful.
			$orderInfo['order_status'] = 0;
		}
		else
		{
			$orderInfo['order_status'] = 99;
		}

		$orderInfo['order_random_code'] = $_REQUEST['ref'];
		$orderInfo['gateway_response'] = $result;


		self::changeStatus( $orderInfo );
		
		//	Code to change check status goes heres
		return $orderInfo;
    }

	// END OF CLASS
}
