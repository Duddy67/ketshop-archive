<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class KetshopModelProduct extends JModelItem
{

  protected $_context = 'com_ketshop.product';

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   *
   * @return void
   */
  protected function populateState()
  {
    $app = JFactory::getApplication('site');

    // Load state from the request.
    $pk = $app->input->getInt('id');
    $this->setState('product.id', $pk);

    //Load the global parameters of the component.
    $params = $app->getParams();
    $this->setState('params', $params);
  }


  //Returns a Table object, always creating it.
  public function getTable($type = 'Product', $prefix = 'KetshopTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @since   12.2
   */
  public function getItem($pk = null)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState('product.id');
    $user = JFactory::getUser();

    if($this->_item === null) {
      $this->_item = array();
    }

    if(!isset($this->_item[$pk])) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);

      //The translated fields of a product.
      $translatedFields = 'p.name,p.alias,p.intro_text,p.full_text,p.metakey,p.metadesc,p.metadata,p.xreference,';
      //Check if a translation is needed.
      if(ShopHelper::switchLanguage()) {
	//Get the SQL query parts needed for the translation of the products.
	$prodTranslation = ShopHelper::getTranslation('product', 'id', 'p', 'p');
	//Translation fields are now defined by the SQL conditions.
	$translatedFields = $prodTranslation->translated_fields.',';
      }

      //Select required fields from the products.
      //During the selection we check if product is new and set its is_new flag.
      $query->select($this->getState('list.select', 'p.id,p.type,'.$translatedFields.'p.code,p.allow_order,p.catid,p.access,'.
				     'p.base_price,p.sale_price,p.min_quantity,p.max_quantity,p.stock,p.stock_subtract,p.main_tag_id,'.
				     'p.checked_out,p.checked_out_time,p.shippable,p.min_stock_threshold,p.max_stock_threshold,'.
				     'p.weight_unit,p.weight,p.dimensions_unit,p.length,p.width,p.height,p.img_reduction_rate,'.
				     'p.published,p.publish_up,p.publish_down,p.hits,p.params,p.attribute_group,'.
				     'p.created_by, IF(p.new_until > NOW(),1,0) AS is_new'))
	    ->from($db->quoteName('#__ketshop_product').' AS p')
	    ->where('p.id='.$pk);

      // Join over the tags to get the main tag title.
      $query->select('main_tag.title AS main_tag_title, main_tag.path AS main_tag_route,'.
                     'main_tag.alias AS main_tag_alias')
            ->join('LEFT', '#__tags AS main_tag ON main_tag.id = p.main_tag_id');

      // Join on category table.
      $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	    ->join('LEFT', '#__categories AS ca on ca.id = p.catid');

      // Join on user table.
      $query->select('us.name AS author')
	    ->join('LEFT', '#__users AS us on us.id = p.created_by');

      // Join over the categories to get parent category titles
      $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	    ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

      if((!$user->authorise('core.edit.state', 'com_ketshop')) && (!$user->authorise('core.edit', 'com_ketshop'))) {
	// Filter by start and end dates.
	$nullDate = $db->quote($db->getNullDate());
	$nowDate = $db->quote(JFactory::getDate()->toSql());
	$query->where('(p.publish_up = '.$nullDate.' OR p.publish_up <= '.$nowDate.')')
	      ->where('(p.publish_down = '.$nullDate.' OR p.publish_down >= '.$nowDate.')');
      }

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
      $query->select('t.rate AS tax_rate,'.$taxName)
	    ->join('LEFT', '#__ketshop_tax AS t ON t.id = p.tax_id')
	    ->where('p.id='.$pk);

      $db->setQuery($query);
      $data = $db->loadObject();

      if(is_null($data)) {
	return JError::raiseError(404, JText::_('COM_KETSHOP_ERROR_PRODUCT_NOT_FOUND'));
      }

      // Convert parameter fields to objects.
      $registry = new JRegistry;
      $registry->loadString($data->params);

      $data->params = clone $this->getState('params');
      $data->params->merge($registry);

      $user = JFactory::getUser();
      // Technically guest could edit an article, but lets not check that to improve performance a little.
      if(!$user->get('guest')) {
	$userId = $user->get('id');
	$asset = 'com_ketshop.product.'.$data->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $data->params->set('access-edit', true);
	}

	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $data->created_by) {
	    $data->params->set('access-edit', true);
	  }
	}
      }

      // Get the tags
      $data->tags = new JHelperTags;
      $data->tags->getItemTags('com_ketshop.product', $data->id);

      $this->_item[$pk] = $data;
    }

    return $this->_item[$pk];
  }


  public function getImages()
  {
    //Get the product id.
    $id = $this->getState('product.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__ketshop_prod_image')
	  ->where('prod_id = '.(int)$id)
	  ->order('ordering');
    // Setup the query
    $db->setQuery($query);

    return $db->loadObjectList();
  }


  /**
   * Increment the hit counter for the product.
   *
   * @param   integer  $pk  Optional primary key of the product to increment.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('product.id');

      $table = JTable::getInstance('Product', 'KetshopTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}

