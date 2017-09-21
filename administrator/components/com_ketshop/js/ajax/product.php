<?php

//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_ketshop/js');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));
require_once (JPATH_BASE.'/administrator/components/com_ketshop/helpers/utility.php');

//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//We need to use Joomla's database class 
require_once (JPATH_BASE.'/'.UtilityHelper::getFactoryFilePath());
//Create the application
$mainframe = JFactory::getApplication('site');
$mainframe->initialise();

//Get the required variables.
$productId = JFactory::getApplication()->input->get->get('product_id', 0, 'uint');
$productType = JFactory::getApplication()->input->get->get('product_type', '', 'string');
$isAdmin = JFactory::getApplication()->input->get->get('is_admin', 0, 'uint');

//Get data of attributes, images and bundle product linked the a given product. 
//(Note: a bundle is considered as a product).

$data = array();
$db = JFactory::getDbo();
$query = $db->getQuery(true);

//Get fields and values of the attributes linked to a given product. 
//Note: Conditions are used here to assign values to the correct field.
//In case of a drop down list, data from "pa" table is set as
//the selected value(s) and data from "a" table is set as the values of the drop down list.
//In case of an input field, data from "pa" table is set as the field value
//and the selected value is empty.
$query->select('pa.attrib_id AS id,a.name,a.published,a.field_type_1,a.value_type,a.field_text_1,a.field_type_2,a.field_text_2,'.
	       'IF(a.field_type_1 != "open_field",pa.field_value_1,"") AS selected_value_1,'.
	       'IF(a.field_type_2 != "open_field",pa.field_value_2,"") AS selected_value_2,'.
	       'IF(a.field_type_1 != "open_field",a.field_value_1,pa.field_value_1) AS field_value_1,'.
	       'IF(a.field_type_2 != "open_field",a.field_value_2,pa.field_value_2) AS field_value_2')
      ->from('#__ketshop_prod_attrib AS pa ')
      ->join('INNER', '#__ketshop_attribute AS a ON a.id = pa.attrib_id')
      ->where('pa.prod_id='.$productId)
      ->order('a.ordering');
$db->setQuery($query);
//Get results as a list of associative arrays and put them into the data array.
$data['attribute'] = $db->loadAssocList();


//Get images linked to the product.
$query->clear();
$query->select('src, width, height, alt, ordering') 
      ->from('#__ketshop_prod_image')
      ->where('prod_id='.$productId)
      ->order('ordering');
$db->setQuery($query);
$images = $db->loadAssocList();

if($isAdmin) {
  //Add "../" to the path of each image as we are in the administrator area.
  foreach($images as $key => $image) {
    $image['src'] = '../'.$image['src'];
    $images[$key] = $image;
  }
}
else {
  //On front-end we must set src with the absolute path or SEF will add a wrong url path.  
  $length = strlen('administrator/components/com_ketshop/js/ajax/');
  $length = $length - ($length * 2);
  $url = substr(JURI::root(), 0, $length);

  foreach($images as $key => $image) {
    $image['src'] = $url.$image['src'];
    $images[$key] = $image;
  }
}

$data['image'] = $images;


//Get options linked to the product.
$query->clear();
$query->select('opt_id, option_name, base_price, sale_price, sales, code, stock,'.
               'availability_delay, weight, length, width, height, published, ordering') 
      ->from('#__ketshop_product_option')
      ->where('prod_id='.$productId)
      ->order('opt_id');
$db->setQuery($query);
$options = $db->loadAssocList();

if(!empty($options)) {
  //Get attributes linked to the options.
  $query->clear();
  $query->select('opt_id, attrib_id, attrib_value') 
	->from('#__ketshop_opt_attrib')
	->where('prod_id='.$productId)
	->order('opt_id');
  $db->setQuery($query);
  $optAttribs = $db->loadAssocList();

  $config = JComponentHelper::getParams('com_ketshop');

  //Store the attributes linked to the given option.
  foreach($options as $key => $option) {
    $options[$key]['attributes'] = array();
    foreach($optAttribs as $optAttrib) {
      if($optAttrib['opt_id'] == $option['opt_id']) {
	$options[$key]['attributes'][] = $optAttrib;
      }
    }

    //Format some numerical values.
    $options[$key]['weight'] = UtilityHelper::formatNumber($options[$key]['weight']);
    $options[$key]['length'] = UtilityHelper::formatNumber($options[$key]['length']);
    $options[$key]['width'] = UtilityHelper::formatNumber($options[$key]['width']);
    $options[$key]['height'] = UtilityHelper::formatNumber($options[$key]['height']);
    $options[$key]['base_price'] = UtilityHelper::formatNumber($options[$key]['base_price'], $config->get('digits_precision'));
    $options[$key]['sale_price'] = UtilityHelper::formatNumber($options[$key]['sale_price'], $config->get('digits_precision'));
  }
}

$data['option'] = $options;


if($productType == 'bundle') {
  //Get products linked to the bundle/product.
  $query->clear();
  $query->select('prod_id AS id, name, quantity, stock') 
        ->from('#__ketshop_prod_bundle')
	->join('INNER', '#__ketshop_product ON id=prod_id')
	->where('bundle_id='.$productId)
	->order('prod_id');
  $db->setQuery($query);
  $data['product'] = $db->loadAssocList();
}

echo json_encode($data);

