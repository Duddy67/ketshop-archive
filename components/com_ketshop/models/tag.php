<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT_SITE.'/helpers/query.php';
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';

/**
 * KetShop Component Model
 *
 * @package     Joomla.Site
 * @subpackage  com_ketshop
 */
class KetshopModelTag extends JModelList
{
  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */

  /**
   * Constructor.
   *
   * @param   array  An optional associative array of configuration settings.
   * @see     JController
   * @since   1.6
   */
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'p.id',
	      'name', 'p.name',
	      'author', 'p.author',
	      'created', 'p.created',
	      'catid', 'p.catid', 'category_title',
	      'modified', 'p.modified',
	      'published', 'p.published',
	      'tm.ordering',
	      'publish_up', 'p.publish_up',
	      'publish_down', 'p.publish_down',
      );
    }

    parent::__construct($config);
  }


  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   */
  protected function populateState($ordering = null, $direction = null)
  {
    $app = JFactory::getApplication('site');

    //Get and set the current tag id.
    $pk = $app->input->getInt('id');
    $this->setState('tag.id', $pk);

    //getParams function return global parameters overrided by the menu parameters (if any).
    //Note: Some specific parameters of this menu are not returned.
    $params = $app->getParams();

    $menuParams = new JRegistry;

    //Get the menu with its specific parameters.
    if($menu = $app->getMenu()->getActive()) {
      $menuParams->loadString($menu->params);
    }

    //Merge Global and Menu Item params into a new object.
    $mergedParams = clone $menuParams;
    $mergedParams->merge($params);

    // Load the parameters in the session.
    $this->setState('params', $mergedParams);

    // process show_noauth parameter

    //The user is not allowed to see the registered products unless he has the proper view permissions.
    if(!$params->get('show_noauth')) {
      //Set the access filter to true. This way the SQL query checks against the user
      //view permissions and fetchs only the products this user is allowed to see.
      $this->setState('filter.access', true);
    }
    //The user is allowed to see any of the registred products (ie: intro_text as a teaser). 
    else {
      //The user is allowed to see all the products or some of them.
      //All of the products are returned and it's up to thelayout to 
      //deal with the access (ie: redirect the user to login form when Read more
      //button is clicked).
      $this->setState('filter.access', false);
    }

    // List state information
    //Get the number of products to display per page.
    //Note: The LIMIT clause is added in the setQuery function: libraries/joomla/database/query/driver.php
    $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'uint');
    $this->setState('list.limit', $limit);

    //Get the limitstart variable (used for the pagination) from the form variable.
    $limitstart = $app->input->get('limitstart', 0, 'uint');
    $this->setState('list.start', $limitstart);

    // Optional filter text
    $this->setState('list.filter', $app->input->getString('filter-search'));
    //Get the value of the select list and load it in the session.
    $this->setState('list.filter_ordering', $app->input->getString('filter-ordering'));

    //Check if the user is root. 
    $user = JFactory::getUser();
    if(!$user->get('isRoot')) {
      // Limit to published for people who are not super user.
      $this->setState('filter.published', 1);

      // Filter by start and end dates.
      $this->setState('filter.publish_date', true);
    }
    else {
      //Super users can access published, unpublished and archived products.
      $this->setState('filter.published', array(0, 1, 2));
    }
  }


  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */
  public function getItems()
  {
    // Invoke the parent getItems method (using the getListQuery method) to get the main list
    $items = parent::getItems();
    $input = JFactory::getApplication()->input;
    $itemIdsAttr = array();

    //Get some user data.
    $user = JFactory::getUser();
    $userId = $user->get('id');
    $guest = $user->get('guest');
    $groups = $user->getAuthorisedViewLevels();

    // Convert the params field into an object, saving original in _params
    foreach($items as $key => $item) {
      //Get the product parameters only.
      $productParams = new JRegistry;
      $productParams->loadString($item->params);
      //Set the params attribute, eg: the merged global and menu parameters set
      //in the populateState function.
      $item->params = clone $this->getState('params');

      // For Blog layout, product params override menu item params only if menu param='use_product'.
      // Otherwise, menu item params control the layout.
      // If menu item is 'use_product' and there is no product param, use global.
      if($input->getString('layout') == 'blog' || $this->getState('params')->get('layout_type') == 'blog') {
	// Create an array of just the params set to 'use_product'
	$menuParamsArray = $this->getState('params')->toArray();
	$productArray = array();

	foreach($menuParamsArray as $key => $value) {
	  if($value === 'use_product') {
	    // If the product has a value, use it
	    if($productParams->get($key) != '') {
	      // Get the value from the product
	      $productArray[$key] = $productParams->get($key);
	    }
	    else {
	      // Otherwise, use the global value
	      $productArray[$key] = $globalParams->get($key);
	    }
	  }
	}

	// Merge the selected product params
	if(count($productArray) > 0) {
	  $productParams = new JRegistry;
	  $productParams->loadArray($productArray);
	  $item->params->merge($productParams);
	}
      }
      else { //Default layout (list).
	// Merge all of the product params.
	//Note: Product params (if they are defined) override global/menu params.
	$item->params->merge($productParams);
      }

      // Compute the asset access permissions.
      // Technically guest could edit a product, but lets not check that to improve performance a little.
      if(!$guest) {
	$asset = 'com_ketshop.product.'.$item->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $item->params->set('access-edit', true);
	}
	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $item->created_by) {
	    $item->params->set('access-edit', true);
	  }
	}
      }

      $access = $this->getState('filter.access');
      //Set the access view parameter.
      if($access) {
	// If the access filter has been set, we already have only the products this user can view.
	$item->params->set('access-view', true);
      }
      else { // If no access filter is set, the layout takes some responsibility for display of limited information.
	if($item->catid == 0 || $item->category_access === null) {
	  //In case the product is not linked to a category, we just check permissions against the product access.
	  $item->params->set('access-view', in_array($item->access, $groups));
	}
	else { //Check the user permissions against the product access as well as the category access.
	  $item->params->set('access-view', in_array($item->access, $groups) && in_array($item->category_access, $groups));
	}
      }

      //Set the type of date to display, (default layout only).
      if($this->getState('params')->get('layout_type') != 'blog'
	  && $this->getState('params')->get('list_show_date')
	  && $this->getState('params')->get('order_date')) {
	switch($this->getState('params')->get('order_date')) {
	  case 'modified':
		  $item->displayDate = $item->modified;
		  break;

	  case 'published':
		  $item->displayDate = ($item->publish_up == 0) ? $item->created : $item->publish_up;
		  break;

	  default: //created
		  $item->displayDate = $item->created;
	}
      }

      // Get the tags
      $item->tags = new JHelperTags;
      $item->tags->getItemTags('com_ketshop.product', $item->id);

      //Collect product ids in order to get product attributes
      $itemIdsAttr[] = $item->id;
    }

    //Add attributes to each product item.
    if(!empty($items)) {
      //Get attributes of each current product.
      $attributes = ShopHelper::getProductAttributes($itemIdsAttr);

      //Add attributes to products.
      $nbItems = count($items); 
      for($i = 0; $i < $nbItems; $i++) {
	$itemAttributes = array();
	foreach($attributes as $attribute) {
	  if($attribute->prod_id == $items[$i]->id) {
	    $itemAttributes[] = $attribute;
	  }
	}

	$items[$i]->attributes = $itemAttributes;
      }
    }

    return $items;
  }


  /**
   * Method to build an SQL query to load the list data (product items).
   *
   * @return  string    An SQL query
   * @since   1.6
   */
  protected function getListQuery()
  {
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    // Create a new query object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //The translated fields of a product.
    $translatedFields = 'p.name,p.alias,p.intro_text,';
    //Check if a translation is needed.
    if(ShopHelper::switchLanguage()) {
      //Get the SQL query parts needed for the translation of the products.
      $prodTranslation = ShopHelper::getTranslation('product', 'id', 'p', 'p');
      //Translation fields are now defined by the SQL conditions.
      $translatedFields = $prodTranslation->translated_fields.',';
    }

    // Select required fields from the categories.
    $query->select($this->getState('list.select', 'p.id,'.$translatedFields.'p.catid,'.
	                           'tm.tag_id,p.published,p.checked_out,p.checked_out_time,p.created,'.
				   'p.created_by,p.access,p.params,p.metadata,p.metakey,p.metadesc,p.hits,'.
				   'p.publish_up,p.publish_down,p.modified,p.modified_by,'.
	                           'p.type,p.base_price,p.sale_price,p.min_quantity,p.max_quantity,p.stock,p.stock_subtract,'.
				   'p.shippable,p.min_stock_threshold,p.max_stock_threshold,p.weight_unit,p.weight,'.
				   'p.code,p.allow_order,p.dimensions_unit,p.length,p.width,p.height,p.img_reduction_rate,'.
				   'p.attribute_group,p.option_name,IF(p.new_until > NOW(),1,0) AS is_new'))
	  ->from($db->quoteName('#__ketshop_product').' AS p')
	  ->join('LEFT', '#__ketshop_product_tag_map AS tm ON p.id=tm.product_id')
	  //Display products labeled with the current tag.
	  ->where('tm.tag_id='.(int)$this->getState('tag.id'));

    // Join on tag table.
    $query->select('ta.title AS tag_title, ta.alias AS tag_alias')
	  ->join('LEFT', '#__tags AS ta ON ta.id='.(int)$this->getState('tag.id'))
	  //Ensure the current tag is published.
	  ->where('ta.published=1');

    // Join over the tags to get parent tag title.
    $query->select('tag_parent.title AS tag_parent_title, tag_parent.id AS tag_parent_id,'.
		   'tag_parent.path AS tag_parent_route, tag_parent.alias AS tag_parent_alias')
	  ->join('LEFT', '#__tags as tag_parent ON tag_parent.id = ta.parent_id');

    // Join on category table.
    $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	  ->join('LEFT', '#__categories AS ca ON ca.id = p.catid')
	  //Ensure the category the product is in is published.
	  ->where('ca.published=1');

    // Join over the categories to get parent category title.
    $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	  ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

    // Join over the users.
    $query->select('us.name AS author')
	  ->join('LEFT', '#__users AS us ON us.id = p.created_by');

    // Join over the asset groups.
    $query->select('al.title AS access_level');
    $query->join('LEFT', '#__viewlevels AS al ON al.id = p.access');

    $taxName ='t.name AS tax_name';
    if(ShopHelper::switchLanguage()) {
      //Join over the product translation.
      $query->join('LEFT', $prodTranslation->left_join);

      //Get the SQL query parts needed for the tax translation.
      $taxTranslation = ShopHelper::getTranslation('tax', 'tax_id', 'p', 't', 'tax_name');
      //Join over the tax translation.
      $query->join('LEFT', $taxTranslation->left_join);
      //Translation fields are now defined by the SQL conditions.
      $taxName = $taxTranslation->translated_fields;
    }

    // Join over the tax 
    $query->select('t.rate AS tax_rate,'.$taxName);
    $query->join('LEFT', '#__ketshop_tax AS t ON t.id = p.tax_id');

    // Join over the product image
    $query->select('i.src AS img_src, i.width AS img_width, i.height AS img_height, i.alt AS img_alt');
    $query->join('LEFT', '#__ketshop_prod_image AS i ON i.prod_id = p.id AND i.ordering=1');

    // Filter by access level.
    //Note: if($access = $this->getState('filter.access')) is a shorthand for:
    //      $access = $this->getState('filter.access')    
    //      if($access) { ...
    if($access = $this->getState('filter.access')) {
      $query->where('p.access IN ('.$groups.')')
	    //Category access is also taken in account.
	    ->where('ca.access IN ('.$groups.')');
    }

    // Filter by state
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      //Users are only allowed to see published products.
      $query->where('p.published='.(int)$published);
    }
    elseif(is_array($published)) {
      //Only super users are allowed to see products with different states.
      JArrayHelper::toInteger($published);
      $published = implode(',', $published);
      $query->where('p.published IN ('.$published.')');
    }

    //Do not show expired products to users who are not Root.
    if($this->getState('filter.publish_date')) {
      // Filter by start and end dates.
      $nullDate = $db->quote($db->getNullDate());
      $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

      $query->where('(p.publish_up = '.$nullDate.' OR p.publish_up <= '.$nowDate.')')
	    ->where('(p.publish_down = '.$nullDate.' OR p.publish_down >= '.$nowDate.')');
    }

    // Filter by search in title
    $search = $this->getState('list.filter');
    //Get the field to search by.
    $field = $this->getState('params')->get('filter_field');
    if(!empty($search)) {
      if(stripos($search, 'code:') === 0) {
	$query->where('p.code = '.$db->quote(substr($search, 5)));
      }
      else {
	$search = $db->quote('%'.$db->escape($search, true).'%');
	$query->where('(p.'.$field.' LIKE '.$search.')');
      }
    }

    //Get the products ordering by default set in the menu options. (Note: sec stands for secondary). 
    $productOrderBy = $this->getState('params')->get('orderby_sec', 'rdate');
    //If products are sorted by date (ie: date, rdate), order_date defines
    //which type of date should be used (ie: created, modified or publish_up).
    $productOrderDate = $this->getState('params')->get('order_date');
    //Get the field to use in the ORDER BY clause according to the orderby_sec option.
    $orderBy = KetshopHelperQuery::orderbySecondary($productOrderBy, $productOrderDate);

    //Filter by order (ie: the select list set by the end user).
    $filterOrdering = $this->getState('list.filter_ordering');
    //If the end user has define an order, we override the ordering by default.
    if(!empty($filterOrdering)) {
      $orderBy = KetshopHelperQuery::orderbySecondary($filterOrdering, $productOrderDate);
    }

    //Ordering products against the product/tag mapping table.
    if(preg_match('#^tm.ordering( DESC)?$#', $orderBy, $matches)) {
      //Note: Products with NULL order value are placed at the end of the list.
      $query->select('ISNULL(tm.ordering), tm.ordering AS tm_ordering');
      $orderBy = 'ISNULL(tm.ordering) ASC, tm_ordering';

      //Check for DESC direction.
      if(isset($matches[1])) {
	$orderBy .= $matches[1]; 
      }
    }

    $query->order($orderBy);
//echo $query;
    return $query;
  }


  //Get the current tag.
  public function getTag()
  {
    $tagId = $this->getState('tag.id');
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__tags')
	  ->where('id='.(int)$tagId);
    $db->setQuery($query);
    $tag = $db->loadObject();

    $this->setState('tag.level', $tag->level);

    $images = new JRegistry;
    $images->loadString($tag->images);
    $tag->images = $images;

    return $tag;
  }


  public function getChildren()
  {
    $tagId = $this->getState('tag.id');
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    //Add one to the start level as we don't want the current tag in the result.
    $startLevel = $this->getState('tag.level', 1) + 1;
    $endLevel = $this->getState('params')->get('tag_max_level', 0);

    if($endLevel > 0) { //Compute the end level from the start level.
      $endLevel = $startLevel + $endLevel;
    }
    elseif($endLevel == -1) { //Display all the subtags.
      $endLevel = 10;
    }

    //Ensure subcats are required.
    if($endLevel) {
      //Get the tag order type.
      $tagOrderBy = $this->getState('params')->get('orderby_pri');
      $orderBy = KetshopHelperQuery::orderbyPrimary($tagOrderBy);
      //Remove the comma and space from the string.
      $orderBy = substr($orderBy, 0, -2);

      $db = $this->getDbo();
      $query = $db->getQuery(true);
      $query->select('DISTINCT n.*')
	    ->from('#__tags AS n, #__tags AS p')
	    ->where('n.lft BETWEEN p.lft AND p.rgt')
	    ->where('n.level >= '.(int)$startLevel.' AND n.level <= '.(int)$endLevel)
	    ->where('n.access IN('.$groups.')')
	    ->where('n.published=1')
	    ->where('p.id='.(int)$tagId);

      if(!empty($orderBy)) {
	$query->order($orderBy);
      }

      $db->setQuery($query);
      $children = $db->loadObjectList();

      if(empty($children)) {
        return $children;
      }

      if($this->getState('params')->get('show_tagged_num_products', 0)) {
	//Get the tag children ids.
	$ids = array();
	foreach($children as $child) {
	  $ids[] = $child->id;
	}

	//Compute the number of products for each tag.
	$query->clear()
	      ->select('tm.tag_id, COUNT(*) AS numitems')
	      ->from('#__ketshop_product_tag_map AS tm')
	      ->join('LEFT', '#__ketshop_product AS p ON p.id=tm.product_id')
	      ->join('LEFT', '#__categories AS ca ON ca.id=p.catid')
	      ->where('p.access IN('.$groups.')')
	      ->where('ca.access IN('.$groups.')');

	// Filter by state
	$published = $this->getState('filter.published');
	if(is_numeric($published)) {
	  //Only published products are counted when user is not Root.
	  $query->where('p.published='.(int)$published);
	}
	elseif(is_array($published)) {
	  //Products with different states are also taken in account for super users.
	  JArrayHelper::toInteger($published);
	  $published = implode(',', $published);
	  $query->where('p.published IN ('.$published.')');
	}

	//Do not count expired products when user is not Root.
	if($this->getState('filter.publish_date')) {
	  // Filter by start and end dates.
	  $nullDate = $db->quote($db->getNullDate());
	  $nowDate = $db->quote(JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true));

	  $query->where('(p.publish_up = '.$nullDate.' OR p.publish_up <= '.$nowDate.')')
		->where('(p.publish_down = '.$nullDate.' OR p.publish_down >= '.$nowDate.')');
	}

	$query->where('tm.tag_id IN('.implode(',', $ids).') GROUP BY tm.tag_id');
	$db->setQuery($query);
	$tags = $db->loadObjectList('tag_id');

	//Set the numitems attribute.
	foreach($children as $child) {
	  $child->numitems = 0;

	  if(isset($tags[$child->id])) {
	    $child->numitems = $tags[$child->id]->numitems;
	  }
	}
      }

      return $children;
    }

    return array();
  }


  /**
   * Increment the hit counter for the tag.
   *
   * @param   int  $pk  Optional primary key of the tag to increment.
   *
   * @return  boolean True if successful; false otherwise and internal error set.
   *
   * @since   3.2
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('tag.id');

      $table = JTable::getInstance('Tag', 'JTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}



