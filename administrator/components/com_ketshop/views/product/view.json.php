<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
 

/**
 * JSON Product View class. Mainly used for Ajax request. 
 */
class KetshopViewProduct extends JViewLegacy
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    //Collects the required variables.
    $context = $jinput->get('context', '', 'string');
    $productId = $jinput->get('product_id', 0, 'uint');
    $isAdmin = $jinput->get('is_admin', 0, 'uint');
    $model = $this->getModel();
    $results = array();

    $results['attribute'] = $model->getProductAttributes($productId);
    $results['image'] = $model->getImageData($productId, $isAdmin);
    //$results['variant'] = array();
    $results['variant'] = $model->getVariantData($productId);

    //Calls the corresponding functions.
    /*if($context == 'product_elements') {
      //Gathers all the elements linked to the product.
      $productType = $jinput->get('product_type', '', 'string');
      $isAdmin = $jinput->get('is_admin', 0, 'uint');

      $results['attribute'] = $model->getAttributeData($productId);
      $results['image'] = $model->getImageData($productId, $isAdmin);
      $results['variant'] = $model->getVariantData($productId);

      if($productType == 'bundle') {
	$results['product'] = $model->getBundleProducts($productId);
      }
    }
    elseif($context == 'check_alias') {
      $name = $jinput->get('name', '', 'string');
      $alias = $jinput->get('alias', '', 'string');
      $results = $model->checkAlias($productId, $name, $alias);
    }
    elseif($context == 'attribute') {
      $attributeId = $jinput->get('attribute_id', 0, 'uint');
      $results = $model->getAttribute($attributeId);
    }
    else {
      echo new JResponseJson($results, JText::_('COM_KETSHOP_ERROR_UNKNOWN_CONTEXT'), true);
      return;
    }*/

    echo new JResponseJson($results);
  }
}



