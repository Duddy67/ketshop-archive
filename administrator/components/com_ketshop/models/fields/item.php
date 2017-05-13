<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('JPATH_PLATFORM') or die;

/**
 * Field to select different KetShop items to translate as product, attribute etc...
 *
 * @package     Joomla.Platform
 * @subpackage  Form
 * @since       11.1
 */
class JFormFieldItem extends JFormField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  11.1
   */
  public $type = 'Item';

  /**
   * Method to get the user field input markup.
   *
   * @return  string  The field input markup.
   *
   * @since   11.1
   */
  protected function getInput()
  {
    // Initialize variables.
    $html = array();

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
    $script[] = '  var old_id = document.getElementById("'.$this->id.'_id").value;';
    $script[] = '  if (old_id != id) {';
    $script[] = '    document.getElementById("'.$this->id.'_id").value = id;';
    $script[] = '    document.getElementById("'.$this->id.'_name").value = title;';
    $script[] = '	'.$onchange;
    $script[] = '  }';
    $script[] = '  SqueezeBox.close();';
    $script[] = '}';

    // Add the script to the document head.
    JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

    $itemType = 'product'; //Item type by default.

    //Set the chosen item type.
    if(!is_null($this->form->getValue('item_type'))) {
      $itemType = $this->form->getValue('item_type');
    }

    //Remove underscore from the item type name if any (ex: price_rule).
    $itemType = preg_replace('#(_)#', '', $itemType);

    // Load the current item name if available.
    $table = JTable::getInstance($itemType, 'KetshopTable');
    if($this->value) {
      $table->load($this->value);
    }
    else {
      $table->item_name = JText::_('COM_KETSHOP_BUTTON_SELECT_ITEM');
    }

    //Set the proper view name according to the item type.
    $view = $itemType;
    //View names we need are in the plural (products, attributes etc...).
    $view = $view.'s';

    //Build the link to a modal item windows.
    $link = 'index.php?option=com_ketshop&amp;view='.$view.'&amp;layout=modal&amp;tmpl=component';

    // Create a dummy text field with the user name.
    $html[] = '<input type="text" class="translated-item-name input-xxlarge input-large-text" id="'.$this->id.'_name"'.
              ' value="'.htmlspecialchars($table->name, ENT_COMPAT, 'UTF-8').'"'.
	      ' disabled="disabled"'.$attr.' />';

    $html[] = '<span style="display:block;">&nbsp;</span>';
    // Create the user select button.
    if($this->element['readonly'] != 'true') {
      $html[] = '<a class="modal_'.$this->id.' btn" id="item_link" title="'.JText::_('JLIB_FORM_CHANGE_USER').'"' . ' href="'.$link.'"'
		.' rel="{handler: \'iframe\', size: {x: 800, y: 500}}">';
      $html[] = JText::_('COM_KETSHOP_BUTTON_SELECT_ITEM').'</a>';
    }

    // Create the real field, hidden, that stored the item id.
    $html[] = '<input type="hidden" id="'.$this->id.'_id" name="'.$this->name.'" value="'.(int)$this->value.'" />';

    return implode("\n", $html);
  }
}
