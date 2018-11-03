<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * KetShop Component Route Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_ketshop
 * @since       1.5
 */
abstract class KetshopHelperRoute
{
  /**
   * Gets the product route.
   * Note: For compatibility reasons the tagid value has to be passed through an array to
   * differenciate between a call from our component (array) and a call from the com_tags
   * component (integer).
   *
   * @param   integer  $id        The slug of the product (eg: 5:item-title).
   * @param   multi    $tagid     An array containing the main tag id linked to the product
   *                              or an integer as the catid of the product.
   * @param   integer  $language  The language code.
   *
   * @return  string  The product route.
   *
   * @since   1.5
   */
  public static function getProductRoute($id, $tagid, $language = 0)
  {
    //Create the link
    $link = 'index.php?option=com_ketshop&view=product&id='.$id;

    //In some specific cases (eg: the function is called from com_tags) the argument passed is 
    //an integer as the catid of the product.  
    if(!is_array($tagid) && (int)$tagid > 1) {
      $categories = JCategories::getInstance('Ketshop');
      $category = $categories->get($tagid);

      //If the category exists set the link with the main tag id of the product
      //instead of its category id.
      if($category) {
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	$query->select('main_tag_id')
	      ->from('#__ketshop_product')
	      ->where('catid='.(int)$tagid.' AND id='.(int)$id);
	$db->setQuery($query);
	$mainTagId = $db->loadResult();

	$link .= '&tag_id='.$mainTagId;
      }
    }
    //The function is called from our component.
    elseif(is_array($tagid) && (int)$tagid[0] > 1) {
      $link .= '&tag_id='.$tagid[0];
    }

    if($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
      $link .= '&lang='.$language;
    }

    return $link;
  }


  /**
   * Get the category route.
   *
   * @param   integer  $catid     The category ID.
   * @param   integer  $language  The language code.
   *
   * Note: This function is not used by the com_ketshop component but it might be used by
   *       the com_tags component.
   *
   * @return  string  The category route.
   *
   * @since   1.5
   */
  public static function getCategoryRoute($catid, $language = 0)
  {
    if($catid instanceof JCategoryNode) {
      $id = $catid->id;
    }
    else {
      $id = (int) $catid;
    }

    if($id < 1) {
      $link = '';
    }
    else {
      $link = 'index.php?option=com_ketshop&view=category&id='.$id;

      if($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
	$link .= '&lang='.$language;
      }
    }

    return $link;
  }


  /**
   * Get the tag route.
   *
   * @param   integer  $id        The tag ID.
   * @param   integer  $language  The language code.
   *
   *
   * @return  string  The tag route.
   *
   * @since   1.5
   */
  public static function getTagRoute($id, $language = 0)
  {
    if((int)$id < 1) {
      $link = '';
    }
    else {
      $link = 'index.php?option=com_ketshop&view=tag&id='.$id;

      if($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
	$link .= '&lang='.$language;
      }
    }

    return $link;
  }


  /**
   * Get the form route.
   *
   * @param   integer  $id  The form ID.
   *
   * @return  string  The product route.
   *
   * @since   1.5
   */
  public static function getFormRoute($id)
  {
    return 'index.php?option=com_ketshop&task=product.edit&s_id='.(int)$id;
  }


  /**
   * Returns a tag from a given id.
   *
   * @param   integer  $id      The tag ID.
   * @param   integer  $access  Flag to take into account (or not) the user's access.
   *
   * @return  object   The tag object.
   *
   * @since   1.5
   */
  public static function getTag($id, $access = true)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id, alias')
            ->from('#__tags')
            ->where('id='.(int)$id);

    if($access) {
      $user = JFactory::getUser();
      $groups = implode(',', $user->getAuthorisedViewLevels());
      $query->where('access IN ('.$groups.')');
    }

    $db->setQuery($query);
    $tag = $db->loadObject();

    return $tag;
  }


  /**
   * Returns the tag children from a given tag id.
   * Note: If no tag id is passed the function return the complete tags hierarchy (parents/children) 
   *       from the top parent tag.
   *
   * @param   integer  $id      The tag ID to get the children from.
   * @param   integer  $access  Flag to take into account (or not) the user's access.
   *
   * @return  mixed    The tag children objects or the complete tags hierarchy.
   *
   * @since   1.5
   */
  public static function getTagChildren($tagId = 0, $access = true)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('DISTINCT n.id, n.alias, n.parent_id, n.level')
	  ->from('#__tags AS n, #__tags AS p')
	  ->where('n.lft BETWEEN p.lft AND p.rgt AND n.level >= 1 AND n.level <= 10');

    if($tagId) {
      $query->where('n.parent_id='.(int)$tagId);
    }

    if($access) {
      $user = JFactory::getUser();
      $groups = implode(',', $user->getAuthorisedViewLevels());
      $query->where('n.access IN ('.$groups.')');
    }

    $query->where('n.published=1')
	  ->order('n.lft');
    $db->setQuery($query);

    if($tagId) {
      return $db->loadObjectList();
    }

    return $db->loadAssocList('id');
  }


  /**
   * Returns the tag path to the root tag.
   * Note: The returned path is reversed.
   *
   * @param   integer  $id      The tag ID.
   *
   * @return  array    The tag path.
   *
   * @since   1.5
   */
  public static function getTagPath($tagId)
  {
    $tags = self::getTagChildren();
    $path = array();
    $currentId = $tagId;

    if(!empty($tags)) {
      //Note: Don't use a foreach loop as it's not possible to reset looping.
      //Use a while loop instead.
      while(list($key, $tag) = each($tags)) {
        if($key == $currentId) {
          $path[$key] = $tag['id'].':'.$tag['alias'];
          //Checks for children tags.
          if($tag['level'] > 1) {
            //Goes back into the tags hierarchy.
            $currentId = $tag['parent_id'];
            //Starts again looping from the top.
            reset($tags);
          }
          else {
            break;
          }
        }
      }   
    }   

    return $path;
  }
}
