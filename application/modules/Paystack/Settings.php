<?php
/**
 * PageCarton Content Management System
 *
 * LICENSE
 *
 * @category   PageCarton CMS
 * @package    Paystack_Settings
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @version    $Id: Settings.php 02.05.2013 12.02am ayoola $
 */

/**
 * @see Application_Article_Abstract
 */
 
require_once 'Application/Article/Abstract.php';


/**
 * @category   PageCarton CMS
 * @package    Paystack_Settings
 * @copyright  Copyright (c) 2011-2016 PageCarton (http://www.pagecarton.com)
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

class Paystack_Settings extends PageCarton_Settings
{
	
    /**
     * creates the form for creating and editing
     * 
     * param string The Value of the Submit Button
     * param string Value of the Legend
     * param array Default Values
     */
	public function createForm( $submitValue, $legend = null, Array $values = null )
    {
        $values = @$values['data'] ? : unserialize( @$values['settings'] );
        $form = new Ayoola_Form( array( 'name' => $this->getObjectName() ) );
		$form->submitValue = $submitValue ;
		$form->oneFieldSetAtATime = true;
		$fieldset = new Ayoola_Form_Element;

        //	auth levels
		$fieldset->addElement( array( 'name' => 'secret_key', 'label' => 'Secret Key', 'value' => @$values['secret_key'], 'type' => 'InputText' ) );
		$fieldset->addElement( array( 'name' => 'public_key', 'label' => 'Public Key', 'value' => @$values['public_key'], 'type' => 'InputText' ) );
		$fieldset->addElement( array( 'name' => 'currency', 'label' => 'Currency Code (default is NGN)', 'value' => @$values['currency'], 'type' => 'InputText' ) );  
		
		$fieldset->addLegend( 'Paystack Settings' );        
		$form->addFieldset( $fieldset );
		$this->setForm( $form );
    } 
	// END OF CLASS
}
