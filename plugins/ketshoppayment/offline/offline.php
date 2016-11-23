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
	$utility['error'] = $db->getErrorMsg();
      }
      else {
	$utility['error'] = JText::_('COM_KETSHOP_ERROR');
      }

      $utility['plugin_result'] = false;

      return $utility;
    }

    //Set the name of the offline payment as details.
    $utility['payment_details'] = $offlinePayment->name;

    //Create the form corresponding to the selected offline payment mode.

    $output = '<form action="index.php?option=com_ketshop&task=finalize.confirmPurchase&payment=offline" '.
	       'method="post" id="payment_modes" >';
    $output .= '<div class="offline-payment">';
    $output .= '<h1>'.$offlinePayment->name.'</h1>';
    $output .= $offlinePayment->information;
    $output .= '<div id="action-buttons">';
    $output .= '<span class="btn">'.
               '<a href="index.php?option=com_ketshop&view=payment&task=payment.cancel&payment=offline" onclick="hideButton(\'action-buttons\')">'.
                        JText::_('COM_KETSHOP_CANCEL').'</a></span>';
    $output .= '<span class="button-separation">&nbsp;</span>';
    $output .= '<input id="submit-button" class="btn btn-success" onclick="hideButton(\'action-buttons\')" type="submit" value="'.JText::_('COM_KETSHOP_VALIDATE').'" />';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</form>';

    //Store the output into the utility array in order to be displayed
    //in the payment view.
    $utility['plugin_output'] = $output;
    //Everything went ok.
    $utility['plugin_result'] = true;

    return $utility;
  }


  public function onKetshopPaymentOfflineResponse($amounts, $cart, $settings, $utility)
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $utility = $session->get('utility', array(), 'ketshop'); 
    $utility['payment_result'] = 1;
    $session->set('utility', $utility, 'ketshop');

    $app = JFactory::getApplication();
    $app->redirect(JRoute::_('index.php?option=com_ketshop&task=finalize.confirmPurchase', false));
    return true;
  }


  public function onKetshopPaymentOfflineCancel($utility)
  {
    //Grab the user session.
    $session = JFactory::getSession();
    $utility = $session->get('utility', array(), 'ketshop'); 
    //Empty the output variable which make the view to display the
    //payment modes. 
    $utility['plugin_output'] = '';
    $session->set('utility', $utility, 'ketshop');

    $app = JFactory::getApplication();
    $app->redirect(JRoute::_('index.php?option=com_ketshop&view=payment', false));
    return true;
  }


}
