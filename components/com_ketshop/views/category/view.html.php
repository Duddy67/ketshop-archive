<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
require_once JPATH_COMPONENT_SITE.'/helpers/pricerule.php';

/**
 * HTML View class for the KetShop component.
 */
class KetshopViewCategory extends JViewCategory
{
  /**
   * Execute and display a template script.
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  mixed  A string if successful, otherwise a Error object.
   */

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

  protected $nowDate;
  protected $user;
  protected $uri;
  public $shopSettings;

  public function display($tpl = null)
  {
    //Call parent method with common display elements (state, items etc...) used in
    //category list displays.
    parent::commonCategoryDisplay();

    // Prepare the data
    // Get the metrics for the structural page layout.
    $params     = $this->params;
    $numLeading = $params->def('num_leading_products', 1);
    $numIntro   = $params->def('num_intro_products', 4);
    $numLinks   = $params->def('num_links', 4);

    //Get the user object and the current url, (needed in the product edit layout).
    $this->user = JFactory::getUser();
    $this->uri = JUri::getInstance();

    // Prepare the data.
    // Compute the product slugs.
    foreach($this->items as $item) {
      $item->slug = $item->alias ? ($item->id.':'.$item->alias) : $item->id;
      $item->catslug = $item->category_alias ? ($item->catid.':'.$item->category_alias) : $item->catid;
      $item->parent_slug = ($item->parent_alias) ? ($item->parent_id.':'.$item->parent_alias) : $item->parent_id;
      // No link for ROOT category
      if($item->parent_alias == 'root') {
	$item->parent_slug = null;
      }

      //Variables needed in the product edit layout.
      $item->user_id = $this->user->get('id');
      $item->uri = $this->uri;
      //Needed for the product properties layouts.
      $item->attributes_location = $item->weight_location = $item->dimensions_location = 'summary';
    }

    // Check for layout override only if this is not the active menu item
    // If it is the active menu item, then the view and category id will match
    $app = JFactory::getApplication();
    $active = $app->getMenu()->getActive();

    //The category has no itemId and thus is not linked to any menu item. 
    if((!$active) || ((strpos($active->link, 'view=category') === false) ||
		      (strpos($active->link, '&id='.(string)$this->category->id) === false))) {
      // Get the layout from the merged category params
      if($layout = $this->category->params->get('category_layout')) {
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

    // For blog layouts, preprocess the breakdown of leading, intro and linked articles.
    // This makes it much easier for the designer to just interrogate the arrays.
    if(($params->get('layout_type') == 'blog') || ($this->getLayout() == 'blog')) {
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

      $this->columns = max(1, $params->def('num_columns', 1));

      $order = $params->def('multi_column_order', 1);

      if($order == 0 && $this->columns > 1) {
	// Call order down helper
	$this->intro_items = KetshopHelperQuery::orderDownColumns($this->intro_items, $this->columns);
      }
    }

    //Get the global settings of the shop.
    $this->shopSettings = ShopHelper::getShopSettings();

    //Set the label of the current tax method for more convenience.
    $taxMethodLabel = 'COM_KETSHOP_FIELD_INCLUDING_TAXES_LABEL';
    if($this->shopSettings->tax_method == 'excl_tax') {
      $taxMethodLabel = 'COM_KETSHOP_FIELD_EXCLUDING_TAXES_LABEL';
    }

    $nbItems = count($this->items);
    //Set each product that should be displayed.
    for($i = 0; $i < $nbItems; $i++) {
      $item = $this->items[$i];

      //Get the catalog price of the product.
      $catalogPrice = PriceruleHelper::getCatalogPrice($item, $this->shopSettings);

      $item->final_price = $catalogPrice->final_price;
      $item->rules_info = $catalogPrice->rules_info;
      $item->tax_method_label = $taxMethodLabel;
      
      if($this->shopSettings->tax_method == 'excl_tax') {
	$item->final_price_with_taxes = UtilityHelper::getPriceWithTaxes($item->final_price, $item->tax_rate);
	$item->final_price_with_taxes = UtilityHelper::roundNumber($item->final_price_with_taxes,
								   $this->shopSettings->rounding_rule, $this->shopSettings->digits_precision);
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

    $this->nowDate = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);

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

    //If no title is find, set it to the category title. 
    if(empty($title)) {
      $title = $this->category->title;
    }

    $this->document->setTitle($title);

    if($this->category->metadesc) {
      $this->document->setDescription($this->category->metadesc);
    }
    elseif($this->params->get('menu-meta_description')) {
      $this->document->setDescription($this->params->get('menu-meta_description'));
    }

    if($this->category->metakey) {
      $this->document->setMetadata('keywords', $this->category->metakey);
    }
    elseif($this->params->get('menu-meta_keywords')) {
      $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
    }

    if($this->params->get('robots')) {
      $this->document->setMetadata('robots', $this->params->get('robots'));
    }

    if(!is_object($this->category->metadata)) {
      $this->category->metadata = new Registry($this->category->metadata);
    }

    if(($app->get('MetaAuthor') == '1') && $this->category->get('author', '')) {
      $this->document->setMetaData('author', $this->category->get('author', ''));
    }

    $mdata = $this->category->metadata->toArray();

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
