<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
 

/**
 * JSON Pricerule View class. Mainly used for Ajax request. 
 */
class KetshopViewPricerule extends JViewLegacy
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    //Collects the required variables.
    $priceRuleId = $jinput->get('pricerule_id', 0, 'uint');
    $priceRuleType = $jinput->get('pricerule_type', '', 'string');
    $conditionType = $jinput->get('condition_type', '', 'string');
    $recipientType = $jinput->get('recipient_type', '', 'string');
    $targetType = $jinput->get('target_type', '', 'string');

    $model = $this->getModel();
    $results = array();

    $results['recipient'] = $model->getRecipientData($priceRuleId, $recipientType);

    // Calls the corresponding function.
    if($priceRuleType == 'cart') {
      $results['condition'] = $model->getConditionData($priceRuleId, $conditionType);
    }
    // catalog
    else { 
      $results['target'] = $model->getTargetData($priceRuleId, $targetType);
    }

    echo new JResponseJson($results);
  }
}



