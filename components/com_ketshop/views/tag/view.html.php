<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_COMPONENT_SITE.'/helpers/pricerule.php';

/**
 * HTML View class for the KetShop component.
 */
class KetshopViewTag extends JViewLegacy
{
  /**
   * Execute and display a template script.
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  mixed  A string if successful, otherwise a Error object.
   */

  /**
   * State data
   *
   * @var    \Joomla\Registry\Registry
   * @since  3.2
   */
  protected $state;

  /**
   * Tag items data
   *
   * @var    array
   * @since  3.2
   */
  protected $items;

  /**
   * Pagination object
   *
   * @var    JPagination
   * @since  3.2
   */
  protected $pagination;

  /**
   * @var    array  Array of leading items for blog display
   * @since  3.2
   */
  protected $lead_items = array();

  /**
   * @var    array  Array of intro (multicolumn display) items for blog display
   * @since  3.2
   */
  protected $intro_items = array();

  /**
   * @var    array  Array of links in blog display
   * @since  3.2
   */
  protected $link_items = array();

  /**
   * @var    integer  Number of columns in a multi column display
   * @since  3.2
   */
  protected $columns = 1;

  /**
   * @var    string  The name of the extension for the category
   * @since  3.2
   */
  protected $extension = 'com_ketshop';

  protected $tag;
  protected $children;
  protected $tagMaxLevel;
  protected $nowDate;
  protected $user;
  protected $uri;
  public $params;
  public $shopSettings;
  public $filterAttributes = null;

  public function display($tpl = null)
  {
    $app = JFactory::getApplication();
    $this->params = $app->getParams();

    // Get some data from the models
    $this->state = $this->get('State');
    $this->items = $this->get('Items');
    $this->tag = $this->get('Tag');
    $this->children = $this->get('Children');
    $this->pagination = $this->get('Pagination');
    $this->tagMaxLevel = $this->params->get('tag_max_level');
    $model = $this->getModel();

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      $app->enqueueMessage($errors, 'error');
      return false;
    }

    if($this->tag == false) {
      $app->enqueueMessage(JText::_('COM_KETSHOP_TAG_NOT_FOUND'), 'error');
      return false;
    }

    //Check whether tag access level allows access.
    $this->user = JFactory::getUser();
    $groups = $this->user->getAuthorisedViewLevels();
    if(!in_array($this->tag->access, $groups)) {
      $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
      return false;
    }

    // Prepare the data
    // Get the metrics for the structural page layout.
    $numLeading = $this->params->def('num_leading_products', 1);
    $numIntro   = $this->params->def('num_intro_products', 4);
    $numLinks   = $this->params->def('num_links', 4);

    //Get the current url, (needed in the product edit layout).
    $this->uri = JUri::getInstance();

    //Set variables used for a multilangual purpose.
    $isSiteMultilng = ShopHelper::isSiteMultilingual();
    $assocKeys = $itemIds = array();
    $langTag = ShopHelper::switchLanguage(true);

    // Prepare the data.
    // Compute the product slugs.
    foreach($this->items as $item) {
      $item->slug = $item->alias ? ($item->id.':'.$item->alias) : $item->id;
      $item->maintagslug = $item->main_tag_alias ? ($item->main_tag_id.':'.$item->main_tag_alias) : $item->main_tag_id;

      //Variables needed in the product edit layout.
      $item->user_id = $this->user->get('id');
      $item->uri = $this->uri;
      //Needed for the product properties layouts.
      $item->attributes_location = $item->weight_location = $item->dimensions_location = 'summary';

      //For compatibility reasons the main tag id has to be passed through an array.
      $item->tagid = array($item->main_tag_id);

      //Set the default language value.
      $item->language = 0;
      if($isSiteMultilng) {
        $item->language = $langTag;

        //Collects the associated menu item key (if any) of each item.
        if(!empty($item->assoc_menu_item_key) && !in_array($item->assoc_menu_item_key, $assocKeys)) {
          $assocKeys[] = $item->assoc_menu_item_key;
        }
      }

      $itemIds[] = $item->id;
    }

    if(!empty($assocKeys)) {
      $assocMenuItems = $this->getModel()->getAssocMenuItems($assocKeys, $langTag);
      //Binds the results to the products.
      foreach($this->items as $key => $item) {
        foreach($assocMenuItems as $assocMenuItem) {
          if($assocMenuItem->key == $item->assoc_menu_item_key && $assocMenuItem->tag_id) {
            //Add the "associated main" tag id to the beginning of the id array.
            array_unshift($this->items[$key]->tagid, $assocMenuItem->tag_id);
          }
        }
      }
    }

    // Check for layout override only if this is not the active menu item
    // If it is the active menu item, then the view and tag id will match
    $app = JFactory::getApplication();
    $active = $app->getMenu()->getActive();
    $menus = $app->getMenu();

    //The tag has no itemId and thus is not linked to any menu item. 
    if((!$active) || ((strpos($active->link, 'view=tag') === false) ||
		      (strpos($active->link, '&id='.(string)$this->tag->id) === false))) {
      // Get the layout from the merged tag params
      if($layout = $this->params->get('tag_layout')) {
	$this->setLayout($layout);
      }
    }
    // At this point, we are in a menu item, so we don't override the layout
    elseif(isset($active->query['layout'])) {
      // We need to set the layout from the query in case this is an alternative menu item (with an alternative layout)
      $this->setLayout($active->query['layout']);
    }
    //Note: In case the layout parameter is not found within the query, the default layout
    //will be set.

    // For blog layouts, preprocess the breakdown of leading, intro and linked products.
    // This makes it much easier for the designer to just interrogate the arrays.
    if(($this->params->get('layout_type') == 'blog') || ($this->getLayout() == 'blog')) {
      foreach($this->items as $i => $item) {
	if($i < $numLeading) {
	  $this->lead_items[] = $item;
	}
	elseif($i >= $numLeading && $i < $numLeading + $numIntro) {
	  $this->intro_items[] = $item;
	}
	elseif($i < $numLeading + $numIntro + $numLinks) {
	  $this->link_items[] = $item;
	}
	else {
	  continue;
	}
      }

      $this->columns = max(1, $this->params->def('num_columns', 1));

      $order = $this->params->def('multi_column_order', 1);

      if($order == 0 && $this->columns > 1) {
	// Call order down helper
	$this->intro_items = KetshopHelperQuery::orderDownColumns($this->intro_items, $this->columns);
      }
    }

    //Get the global settings of the shop.
    $this->shopSettings = ShopHelper::getShopSettings();

    //Set the label of the current tax method for more convenience.
    $taxMethodLabel = 'COM_KETSHOP_FIELD_INCLUDING_TAXES_LABEL';
    if($this->shopSettings['tax_method'] == 'excl_tax') {
      $taxMethodLabel = 'COM_KETSHOP_FIELD_EXCLUDING_TAXES_LABEL';
    }

    $nbItems = count($this->items);
    //Set each product that should be displayed.
    for($i = 0; $i < $nbItems; $i++) {
      $item = $this->items[$i];

      //Convert item object into associative array by just casttype it.
      $product = (array)$item;
      //Get the possible price rules linked to the product.
      $product['pricerules'] = PriceruleHelper::getCatalogPriceRules($product);
      //Get the catalog price of the product.
      $catalogPrice = PriceruleHelper::getCatalogPrice($product, $this->shopSettings);

      $item->final_price = $catalogPrice->final_price;
      $item->pricerules = $catalogPrice->pricerules;
      $item->tax_method_label = $taxMethodLabel;
      
      if($this->shopSettings['tax_method'] == 'excl_tax') {
	$item->final_price_with_taxes = UtilityHelper::getPriceWithTaxes($item->final_price, $item->tax_rate);
	$item->final_price_with_taxes = UtilityHelper::roundNumber($item->final_price_with_taxes,
								   $this->shopSettings['rounding_rule'],
								   $this->shopSettings['digits_precision']);
      }

      //Get the stock state.
      if($item->stock_subtract && $item->shippable) {
        $item->stock_state = ShopHelper::getStockState($item->min_stock_threshold,
							     $item->max_stock_threshold,
							     $item->stock, $item->allow_order);
      }
      else { //If a product is not subtracted from stock or is not shippable, we assume that stock is always full.
	$item->stock_state = 'maximum'; 
      }

      //Shop settings is needed in the layout.
      $item->shop_settings = $this->shopSettings;
    }

    //Since the final price of a product is known after the SQL query
    //is executed in the model, we need to sort products again each time they are
    //filtered by price.
    //In order to do so we use a simple bubble sort algorithm.
    if($this->state->get('list.ordering') == 'p.sale_price') {
      for($i = 0; $i < $nbItems; $i++) {
	for($j = 0; $j < $nbItems - 1; $j++) {
	  if(strtoupper($this->state->get('list.direction')) == 'ASC') {
	    if($this->items[$j]->final_price > $this->items[$j + 1]->final_price) {
	      $temp = $this->items[$j + 1];
	      $this->items[$j + 1] = $this->items[$j];
	      $this->items[$j] = $temp;
	    }
	  }
	  else { //direction = desc
	    if($this->items[$j]->final_price < $this->items[$j + 1]->final_price) {
	      $temp = $this->items[$j + 1];
	      $this->items[$j + 1] = $this->items[$j];
	      $this->items[$j] = $temp;
	    }
	  }
	}
      }
    }

    //Set the name of the active layout in params, (needed for the filter ordering layout).
    $this->params->set('active_layout', $this->getLayout());
    //Set the filter_ordering parameter for the layout.
    $this->filter_ordering = $this->state->get('list.filter_ordering');

    if($this->params->get('filter_ids') !== null) {
      $this->filterAttributes = $model->getFilterAttributes($this->params->get('filter_ids'), $itemIds);
    }

    $this->nowDate = JFactory::getDate()->toSql();

    // Creates a new JForm object
    $this->filterForm = new JForm('FilterForm');
    $this->filterForm->loadFile(JPATH_SITE.'/components/com_ketshop/models/forms/filter_tag.xml');

    $this->prepareDocument();

    $this->setDocument();

    return parent::display($tpl);
  }


  /**
   * Method to prepares the document
   *
   * @return  void
   *
   * @since   3.2
   */
  protected function prepareDocument()
  {
    $app = JFactory::getApplication();
    // Because the application sets a default page title,
    // we need to get it from the menu item itself
    $menus = $app->getMenu();
    $menu = $menus->getActive();

    if($menu) {
      $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
    }

    $title = $this->params->get('page_title', '');

    // Check for empty title and add site name if param is set
    if(empty($title)) {
      $title = $app->get('sitename');
    }
    elseif($app->get('sitename_pagetitles', 0) == 1) {
      $title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
    }
    elseif($app->get('sitename_pagetitles', 0) == 2) {
      $title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
    }

    if(empty($title)) {
      $title = $this->tag->title;
    }

    $this->document->setTitle($title);

    if($this->tag->metadesc) {
      $this->document->setDescription($this->tag->metadesc);
    }
    elseif($this->params->get('menu-meta_description')) {
      $this->document->setDescription($this->params->get('menu-meta_description'));
    }

    if($this->tag->metakey) {
      $this->document->setMetadata('keywords', $this->tag->metakey);
    }
    elseif($this->params->get('menu-meta_keywords')) {
      $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
    }

    if($this->params->get('robots')) {
      $this->document->setMetadata('robots', $this->params->get('robots'));
    }

    if(!is_object($this->tag->metadata)) {
      $this->tag->metadata = new Registry($this->tag->metadata);
    }

    $mdata = $this->tag->metadata->toArray();

    if(($app->get('MetaAuthor') == '1') && $mdata['author']) {
      $this->document->setMetaData('author', $mdata['author']);
    }

    foreach($mdata as $k => $v) {
      if($v) {
	$this->document->setMetadata($k, $v);
      }
    }

    return;
  }



  protected function setDocument() 
  {
    //Include css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/css/ketshop.css');
  }
}
