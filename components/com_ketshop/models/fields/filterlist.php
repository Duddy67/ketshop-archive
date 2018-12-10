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


//Script which build the select html tag containing the search filters previously
//defined.

class JFormFieldFilterList extends JFormFieldList
{
  protected $type = 'filterlist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the search filters.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id,name')
	  ->from('#__ketshop_filter')
	  ->where('published=1')
	  ->order('id');
    $db->setQuery($query);
    $filters = $db->loadObjectList();

    //Build the select options.
    foreach($filters as $filter) {
      $options[] = JHtml::_('select.option', $filter->id, $filter->name);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}

