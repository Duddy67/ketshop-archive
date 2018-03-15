<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
require_once('route.php');


/**
 * Legacy routing rules class from com_ketshop
 *
 * @since       3.6
 * @deprecated  4.0
 */
class KetshopRouterRulesLegacy implements JComponentRouterRulesInterface
{
  /**
   * Constructor for this legacy router
   *
   * @param   JComponentRouterAdvanced  $router  The router this rule belongs to
   *
   * @since       3.6
   * @deprecated  4.0
   */
  public function __construct($router)
  {
    $this->router = $router;
  }


  /**
   * Preprocess the route for the com_ketshop component
   *
   * @param   array  &$query  An array of URL arguments
   *
   * @return  void
   *
   * @since       3.6
   * @deprecated  4.0
   */
  public function preprocess(&$query)
  {
  }


  /**
   * Build the route for the com_ketshop component
   *
   * @param   array  &$query     An array of URL arguments
   * @param   array  &$segments  The URL arguments to use to assemble the subsequent URL.
   *
   * @return  void
   *
   * @since       3.6
   * @deprecated  4.0
   */
  public function build(&$query, &$segments)
  {
    // Get a menu item based on Itemid or currently active
    $params = JComponentHelper::getParams('com_ketshop');
    $advanced = $params->get('sef_advanced_link', 0);

    // We need a menu item.  Either the one specified in the query, or the current active one if none specified
    if(empty($query['Itemid'])) {
      $menuItem = $this->router->menu->getActive();
      $menuItemGiven = false;
    }
    else {
      $menuItem = $this->router->menu->getItem($query['Itemid']);
      $menuItemGiven = true;
    }

    // Check again
    if($menuItemGiven && isset($menuItem) && $menuItem->component != 'com_ketshop') {
      $menuItemGiven = false;
      unset($query['Itemid']);
    }

    if(isset($query['view'])) {
      $view = $query['view'];
    }
    else {
      // We need to have a view in the query or it is an invalid URL
      return;
    }

    // Are we dealing with a product or a tag that is attached to a menu item?
    if($menuItem !== null && $menuItem->query['view'] == $query['view'] && isset($menuItem->query['id'], $query['id'])
       && $menuItem->query['id'] == (int)$query['id'])
    {
      unset($query['view']);

      if(isset($query['tag_id'])) {
	unset($query['tag_id']);
      }

      if(isset($query['layout'])) {
	unset($query['layout']);
      }

      unset($query['id']);

      return;
    }

    if($view == 'tag' || $view == 'product') {
      if(!$menuItemGiven) {
	$segments[] = $view;
      }

      unset($query['view']);

      if($view == 'product') {
	if(isset($query['id']) && isset($query['tag_id']) && $query['tag_id']) {
	  $tagId = $query['tag_id'];

	  // Make sure we have the id and the alias
	  if(strpos($query['id'], ':') === false) {
	    $db = JFactory::getDbo();
	    $dbQuery = $db->getQuery(true)
		    ->select('alias')
		    ->from('#__ketshop_product')
		    ->where('id='.(int)$query['id']);
	    $db->setQuery($dbQuery);
	    $alias = $db->loadResult();
	    $query['id'] = $query['id'].':'.$alias;
	  }
	}
	else {
	  // We should have these two set for this view.  If we don't, it is an error
	  return;
	}
      }
      else {
	if(isset($query['id'])) {
	  $tagId = $query['id'];
	}
	else {
	  // We should have id set for this view.  If we don't, it is an error
	  return;
	}
      }

      if($menuItemGiven && isset($menuItem->query['id'])) {
	$mTagId = $menuItem->query['id'];
      }
      else {
	$mTagId = 0;
      }

      $tag = KetshopHelperRoute::getTag($tagId);

      if(!$tag) {
	// We couldn't find the tag we were given.  Bail.
	return;
      }

      $path = KetshopHelperRoute::getTagPath($tagId);
      $array = array();

      foreach($path as $id) {
	if((int) $id == (int) $mTagId) {
	  break;
	}

	list($tmp, $id) = explode(':', $id, 2);

	$array[] = $id;
      }

      $array = array_reverse($array);

      if(!$advanced && count($array)) {
	$array[0] = (int)$tagId.':'.$array[0];
      }

      $segments = array_merge($segments, $array);

      if($view == 'product') {
	if($advanced) {
	  list($tmp, $id) = explode(':', $query['id'], 2);
	}
	else {
	  $id = $query['id'];
	}

	$segments[] = $id;
      }

      unset($query['id'], $query['tag_id']);
    }

    /*
     * If the layout is specified and it is the same as the layout in the menu item, we
     * unset it so it doesn't go into the query string.
     */
    if(isset($query['layout'])) {
      if(!empty($query['Itemid']) && isset($menuItem->query['layout'])) {
	if($query['layout'] == $menuItem->query['layout']) {
	  unset($query['layout']);
	}
      }
      else {
	if($query['layout'] == 'default') {
	  unset($query['layout']);
	}
      }
    }

    $total = count($segments);

    //Converts colon into hyphen.
    for($i = 0; $i < $total; $i++) {
      $segments[$i] = str_replace(':', '-', $segments[$i]);
    }
  }


  /**
   * Parse the segments of a URL.
   *
   * @param   array  &$segments  The segments of the URL to parse.
   * @param   array  &$vars      The URL attributes to be used by the application.
   *
   * @return  void
   *
   * @since       3.6
   * @deprecated  4.0
   */
  public function parse(&$segments, &$vars)
  {
    $total = count($segments);

    //Converts hyphen into colon.
    for($i = 0; $i < $total; $i++) {
      $segments[$i] = preg_replace('/-/', ':', $segments[$i], 1);
    }

    // Get the active menu item.
    $item = $this->router->menu->getActive();
    $params = JComponentHelper::getParams('com_ketshop');
    $advanced = $params->get('sef_advanced_link', 0);

    // Count route segments
    $count = count($segments);

  /*
   * Standard routing for products. If we don't pick up an Itemid then we get the view from the segments
   * the first segment is the view and the last segment is the id of the product or tag.
   */
    if(!isset($item)) {
      $vars['view'] = $segments[0];
      $vars['id'] = $segments[$count - 1];
      return;
    }

    /*
     * If there is only one segment, then it points to either an product or a tag.
     * We test it first to see if it is a tag.  If the id and alias match a tag,
     * then we assume it is a tag.  If they don't we assume it is an product
     */
    if($count == 1) {
      // We check to see if an alias is given.  If not, we assume it is an product
      if(strpos($segments[0], ':') === false) {
	$vars['view'] = 'product';
	$vars['id'] = (int) $segments[0];

	return;
      }

      list($id, $alias) = explode(':', $segments[0], 2);

      // First we check if it is a tag
      $tag = KetshopHelperRoute::getTag($id);

      if($tag && $tag->alias == $alias) {
	$vars['view'] = 'tag';
	$vars['id'] = $id;

	return;
      }
      else {
	$db = JFactory::getDbo();
	$query = $db->getQuery(true)
		->select($db->quoteName(array('alias', 'main_tag_id')))
		->from($db->quoteName('#__ketshop_product'))
		->where($db->quoteName('id') . ' = ' . (int)$id);
	$db->setQuery($query);
	$product = $db->loadObject();

	if($product) {
	  if($product->alias == $alias) {
	    $vars['view'] = 'product';
	    $vars['tag_id'] = (int)$product->main_tag_id;
	    $vars['id'] = (int)$id;

	    return;
	  }
	}
      }
    }

    /*
     * If there was more than one segment, then we can determine where the URL points to
     * because the first segment will have the target tag id prepended to it.  If the
     * last segment has a number prepended, it is a product, otherwise, it is a tag.
     */
    if(!$advanced) {
      $tag_id = (int)$segments[0];

      $product_id = (int)$segments[$count - 1];

      if($product_id > 0) {
	$vars['view'] = 'product';
	$vars['tag_id'] = $tag_id;
	$vars['id'] = $product_id;
      }
      else {
	$vars['view'] = 'tag';
	$vars['id'] = $tag_id;
      }

      return;
    }

    // We get the tag id from the menu item and search from there
    $id = $item->query['id'];
    $tag = KetshopHelperRoute::getTag($id);

    if(!$tag) {
      JError::raiseError(404, JText::_('COM_NOTEBOOK_ERROR_PARENT_TAG_NOT_FOUND'));
      return;
    }

    $tags = KetshopHelperRoute::getTagChildren($tag->id);
    $vars['tag_id'] = $id;
    $vars['id'] = $id;
    $found = 0;

    foreach($segments as $segment) {
      $segment = str_replace(':', '-', $segment);

      foreach($tags as $tag) {
	if($tag->alias == $segment) {
	  $vars['id'] = $tag->id;
	  $vars['tag_id'] = $tag->id;
	  $vars['view'] = 'tag';
	  $tags = KetshopHelperRoute::getTagChildren($tag->id);
	  $found = 1;
	  break;
	}
      }

      if($found == 0) {
	if($advanced) {
	  $db = JFactory::getDbo();
	  $query = $db->getQuery(true)
		  ->select($db->quoteName('id'))
		  ->from('#__ketshop_product')
		  ->where($db->quoteName('main_tag_id').'='.(int)$vars['tag_id'])
		  ->where($db->quoteName('alias').'='.$db->quote($segment));
	  $db->setQuery($query);
	  $sid = $db->loadResult();
	}
	else {
	  $sid = $segment;
	}

	$vars['id'] = $sid;
	$vars['view'] = 'product';
      }

      $found = 0;
    }
  }
}

