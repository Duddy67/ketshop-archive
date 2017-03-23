<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/components/com_ketshop/helpers/shop.php';



class plgKetshoppaymentOffline extends JPlugin
{

  //Grab the event triggered by the payment controller.
  public function onKetshopPaymentOffline ($amounts, $cart, $settings, $utility)
  {
    //Get the id of the offline payment chosen.
    $offlineId = $utility['offline_id'];

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //The translated fields of a payment mode.
    $translatedFields = 'pm.name,pm.information';
    //Check if a translation is needed.
    if(ShopHelper::switchLanguage()) {
      //Get the SQL query parts needed for the translation of the payment modes.
      $paymentTranslation = ShopHelper::getTranslation('payment_mode', 'id', 'pm', 'pm');
      //Translation field are now defined by the SQL conditions.
      //$translatedFields = $paymentTranslation->translated_fields;
      $query->select($paymentTranslation->translated_fields);
      //Build the left join SQL clause.
      $query->join('LEFT', $paymentTranslation->left_join);
    }
    else {
      $query->select($translatedFields);
    }

    $query->from('#__ketshop_payment_mode AS pm');
    $query->where('pm.id='.$offlineId);
    $db->setQuery($query);
    $offlinePayment = $db->loadObject();
    
    //Check for errors.
    if($db->getErrorNum() || is_null($offlinePayment)) {
      if($db->getErrorNum()) {
	$utility['payment_detail'] = $db->getErrorMsg();
      }
      else {
	$utility['payment_detail'] = JText::_('COM_KETSHOP_ERROR');
      }

      $utility['plugin_result'] = false;

      return $utility;
    }

    //Set the name of the offline payment as details.
    $utility['payment_detail'] = $offlinePayment->name;

    //Create the form corresponding to the selected offline payment mode.

    $output = '<form action="index.php?option=com_ketshop&task=payment.response&payment=offline" '.
	       'method="post" id="payment_modes" >';
    $output .= '<div class="offline-payment">';
    $output .= '<h1>'.$offlinePayment->name.'</h1>';
    $output .= $offlinePayment->information;
    $output .= '<div id="action-buttons">';
    $output .= '<span class="btn">'.
               '<a href="index.php?option=com_ketshop&view=payment&task=payment.cancelPayment&payment=offline" onclick="hideButton(\'action-buttons\')">'.JText::_('COM_KETSHOP_CANCEL').'</a></span>';
    $output .= '<span class="button-separation">&nbsp;</span>';
    $output .= '<input id="submit-button" class="btn btn-success" onclick="hideButton(\'action-buttons\')" type="submit" value="'.JText::_('COM_KETSHOP_VALIDATE').'" />';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</form>';

    //Store the output into the utility array in order to be displayed
    //in the payment view.
    $utility['plugin_output'] = $output;

    return $utility;
  }


  public function onKetshopPaymentOfflineResponse($amounts, $cart, $settings, $utility)
  {
    //Note: Payment results can only be ok with offline payment method since there's
    //      no web procedure to pass through.

    ShopHelper::createTransaction($amounts, $utility, $settings); 

    //Redirect the customer to the ending step.
    $utility['redirect_url'] = JRoute::_('index.php?option=com_ketshop&task=finalize.confirmPurchase', false);

    return $utility;
  }


  public function onKetshopPaymentOfflineCancel($amounts, $cart, $settings, $utility)
  {
    //Some code here if needed.
    //...
    return true;
  }
}
