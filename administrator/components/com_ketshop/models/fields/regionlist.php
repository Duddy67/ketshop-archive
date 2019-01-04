<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


//Script which build the select html tag containing the region names and codes.

class JFormFieldRegionList extends JFormFieldList
{
  protected $type = 'regionlist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('r.code,r.lang_var,c.alpha_2')
	  ->from('#__ketshop_region AS r')
	  //Get only regions which country they're linked with is published (to minimized
	  //the number of regions to display).
	  ->join('LEFT', '#__ketshop_country AS c ON r.country_code=c.alpha_2')
	  ->where('c.published=1')
	  ->order('r.country_code');
    $db->setQuery($query);
    $regions = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_KETSHOP_OPTION_SELECT'));

    //Build the select options.
    foreach($regions as $region) {
      //Add the country code to the region name to get an easier search.
      $options[] = JHtml::_('select.option', $region->code, $region->alpha_2.' - '.JText::_($region->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



