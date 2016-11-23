<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('JPATH_PLATFORM') or die;

/**
 * Field to select a user id from a modal list.
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldProduct extends JFormField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  11.1
   */
  public $type = 'Product';

  /**
   * Method to get the user field input markup.
   *
   * @return  string  The field input markup.
   *
   * @since   11.1
   */
  protected function getInput()
  {
    //var_dump($this->form->getValue('item_type'));
    // Initialize variables.
    $html = array();

//file_put_contents('debog_item_form.txt', print_r($this->form, true));
    // Initialize some field attributes.
    $attr = $this->element['class'] ? ' class="'.(string)$this->element['class'].'"' : '';
    $attr .= $this->element['size'] ? ' size="'.(int)$this->element['size'].'"' : '';

    // Initialize JavaScript field attributes.
    $onchange = (string) $this->element['onchange'];

    // Load the modal behavior script.
    JHtml::_('behavior.modal', 'a.modal_' . $this->id);

    // Build the script.
    $script = array();
    $script[] = 'function selectItem(id, title) {';
    $script[] = '  var old_id = document.getElementById("' . $this->id . '_id").value;';
    $script[] = '  if (old_id != id) {';
    $script[] = '    document.getElementById("'.$this->id.'_id").value = id;';
    $script[] = '    document.getElementById("'.$this->id.'_name").value = title;';
    $script[] = '	'.$onchange;
    $script[] = '  }';
    $script[] = '  SqueezeBox.close();';
    $script[] = '}';

    // Add the script to the document head.
    JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

    // Get the name of the product.
    $db = JFactory::getDBO();
    $db->setQuery('SELECT name FROM #__ketshop_product WHERE id='.(int) $this->value);
    $name = $db->loadResult();

    if($error = $db->getErrorMsg()) {
      JError::raiseWarning(500, $error);
    }

    if(empty($name)) {
	    $name = JText::_('COM_KETSHOP_SELECT_PRODUCT');
    }

    //Build the link to the modal product list.
    $link = 'index.php?option=com_ketshop&amp;view=products&amp;layout=modal&amp;tmpl=component';

    // Create a dummy text field with the user name.
    $html[] = '<div class="fltlft">';
    $html[] = '  <input type="text" id="'.$this->id.'_name"'.' value="'.htmlspecialchars($name, ENT_COMPAT, 'UTF-8').'"'
	    . ' disabled="disabled"'.$attr.' />';
    $html[] = '</div>';

    // Create the user select button.
    $html[] = '<div class="button2-left">';
    $html[] = '  <div class="blank">';
    if($this->element['readonly'] != 'true') {
      $html[] = '<a class="modal_'.$this->id.'" id="item_link" title="'.JText::_('COM_KETSHOP_SELECT_CHANGE_PRODUCT').'"' . ' href="'.$link.'"'
		.' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
      $html[] = JText::_('COM_KETSHOP_SELECT_CHANGE_PRODUCT').'</a>';
    }

    $html[] = '  </div>';
    $html[] = '</div>';

    // Create the real field, hidden, that stored the item id.
    $html[] = '<input type="hidden" id="'.$this->id.'_id" name="'.$this->name.'" value="'.(int)$this->value.'" />';

    return implode("\n", $html);
  }
}
