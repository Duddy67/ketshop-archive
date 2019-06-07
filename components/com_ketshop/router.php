<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JLoader::register('KetshopHelperRoute', JPATH_SITE.'/components/com_ketshop/helpers/route.php');
JLoader::register('ShopHelper', JPATH_SITE.'/components/com_ketshop/helpers/shop.php');


/**
 * Routing class of com_ketshop
 *
 * @since  3.3
 */
class KetshopRouter extends JComponentRouterView
{
  protected $noIDs = false;


  /**
   * KetShop Component router constructor
   *
   * @param   JApplicationCms  $app   The application object
   * @param   JMenu            $menu  The menu object to work with
   */
  public function __construct($app = null, $menu = null)
  {
    $params = JComponentHelper::getParams('com_ketshop');
    $this->noIDs = (bool) $params->get('sef_ids');

    $tags = new JComponentRouterViewconfiguration('tags');
    $tags->setKey('id');
    $this->registerView($tags);
    $tag = new JComponentRouterViewconfiguration('tag');
    $tag->setKey('id')->setParent($tags, 'tag_id')->setNestable()->addLayout('blog');
    $this->registerView($tag);

    $product = new JComponentRouterViewconfiguration('product');
    $product->setKey('id')->setParent($tag, 'tag_id');
    $this->registerView($product);
    $form = new JComponentRouterViewconfiguration('form');
    $form->setKey('p_id');
    $this->registerView($form);

    $cart = new JComponentRouterViewconfiguration('cart');
    $this->registerView($cart);
    $orders = new JComponentRouterViewconfiguration('orders');
    $this->registerView($orders);
    //$order = new JComponentRouterViewconfiguration('order');
    //$order->setKey('order_id')->setParent($orders);
    //$this->registerView($order);

    parent::__construct($app, $menu);

    $this->attachRule(new JComponentRouterRulesMenu($this));

    if($params->get('sef_advanced', 0)) {
      $this->attachRule(new JComponentRouterRulesStandard($this));
      $this->attachRule(new JComponentRouterRulesNomenu($this));
    }
    else {
      JLoader::register('KetshopRouterRulesLegacy', __DIR__.'/helpers/legacyrouter.php');
      $this->attachRule(new KetshopRouterRulesLegacy($this));
    }
  }


  /**
   * Method to get the segment(s) for a tag 
   *
   * @param   string  $id     ID of the tag to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getTagSegment($id, $query)
  {
    $tag = KetshopHelperRoute::getTag($id);

    if($tag) {
      $path = KetshopHelperRoute::getTagPath($id);
      $path[0] = '1:root';

      if($this->noIDs) {
	foreach($path as &$segment) {
	  list($id, $segment) = explode(':', $segment, 2);
	}
      }

      return $path;
    }

    return array();
  }


  /**
   * Method to get the segment(s) for a tag
   *
   * @param   string  $id     ID of the tag to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getTagsSegment($id, $query)
  {
    return $this->getTagSegment($id, $query);
  }


  /**
   * Method to get the segment(s) for a product 
   *
   * @param   string  $id     ID of the product to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getProductSegment($id, $query)
  {
    if(!strpos($id, ':')) {
      $db = JFactory::getDbo();
      $dbquery = $db->getQuery(true);
      // Checks if an alias translation is needed.
      $switchLanguage = ShopHelper::switchLanguage();

      $dbquery->select($dbquery->qn('p.alias'))
	      ->from($dbquery->qn('#__ketshop_product AS p'));

      if($switchLanguage) {
	// Finds out if a translated alias is available for this item in the given language.
	$dbquery->select('t.alias AS t_alias')
		->join('LEFT', '#__ketshop_translation AS t ON t.item_id=p.id AND t.item_type='.$db->Quote('product').
							       ' AND t.language='.$db->Quote(ShopHelper::switchLanguage(true)));
      }

      $dbquery->where('p.id='.$dbquery->q((int) $id));
      $db->setQuery($dbquery);
      $aliases = $db->loadAssoc();

      if($switchLanguage && !empty($aliases['t_alias'])) {
	$id .= ':'.$aliases['t_alias'];
      }
      else {
	$id .= ':'.$aliases['alias'];
      }
    }

    if($this->noIDs) {
      list($void, $segment) = explode(':', $id, 2);

      return array($void => $segment);
    }

    return array((int) $id => $id);
  }


  /**
   * Method to get the segment(s) for a form
   *
   * @param   string  $id     ID of the product form to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   3.7.3
   */
  public function getFormSegment($id, $query)
  {
    return $this->getProductSegment($id, $query);
  }


  /**
   * Method to get the id for a tag
   *
   * @param   string  $segment  Segment to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getTagId($segment, $query)
  {
    if(isset($query['id'])) {
      $tag = KetshopHelperRoute::getTag($query['id'], false);

      if($tag) {
	$children = KetshopHelperRoute::getTagChildren($query['id']);

	foreach($children as $child) {
	  if($this->noIDs) {
	    if($child->alias == $segment) {
	      return $child->id;
	    }
	  }
	  else {
	    if($child->id == (int)$segment) {
	      return $child->id;
	    }
	  }
	}
      }
    }

    return false;
  }


  /**
   * Method to get the id for a tag
   *
   * @param   string  $segment  Segment to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getTagsId($segment, $query)
  {
    return $this->getTagId($segment, $query);
  }


  /**
   * Method to get the id for a product
   *
   * @param   string  $segment  Segment of the product to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getProductId($segment, $query)
  {
    if($this->noIDs) {
      $db = JFactory::getDbo();
      $dbquery = $db->getQuery(true);

      if(ShopHelper::switchLanguage()) {
	// Finds out the item id for the given translated alias (if any).
	$dbquery->select('item_id')
		->from($dbquery->qn('#__ketshop_translation'))
		// N.B: Alias is unique for each translated item.
		->where('alias='.$db->Quote($segment))
		->where('language='.$db->Quote(ShopHelper::switchLanguage(true)))
		->where('item_type='.$db->Quote('product'));
	$db->setQuery($dbquery);

	if((int)$db->loadResult()) {
	  return (int)$db->loadResult();
	}
      }

      $dbquery->clear();
      $dbquery->select('id')
	      ->from($dbquery->qn('#__ketshop_product'))
              // N.B: Alias is unique for each item.
	      ->where('alias='.$dbquery->q($segment));
      $db->setQuery($dbquery);

      return (int)$db->loadResult();
    }

    return (int)$segment;
  }
}


/**
 * Product router functions
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param   array  &$query  An array of URL arguments
 *
 * @return  array  The URL arguments to use to assemble the subsequent URL.
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function KetshopBuildRoute(&$query)
{
  $app = JFactory::getApplication();
  $router = new KetshopRouter($app, $app->getMenu());

  return $router->build($query);
}


/**
 * Product router functions
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param   array  $segments  The segments of the URL to parse.
 *
 * @return  array  The URL attributes to be used by the application.
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function KetshopParseRoute($segments)
{
  $app = JFactory::getApplication();
  $router = new KetshopRouter($app, $app->getMenu());

  return $router->parse($segments);
}

