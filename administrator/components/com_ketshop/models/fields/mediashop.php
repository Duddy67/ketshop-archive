<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_PLATFORM') or die;

//In order to use the Joomla modal media selector in the product edit view, we need 
//to modify the JFormFieldMedia javascript to fit our purpose.
//The original file is locate here:
//libraries/cms/form/fields/media.php

//The upload mechanism (selection button etc...) is not defined here since 
//it is dynamicaly created in Javascript (see: js/image.js).
//Note: The asset identification has also been removed since we don't use it. 
class JFormFieldMediashop extends JFormField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  11.1
   */
  protected $type = 'Mediashop';

  /**
   * The initialised state of the document object.
   *
   * @var    boolean
   * @since  11.1
   */
  protected static $initialised = false;


  protected function getInput()
  {

    if(!self::$initialised) {
      // Load the modal behavior script.
      JHtml::_('behavior.modal');

      // Build the script.
      $script = array();
      $script[] = 'function jInsertFieldValue(value, id) {';
      //Build the image url.
      //On front-end we must set src with the absolute path or SEF will add a wrong url path.  
      $script[] = '  url="'.JURI::root().'"+value;';

      if(JFactory::getApplication()->isAdmin()) {
	//Add "../" to the path as we are in the administrator area.
	$script[] = '  url="../"+value;';
      }

      //Get the image attributes.
      $script[] = '  var newImg = new Image();';
      $script[] = '  newImg.src = url;';
      $script[] = '  var height = newImg.height;';
      $script[] = '  var width = newImg.width;';
      //Set the product image tag fields.
      $script[] = '  document.getElementById("product-img-"+id).src=url;';
      $script[] = '  document.getElementById("product-img-"+id).width=width;';
      $script[] = '  document.getElementById("product-img-"+id).height=height;';
      //Set the hidden fields.
      $script[] = '  document.getElementById("image-src-"+id).value=url;';
      $script[] = '  document.getElementById("image-width-"+id).value=width;';
      $script[] = '  document.getElementById("image-height-"+id).value=height;';
      //Div is resized to fit the image dimensions and a 1px gray border is defined. 
      $script[] = ' document.getElementById("img-div-"+id).setAttribute("style","width:"+width+"px;height:"+height+"px;border: 1px solid #c0c0c0;");';

      $script[] = '  var old_id = document.id("product-img-"+id).value;';
      $script[] = '  if (old_id != id) {';
      $script[] = '    var elem = document.id("product-img-"+id)';
      $script[] = '    elem.value = url;';
      $script[] = '    elem.fireEvent("change");';
      $script[] = '  }';
      $script[] = '}';

      // Add the script to the document head.
      JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

      self::$initialised = true;
    }


    return;
  }
}
