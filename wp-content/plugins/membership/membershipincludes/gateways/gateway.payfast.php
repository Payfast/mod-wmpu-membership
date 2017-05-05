<?php
/*
Addon Name: PayFast Payments Gateway
Author: Incsub
Author URI: https://www.payfast.co.za
Gateway ID: payfast
Copyright (c) 2008 PayFast (Pty) Ltd
You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
*/

class payfast extends Membership_Gateway {

	var $gateway = 'payfast';
	var $title = 'PayFast Payments';
	var $issingle = true;

	public function __construct() {
		parent::__construct();

		add_action( 'M_gateways_settings_' . $this->gateway, array( &$this, 'mysettings' ) );

		// If I want to override the transactions output - then I can use this action
		//add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if ( $this->is_active() ) {
			// Subscription form gateway
			add_action( 'membership_purchase_button', array( &$this, 'display_subscribe_button' ), 1, 3 );

			// Payment return
			add_action( 'membership_handle_payment_return_' . $this->gateway, array( &$this, 'handle_payfast_return' ) );
			add_filter( 'membership_subscription_form_subscription_process', array( &$this, 'signup_free_subscription' ), 10, 2 );
		}
	}

	function mysettings() {

		global $M_options;

		?>
		<h3><?php _e('IPN Setup Instructions', 'membership'); ?></h3>
		<p><?php printf(__('In order for Membership to function correctlty you must setup an IPN listening URL with PayPal. Failure to do so will prevent your site from being notified when a member cancels their subscription.<br />Your IPN listening URL is: <strong>%s</strong><br /><a target="_blank" href="https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/">Instructions &raquo;</a></p></p>', 'membership'), trailingslashit(home_url('paymentreturn/' . $this->gateway))); ?></p>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
		  <th scope="row"><?php _e('PayFast Merchant ID', 'membership') ?></th>
		  <td><input type="text" name="payfast_merchant_id" value="<?php esc_attr_e(get_option( $this->gateway . "_payfast_merchant_id" )); ?>" />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('PayFast Merchant Key', 'membership') ?></th>
		  <td><input type="text" name="payfast_merchant_key" value="<?php esc_attr_e(get_option( $this->gateway . "_payfast_merchant_key" )); ?>" />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('PayFast Sandbox', 'membership') ?></th>
		  <td><select name="payfast_sandbox">
		  <option value="live" <?php if (get_option( $this->gateway . "_payfast_sandbox" ) == 'live') echo 'selected="selected"'; ?>><?php _e('Live Site', 'membership') ?></option>
		  <option value="test" <?php if (get_option( $this->gateway . "_payfast_sandbox" ) == 'test') echo 'selected="selected"'; ?>><?php _e('Test Mode (Sandbox)', 'membership') ?></option>
		  </select>
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('PayFast Debug', 'membership') ?></th>
		  <td><select name="payfast_debug">
		  <option value="on" <?php if (get_option( $this->gateway . "_payfast_debug" ) == 'on') echo 'selected="selected"'; ?>><?php _e('Debug On', 'membership') ?></option>
		  <option value="off" <?php if (get_option( $this->gateway . "_payfast_debug" ) == 'off') echo 'selected="selected"'; ?>><?php _e('Debug Off', 'membership') ?></option>
		  </select>
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Subscription button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_payfast_button", 'https://www.payfast.co.za/images/logo/paynow-dark.png' );
		  ?>
		  <td><input type="text" name="payfast_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Renew button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_payfast_renew_button", 'https://www.payfast.co.za/images/logo/paynow-dark.png' );
		  ?>
		  <td><input type="text" name="_payfast_renew_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Upgrade button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_payfast_upgrade_button", 'https://www.payfast.co.za/images/logo/paynow-dark.png' );
		  ?>
		  <td><input type="text" name="_payfast_upgrade_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Cancel button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_payfast_cancel_button", 'https://www.payfast.co.za/images/logo/paynow-dark.png' );
		  ?>
		  <td><input type="text" name="_payfast_cancel_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Secret Passphrase', 'membership') ?></th>
		
		  <td>
		  The secret passphrase is an extra level of security and should only be filled in if you have already set it in the 'Settings' 
		  area of you PayFast account.<br> <span style='font-weight:bold;'>This should never be shared or displayed to anyone.!!!</span><br>
		  <input type="text" name="_payfast_cancel_button" value="<?php esc_attr_e(get_option( $this->gateway . "_payfast_passphrase", '' )); ?>" />
		  <br />
		  </td>
		  </tr>
		</tbody>
		</table>
		<?php
	}

	function build_custom( $user_id, $sub_id, $amount, $sublevel = 0, $fromsub = 0 ) {
		global $M_options;

		$custom = time() . ':' . $user_id . ':' . $sub_id . ':';
		$key = md5( 'MEMBERSHIP' . apply_filters( 'membership_amount_' . $M_options['paymentcurrency'], $amount ) );

		if ( $fromsub === false ) {
			$fromsub = filter_input( INPUT_GET, 'from_subscription', FILTER_VALIDATE_INT );
		}

		$custom .= $key;
		$custom .= ":" . $sublevel . ":" . $fromsub;

		return $custom;
	}

	function single_button($pricing, $subscription, $user_id, $sublevel = 0, $fromsub = 0) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) 
		{
			$M_options['paymentcurrency'] = 'ZAR';
		}

		$html = '';

		if ( get_option( $this->gateway . "_payfast_sandbox" ) == 'live') 
		{
			$html .= '<form action="https://www.payfast.co.za/eng/process" method="post">';
			$merchantId = esc_attr( get_option( $this->gateway . "_payfast_merchant_id" ) );
			$merchantKey = esc_attr( get_option( $this->gateway . "_payfast_merchant_key" ) );
		} 
		else 
		{
			$html .= '<form action="https://sandbox.payfast.co.za/eng/process" method="post">';
			$merchantId = '10001224';
			$merchantKey = '0qz3y3wtoddq1';
		}
		$varArray = array(
            'merchant_id'=> $merchantId,
            'merchant_key'=> $merchantKey,
            'return_url'=> apply_filters( 'membership_return_url_' . $this->gateway, M_get_returnurl_permalink()),
            'cancel_url'=> apply_filters( 'membership_cancel_url_' . $this->gateway, M_get_subscription_permalink()),
            'notify_url'=> apply_filters( 'membership_notify_url_' . $this->gateway, home_url('paymentreturn/' . esc_attr($this->gateway))),
            'm_payment_id'=> $subscription->id,
            'amount'=>apply_filters( 'membership_amount_' . $M_options['paymentcurrency'], number_format($pricing[$sublevel -1]['amount'], 2, '.' , '')),
            'item_name'=>$subscription->sub_name(),
            'custom_str1' => $this->build_custom( $user_id, $subscription->id, number_format($pricing[$sublevel -1]['amount'], 2, '.' , ''), $sublevel, $fromsub)
        );
        $secureString = '';
        foreach($varArray as $k=>$v)
        {
            $html.= '<input type="hidden" name="'.$k.'" value="'.$v.'" />';
            $secureString .= $k.'='.urlencode($v).'&';
        }
       
        $secureString = substr( $secureString, 0, -1 );

        $passphrase = get_option( $this->gateway . "_payfast_passphrase", '' );
        if( !empty( $passphrase ) )
		{
		  $secureString .= '&passphrase='.$passphrase;
		}

        $secureSig = md5($secureString);

        if($sublevel == 1) {
			$button = get_option( $this->gateway . "_payfast_button", '//www.payfast.co.za/images/logo/paynow-dark.png' );
		} else {
			$button = get_option( $this->gateway . "_payfast_button", '//www.payfast.co.za/images/logo/paynow-dark.png' );
		}
        
        $html .= '<input type="hidden" name="signature" value="'.$secureSig.'" />';
        $html .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="PayFast">';
		$html .= '</form>';		

		return $html;

	}

	function signup_free_subscription($content, $error) {

		if(!isset($_POST['action']) || $_POST['action'] != 'validatepage2') {
			return $content;
		}

		if(isset($_POST['custom'])) {
			list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);
		}

		// create_subscription
		$member = Membership_Plugin::factory()->get_member($user_id);
		if($member) {
			$member->create_subscription($sub_id, $this->gateway);
		}

		do_action('membership_payment_subscr_signup', $user_id, $sub_id);

		$content .= '<div id="reg-form">'; // because we can't have an enclosing form for this part

		$content .= '<div class="formleft">';

		$message = get_option( $this->gateway . "_completed_message", $this->defaultmessage );
		$content .= stripslashes($message);

		$content .= '</div>';

		$content .= "</div>";

		$content = apply_filters('membership_subscriptionform_signedup', $content, $user_id, $sub_id);

		return $content;

	}

	function single_free_button($pricing, $subscription, $user_id, $sublevel = 0) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'ZAR';
		}

		$form = '';

		$form .= '<form action="' . M_get_returnurl_permalink() . '" method="post">';
		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, '0', $sublevel) .'">';

		if($sublevel == 1) {
			$form .= '<input type="hidden" name="action" value="subscriptionsignup" />';
			$form .=  wp_nonce_field('free-sub_' . $subscription->sub_id(), "_wpnonce", true, false);
			$form .=  "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";

			$button = get_option( $this->gateway . "_payment_button", '' );
			if( empty($button) ) {
				$form .= '<input type="submit" class="button ' . apply_filters('membership_subscription_button_color', 'blue') . '" value="' . __('Sign Up','membership') . '" />';
			} else {
				$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
			}

		} else {
			$form .=  wp_nonce_field('renew-sub_' . $subscription->sub_id(), "_wpnonce", true, false);
			$form .=  "<input type='hidden' name='action' value='subscriptionsignup' />";
			$form .=  "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
			$form .=  "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
			$form .=  "<input type='hidden' name='user' value='" . $user_id . "' />";
			$form .=  "<input type='hidden' name='level' value='" . $sublevel . "' />";

			$button = get_option( $this->gateway . "_payment_button", '' );
			if( empty($button) ) {
				$form .= '<input type="submit" class="button ' . apply_filters('membership_subscription_button_color', 'blue') . '" value="' . __('Sign Up','membership') . '" />';
			} else {
				$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
			}
		}

		$form .= '</form>';

		return $form;

	}

	function build_subscribe_button($subscription, $pricing, $user_id, $sublevel = 1, $fromsub = 0) {

		if(!empty($pricing)) {
			// check to make sure there is a price in the subscription
			// we don't want to display free ones for a payment system

			if( isset($pricing[$sublevel - 1]) ) {
				if( empty($pricing[$sublevel - 1]) || $pricing[$sublevel - 1]['amount'] == 0 ) {
					// It's a free level
					return $this->single_free_button($pricing, $subscription, $user_id, $sublevel);
				} else {
					// It's a paid level
					return $this->single_button($pricing, $subscription, $user_id, $sublevel, $fromsub);
				}
			}

		}

	}

	function display_upgrade_from_free_button($subscription, $pricing, $user_id, $fromsub_id = false) {
		if($pricing[0]['amount'] < 1) {
			// a free first level
			$this->display_upgrade_button($subscription, $pricing, $user_id, $fromsub_id);
		} else {
			echo $this->build_subscribe_button($subscription, $pricing, $user_id, $fromsub_id);
		}

	}

	function display_upgrade_button($subscription, $pricing, $user_id, $fromsub_id = false) {

		echo '<form class="upgradebutton" action="' . M_get_subscription_permalink() . '" method="post">';
		wp_nonce_field('upgrade-sub_' . $subscription->sub_id());
		echo "<input type='hidden' name='action' value='upgradesolo' />";
		echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
		echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
		echo "<input type='hidden' name='user' value='" . $user_id . "' />";
		echo "<input type='hidden' name='fromsub_id' value='" . $fromsub_id . "' />";
		echo "<input type='submit' name='submit' value=' " . __('Upgrade', 'membership') . " ' class='button blue' />";
		echo "</form>";
	}

	function display_cancel_button($subscription, $pricing, $user_id) {

		echo '<form class="unsubbutton" action="' . M_get_subscription_permalink() . '" method="post">';
		wp_nonce_field('cancel-sub_' . $subscription->sub_id());
		echo "<input type='hidden' name='action' value='unsubscribe' />";
		echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
		echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
		echo "<input type='hidden' name='user' value='" . $user_id . "' />";
		echo "<input type='submit' name='submit' value=' " . __('Unsubscribe', 'membership') . " ' class='button blue' />";
		echo "</form>";
	}

	function display_subscribe_button($subscription, $pricing, $user_id, $sublevel = 1) {
		echo $this->build_subscribe_button($subscription, $pricing, $user_id, $sublevel);
	}

	function update() {

		if(isset($_POST['payfast_sandbox'])) {
			update_option( $this->gateway . "_payfast_merchant_id", ( isset( $_POST[ 'payfast_merchant_id' ] ) ? $_POST[ 'payfast_merchant_id' ] : '' ) );
			update_option( $this->gateway . "_payfast_merchant_key",( isset( $_POST[ 'payfast_merchant_key' ] ) ? $_POST[ 'payfast_merchant_key' ] : '' )  );
			update_option( $this->gateway . "_payfast_passphrase",( isset( $_POST[ 'payfast_passphrase' ] ) ? $_POST[ 'payfast_passphrase' ] : '' )  );
			//update_option( $this->gateway . "_currency", (isset($_POST[ 'currency' ])) ? $_POST[ 'currency' ] : '' );
			update_option( $this->gateway . "_payfast_sandbox", $_POST[ 'payfast_sandbox' ] );
			update_option( $this->gateway . "_payfast_debug", $_POST[ 'payfast_debug' ] );
			update_option( $this->gateway . "_payfast_button", $_POST[ 'payfast_button' ] );
			update_option( $this->gateway . "_payfast_upgrade_button", $_POST[ '_payfast_upgrade_button' ] );
			update_option( $this->gateway . "_payfast_cancel_button", $_POST[ '_payfast_cancel_button' ] );
			update_option( $this->gateway . "_payfast_renew_button", $_POST[ '_payfast_renew_button' ] );
			if ( isset( $_POST[ 'completed_message' ] ) ) {
				update_option( $this->gateway . "_completed_message", $_POST[ 'completed_message' ] );
			}
		}

		// default action is to return true
		return true;

	}

	// IPN stuff
	function handle_payfast_return() 
	{
		// PayPal IPN handling code

		if ( isset( $_POST['pf_payment_id'] )  && isset( $_POST['custom_str1'] ) ) 
		{
			define( 'PF_DEBUG', ( get_option( $this->gateway . "_payfast_debug" ) == 'on' ? true : false ) );
			include('payfast_common.inc');

			$pfHost = get_option( $this->gateway . "_payfast_sandbox" ) == 'live' ? 'www.payfast.co.za' : 'sandbox.payfast.co.za';

			pflog( __('Received PayFast ITN from - ' , 'membership') . $pfHost );			

			$pfError = false;
			$pfErrMsg = '';
			$pfDone = false;
			$pfData = array();      
			$pfParamString = ''; 

			if( !$pfError && !$pfDone )
	        {
	            header( 'HTTP/1.0 200 OK' );
	            flush();
	        }

	         //// Get data sent by PayFast
	        if( !$pfError && !$pfDone )
	        {
	            pflog( 'Get posted data' );
	        
	            // Posted variables from ITN
	            $pfData = pfGetData();
	        	list($timestamp, $user_id, $sub_id, $key, $sublevel, $fromsub) = explode(':', $pfData['custom_str1']);

	            pflog( 'PayFast Data: '. print_r( $pfData, true ) );
	        
	            if( $pfData === false )
	            {
	                $pfError = true;
	                $pfErrMsg = PF_ERR_BAD_ACCESS;
	            }
	        }
	       
	        //// Verify security signature
	        if( !$pfError && !$pfDone )
	        {
	            pflog( 'Verify security signature' );
	        
	            // If signature different, log for debugging
	            if( !pfValidSignature( $pfData, $pfParamString ) )
	            {
	                $pfError = true;
	                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
	            }
	        }
	    
	        //// Verify source IP (If not in debug mode)
	        if( !$pfError && !$pfDone )
	        {
	            pflog( 'Verify source IP' );
	        
	            if( !pfValidIP( $_SERVER['REMOTE_ADDR'] ) )
	            {
	                $pfError = true;
	                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
	            }
	        }
	        
	        
	        //// Verify data received
	        if( !$pfError )
	        {
	            pflog( 'Verify data received' );
	        
	            $pfValid = pfValidData( $pfHost, $pfParamString );
	        
	            if( !$pfValid )
	            {
	                $pfError = true;
	                $pfErrMsg = PF_ERR_BAD_ACCESS;
	            }
	        }
	        
	        //// Check data against internal order
	        if( !$pfError && !$pfDone )
	        {
	            pflog( 'Check data against internal order' );

	            $factory = Membership_Plugin::factory();

	            $amount = $pfData['amount_gross'];
				// case: successful payment
				$newkey = md5('MEMBERSHIP' . $amount);
				if( $key != $newkey ) {
					$member = $factory->get_member($user_id);
					if( $member) 
					{
						if(defined('MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION') && MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION == true ) {
							$member->deactivate();
						}
					}
					$pfError = true;
	                $pfErrMsg = PF_ERR_AMOUNT_MISMATCH;
				}
	                       
	        }

	        if( !$pfError && !$pfDone )
	        {
	            pflog( 'Check status and update order' );	    
	            
	            $transaction_id = $pfData['pf_payment_id'];
	    
	            switch( $pfData['payment_status'] )
	            {
	                case 'COMPLETE':
	                    pflog( '- Complete' );
	    
	                    // Update the purchase status
	                   	$new_status = false;					
					
						if ( !$this->_check_duplicate_transaction( $user_id, $sub_id, $timestamp, trim( $pfData['pf_payment_id'] ) ) ) 
						{
							$this->_record_transaction( $user_id, $sub_id, $amount, $currency, $timestamp, trim( $pfData['pf_payment_id'] ), $pfData['payment_status'], '' );

							if ( $sublevel == '1' ) 
							{
								// This is the first level of a subscription so we need to create one if it doesn't already exist
								$member = $factory->get_member( $user_id );
								if ( $member ) 
								{
									$member->create_subscription( $sub_id, $this->gateway );
									do_action( 'membership_payment_subscr_signup', $user_id, $sub_id );
								}
							} 
							else 
							{
								$member = $factory->get_member( $user_id );
								if ( $member ) 
								{
									// Mark the payment so that we can move through ok
									$member->record_active_payment( $sub_id, $sublevel, $timestamp );
								}
							}

							// remove any current subs for upgrades
							$sub_ids = $member->get_subscription_ids();
							foreach ( $sub_ids as $fromsub ) 
							{
								if ( $sub_id == $fromsub ) 
								{
									continue;
								}

								$member->drop_subscription($fromsub);
							}

							// Added for affiliate system link
							do_action( 'membership_payment_processed', $user_id, $sub_id, $amount, $currency, $pfData['pf_payment_id'] );
						}

						membership_debug_log( __('Processed transaction received - ','membership') . print_r($_POST, true) );
				                    
	                    break;
	    
	                case 'FAILED':
	                    pflog( '- Failed' );	    
	                    // If payment fails, delete the purchase log
	                  
	    
	                    break;
	    
	                case 'PENDING':
	                    pflog( '- Pending' );	    
	                    // Need to wait for "Completed" before processing
	                    break;
	    
	                default:
	                    // If unknown status, do nothing (safest course of action)
	                break;
	            }
	             
	        }
	        else
	        {
	            pflog( "Errors:\n". print_r( $pfErrMsg, true ) );
	        }

		

		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayFast site.
			header('Status: 404 Not Found');
			echo 'Error: Missing POST variables. Identification is not possible.';
			membership_debug_log( 'Error: Missing POST variables. Identification is not possible.' );
			exit;
		}
	}

}

Membership_Gateway::register_gateway( 'payfast', 'payfast' );