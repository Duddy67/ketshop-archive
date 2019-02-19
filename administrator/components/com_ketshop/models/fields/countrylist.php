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
JLoader::register('InilangTrait', JPATH_ADMINISTRATOR.'/components/com_ketshop/traits/inilang.php');


//Script which build the select html tag containing the country names and codes.

class JFormFieldCountryList extends JFormFieldList
{
  use InilangTrait;

  protected $type = 'countrylist';


  protected function getOptions()
  {
    $options = array();

    //Gets the column name to use for the country name according to the current language.
    $countryName = $this->getColumnName('country');

    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('alpha_2,lang_var,'.$countryName)
	  ->from('#__ketshop_country')
	  ->where('published=1')
	  ->order($countryName);
    $db->setQuery($query);
    $countries = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_KETSHOP_OPTION_SELECT'));

    //Build the select options.
    foreach($countries as $country) {
      $options[] = JHtml::_('select.option', $country->alpha_2, $country->$countryName);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



