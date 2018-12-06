<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
 

/**
 * JSON Attribute View class. Mainly used for Ajax request. 
 */
class KetshopViewAttribute extends JViewLegacy
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    //Collects the required variables.
    $attributeId = $jinput->get('attribute_id', 0, 'uint');
    $model = $this->getModel();
    $results = array();
    $results = $model->getOptions($attributeId);

    echo new JResponseJson($results);
  }
}



