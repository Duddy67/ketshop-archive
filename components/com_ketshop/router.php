<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.application.categories');

/**
 * Build the route for the com_ketshop component
 *
 * @param	array	An array of URL arguments
 *
 * @return	array	The URL arguments to use to assemble the subsequent URL.
 */
function KetshopBuildRoute(&$query)
{
  $segments = array();

  if(isset($query['view'])) {
    $segments[] = $query['view'];
    unset($query['view']);
  }

  if(isset($query['id'])) {
    $segments[] = $query['id'];
    unset($query['id']);
  }

  if(isset($query['catid'])) {
    $segments[] = $query['catid'];
    unset($query['catid']);
  }

  if(isset($query['layout'])) {
    unset($query['layout']);
  }

  return $segments;
}


/**
 * Parse the segments of a URL.
 *
 * @param	array	The segments of the URL to parse.
 *
 * @return	array	The URL attributes to be used by the application.
 */
function KetshopParseRoute($segments)
{
  $vars = array();

  switch($segments[0])
  {
    case 'categories':
	   $vars['view'] = 'categories';
	   break;
    case 'category':
	   $vars['view'] = 'category';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   break;
    case 'tag':
	   $vars['view'] = 'tag';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   break;
    case 'product':
	   $vars['view'] = 'product';
	   $id = explode(':', $segments[1]);
	   $vars['id'] = (int)$id[0];
	   $catid = explode(':', $segments[2]);
	   $vars['catid'] = (int)$catid[0];
	   break;
    case 'form':
	   $vars['view'] = 'form';
	   //Form layout is always set to 'edit'.
	   $vars['layout'] = 'edit';
	   break;
    case 'orders':
	   $vars['view'] = 'orders';
	   break;
    case 'order':
	   $vars['view'] = 'order';
	   $id = explode(':', $segments[0]);
	   $vars['order_id'] = (int)$id[0];
	   break;
    case 'cart':
	   $vars['view'] = 'cart';
	   break;
    case 'address':
	   $vars['view'] = 'address';
	   break;
    case 'summary':
	   $vars['view'] = 'summary';
	   break;
    case 'payment':
	   $vars['view'] = 'payment';
	   break;
  }

  return $vars;
}

