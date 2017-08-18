<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


//Script which build the select list containing the available tags.

class JFormFieldMaintag extends JFormFieldList
{
  protected $type = 'maintag';

  protected function getOptions()
  {
    $options = array();
      
    //Get the tags currently linked to the product.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('tm.tag_id, t.path')
	  ->from('#__contentitem_tag_map AS tm')
	  ->join('LEFT', '#__tags AS t ON t.id=tm.tag_id')
	  ->where('tm.type_alias = "com_ketshop.product" AND tm.content_item_id='.(int)$this->form->getValue('id'))
	  ->order('tm.tag_id');
    $db->setQuery($query);
    $tags = $db->loadObjectList();

    $tags = JHelperTags::convertPathsToNames($tags);

    //Build the first option.
    $options[] = JHtml::_('select.option', 0, JText::_('COM_KETSHOP_OPTION_SELECT'));

    //Build the select options.
    foreach($tags as $tag) {
      $options[] = JHtml::_('select.option', $tag->tag_id, $tag->text);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}

