<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT_SITE.'/helpers/pricerule.php';

/**
 * HTML View class for the KetShop component.
 */
class KetshopViewProduct extends JViewLegacy
{
  protected $state;
  protected $item;
  protected $nowDate;
  protected $user;
  protected $uri;
  public $images;
  public $shopSettings;

  public function display($tpl = null)
  {
    // Initialise variables
    $this->state = $this->get('State');
    $this->item = $this->get('Item');
    $this->images = $this->get('Images');
    $model = $this->getModel();
    $user = JFactory::getUser();

    // Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JError::raiseWarning(500, implode("\n", $errors));
      return false;
    }

    // Compute the item and category slugs.
    $this->item->slug = $this->item->alias ? ($this->item->id.':'.$this->item->alias) : $this->item->id;
    //Get the possible extra class name.
    $this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx'));

    // Gets the user object and the current url, (needed in the product edit layout).
    $this->user = JFactory::getUser();
    $this->uri = JUri::getInstance();

    // Gets the attributes of the product (ie: of the basic product variant).
    $this->item->attributes = $model->getAttributeData($this->item->id, $this->item->var_id);

    // Needed for the product properties layouts.
    $this->item->attributes_location = $this->item->weight_location = $this->item->dimensions_location = 'page';

    // Gets the global settings of the shop.
    $this->shopSettings = ShopHelper::getShopSettings();
    // Adds the settings to the item (for the layout).
    $this->item->shop_settings = $this->shopSettings;

    // Converts item object into associative array by just casttype it.
    $product = (array)$this->item;

    // Gets the price rules linked to the product including all its variants.
    $priceRules = PriceruleHelper::getCatalogPriceRules($product);

    // Checks for extra product variants.
    if($this->item->nb_variants > 1) { 
      // Gets the product variants.
      $this->item->variants = $model->getVariantData($this->item->id);

      foreach($this->item->variants as $key => $variant) {
        // Adds some required attributes to the variant.
	$this->item->variants[$key]['pricerules'] = array();
	$this->item->variants[$key]['tax_rate'] = $this->item->tax_rate;
	$this->item->variants[$key]['type'] = $this->item->type;
	$this->item->variants[$key]['nb_variants'] = $this->item->nb_variants;
	$this->item->variants[$key]['shippable'] = $this->item->shippable;
	$this->item->variants[$key]['id'] = $this->item->id;
	$this->item->variants[$key]['slug'] = $this->item->slug;
	$this->item->variants[$key]['catid'] = $this->item->slug;

	// The product is linked to some price rules.
	if(!empty($priceRules)) {
	  // Loops through the price rules. 
	  foreach($priceRules as $priceRule) {
	    // Checks if the product variant is bound to the price rule.
	    if(in_array($variant['var_id'], $priceRule['var_ids'])) {
	      $this->item->variants[$key]['pricerules'][] = $priceRule;
	    }
	  }
	}

	// Gets the final price for this product variant.
	$catalogPrice = PriceruleHelper::getCatalogPrice($this->item->variants[$key], $this->shopSettings);
	$this->item->variants[$key]['final_price'] = $catalogPrice->final_price;

	if($this->shopSettings['tax_method'] == 'excl_tax') {
	  $this->item->variants[$key]['final_price_with_taxes'] = UtilityHelper::getPriceWithTaxes($this->item->variants[$key]['final_price'], $this->item->tax_rate);
	  $this->item->variants[$key]['final_price_with_taxes'] = UtilityHelper::roundNumber($this->item->variants[$key]['final_price_with_taxes'], $this->shopSettings['rounding_rule'], $this->shopSettings['digits_precision']);
	}

	// Computes the stock state.
	if($this->item->variants[$key]['stock_subtract']) {
	  $this->item->variants[$key]['stock_state'] = ShopHelper::getStockState($variant['min_stock_threshold'],
										 $variant['max_stock_threshold'],
										 $variant['stock'], $variant['allow_order']);
	}
	// Stock is infinite.
	else {
	  $this->item->variants[$key]['stock_state'] = 'maximum';
	}
      }
    }
    // The product has just one basic variant.
    else {
      $this->item->pricerules = $priceRules;
      // Gets the catalog price of the product.
      $catalogPrice = PriceruleHelper::getCatalogPrice($product, $this->shopSettings);

      // Sets the final price of the product.
      $this->item->final_price = $catalogPrice->final_price;

      // The global tax method is set to excluding taxes.
      if($this->shopSettings['tax_method'] == 'excl_tax') {
	$this->item->final_price_with_taxes = UtilityHelper::getPriceWithTaxes($this->item->final_price, $this->item->tax_rate);
	$this->item->final_price_with_taxes = UtilityHelper::roundNumber($this->item->final_price_with_taxes,
									 $this->shopSettings['rounding_rule'], 
									 $this->shopSettings['digits_precision']);
      }

      //Get the stock state.
      if($this->item->stock_subtract) {
	$this->item->stock_state = ShopHelper::getStockState($this->item->min_stock_threshold,
							     $this->item->max_stock_threshold,
							     $this->item->stock, $this->item->allow_order);
      }
      // Stock is infinite.
      else {
	$this->item->stock_state = 'maximum'; 
      }
    }

    //Set the first image of the image array as the default image.
    if(!empty($this->images)) { 
      $this->item->img_src = $this->images[0]->src;
      $this->item->img_width = $this->images[0]->width;
      $this->item->img_height = $this->images[0]->height;
      $this->item->img_alt = $this->images[0]->alt;
    }
    else { //Display a blank default image.
      $this->item->img_src = 'media/com_ketshop/images/missing-picture.jpg';
      $this->item->img_width = 200;
      $this->item->img_height = 200;
      $this->item->img_alt = JText::_('COM_KETSHOP_IMAGE_UNAVAILABLE');
    }

    //Increment the hits for this product.
    $model = $this->getModel();
    $model->hit();

    $this->nowDate = JFactory::getDate()->toSql();

    $this->setDocument();

    parent::display($tpl);
  }


  protected function setDocument() 
  {
    //Include css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_ketshop/css/ketshop.css');
  }
}

