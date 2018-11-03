<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
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
      
    //Get the tags linked to the item.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('tm.tag_id, t.path, t.language')
	  ->from('#__contentitem_tag_map AS tm')
	  ->join('LEFT', '#__tags AS t ON t.id=tm.tag_id')
	  ->where('tm.type_alias = "com_ketshop.product" AND tm.content_item_id='.(int)$this->form->getValue('id'))
          //Doesn't retrieve the archived or trashed tags.
          ->where('t.published NOT IN(2, -2)')
	  ->order('tm.tag_id');
    $db->setQuery($query);
    $tags = $db->loadObjectList();

    $tags = JHelperTags::convertPathsToNames($tags);

    //Build the select options.
    foreach($tags as $tag) {
      $langTag = '';
      if($tag->language !== '*') {
	$langTag = ' ('.$tag->language.')';
      }

      $options[] = JHtml::_('select.option', $tag->tag_id, $tag->text.$langTag);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}

