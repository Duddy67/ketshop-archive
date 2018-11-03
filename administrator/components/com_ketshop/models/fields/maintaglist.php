<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\TagField;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\Utilities\ArrayHelper;
FormHelper::loadFieldClass('list');

//Script which build the select list containing the tags used as main tag by product items.
//The unused tags in the list are disabled.

//Inherits from the TagField class.

class JFormFieldMaintaglist extends TagField
{

  /**
   * Method to get a list of tags
   *
   * @return  array  The field option objects.
   *
   * @since   3.1
   */
  protected function getOptions()
  {
    $published = $this->element['published'] ?: array(0, 1);
    $app       = Factory::getApplication();
    $tag       = $app->getLanguage()->getTag();

    $db    = Factory::getDbo();
    $query = $db->getQuery(true)
	    ->select('DISTINCT a.id AS value, a.path, a.title AS text, a.level,'.
		     'a.published, a.lft, (s.main_tag_id IS NOT NULL) AS is_main_tag')
	    ->from('#__tags AS a')
	    ->join('LEFT', $db->qn('#__tags') . ' AS b ON a.lft > b.lft AND a.rgt < b.rgt')
	    //Checks whether the tag is used as main tag by the product.
	    ->join('LEFT', $db->qn('#__ketshop_product') . ' AS s ON s.main_tag_id = a.id');

    // Limit Options in multilanguage
    if ($app->isClient('site') && Multilanguage::isEnabled())
    {
	    $lang = ComponentHelper::getParams('com_tags')->get('tag_list_language_filter');

	    if ($lang == 'current_language')
	    {
		    $query->where('a.language in (' . $db->quote($tag) . ',' . $db->quote('*') . ')');
	    }
    }
    // Filter language
    elseif (!empty($this->element['language']))
    {
	    if (strpos($this->element['language'], ',') !== false)
	    {
		    $language = implode(',', $db->quote(explode(',', $this->element['language'])));
	    }
	    else
	    {
		    $language = $db->quote($this->element['language']);
	    }

	    $query->where($db->quoteName('a.language') . ' IN (' . $language . ')');
    }

    $query->where($db->qn('a.lft') . ' > 0');

    // Filter on the published state
    if (is_numeric($published))
    {
	    $query->where('a.published = ' . (int) $published);
    }
    elseif (is_array($published))
    {
	    $published = ArrayHelper::toInteger($published);
	    $query->where('a.published IN (' . implode(',', $published) . ')');
    }

    $query->order('a.lft ASC');

    // Get the options.
    $db->setQuery($query);

    try
    {
	    $options = $db->loadObjectList();
    }
    catch (\RuntimeException $e)
    {
	    return array();
    }

    // Block the possibility to set a tag as it own parent
    if ($this->form->getName() === 'com_tags.tag')
    {
	    $id   = (int) $this->form->getValue('id', 0);

	    foreach ($options as $option)
	    {
		    if ($option->value == $id)
		    {
			    $option->disable = true;
		    }
	    }
    }

    //Gets the very first option (Select) from the parent options.
    $selectOption = array_slice(parent::getOptions(), 0, 1);
    // Merge the select option in the XML definition.
    $options = array_merge($selectOption, $options);

    // Prepare nested data
    if ($this->isNested())
    {
      //Disables the tags which are not used as main tags.
      foreach($options as $key => $option) {
	if(isset($option->is_main_tag) && !$option->is_main_tag) {
	  $option->disable = true;
	}
      }

      $this->prepareOptionsNested($options);
    }
    else
    {
      $options = TagsHelper::convertPathsToNames($options);
    }

    return $options;
  }

}

