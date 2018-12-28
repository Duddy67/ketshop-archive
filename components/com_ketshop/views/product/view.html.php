<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT_SITE.'/helpers/route.php';
require_once JPATH_COMPONENT_SITE.'/helpers/shop.php';
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

    //Get the user object and the current url, (needed in the product edit layout).
    $this->user = JFactory::getUser();
    $this->uri = JUri::getInstance();

    //Get the attributes of the product.
    $this->item->attributes = $model->getAttributeData($this->item->id);

    //Needed for the product properties layouts.
    $this->item->attributes_location = $this->item->weight_location = $this->item->dimensions_location = 'page';

    //Get the global settings of the shop.
    $this->shopSettings = ShopHelper::getShopSettings();
    //Add the settings to the item (for the layout).
    $this->item->shop_settings = $this->shopSettings;

    //Convert item object into associative array by just casttype it.
    $product = (array)$this->item;
    //Get the possible price rules linked to the product.
    $product['pricerules'] = PriceruleHelper::getCatalogPriceRules($product);
    //Get the catalog price of the product.
    $catalogPrice = PriceruleHelper::getCatalogPrice($product, $this->shopSettings);

    $this->item->final_price = $catalogPrice->final_price;
    $this->item->pricerules = $catalogPrice->pricerules;

    if($this->shopSettings['tax_method'] == 'excl_tax') {
      $this->item->final_price_with_taxes = UtilityHelper::getPriceWithTaxes($this->item->final_price, $this->item->tax_rate);
      $this->item->final_price_with_taxes = UtilityHelper::roundNumber($this->item->final_price_with_taxes,
								       $this->shopSettings['rounding_rule'], 
								       $this->shopSettings['digits_precision']);
    }

    //Get possible product variants.
    $this->item->variants = $model->getVariantData($this->item->id);

    //Check for product variants.
    if(!empty($this->item->variants)) { 
      foreach($this->item->variants as $key => $variant) {
	//Check for variants with a price different from the one of the main product. If a
	//price rule is applied on the main product price, the same price rule must be
	//applied on the product variant price as well.
	if($variant['sale_price'] > 0 && $variant['base_price'] > 0 && $this->item->sale_price != $this->item->final_price) {
	  $product = array('id' => $this->item->id, 
			   'base_price' => $variant['base_price'], 
			   'sale_price' => $variant['sale_price'], 
			   'tax_rate' => $this->item->tax_rate, 
			   'type' => $this->item->type);
	  //Compute the catalog price for this product variant.
	  $catalogPrice = PriceruleHelper::getCatalogPrice($product, $this->shopSettings);
	  $this->item->variants[$key]['final_price'] = $catalogPrice->final_price;
	  $this->item->variants[$key]['pricerules'] = $catalogPrice->pricerules;
	}
	else {
	  $this->item->variants[$key]['final_price'] = $variant['sale_price'];
	}

	if($this->shopSettings['tax_method'] == 'excl_tax') {
	  $this->item->variants[$key]['final_price_with_taxes'] = UtilityHelper::getPriceWithTaxes($this->item->variants[$key]['final_price'],
												  $this->item->tax_rate);
	  $this->item->variants[$key]['final_price_with_taxes'] = UtilityHelper::roundNumber($this->item->variants[$key]['final_price_with_taxes'],
											    $this->shopSettings['rounding_rule'],
											    $this->shopSettings['digits_precision']);
	}
      }
    }

    //Get the stock state.
    if($this->item->stock_subtract) {
      $this->item->stock_state = ShopHelper::getStockState($this->item->min_stock_threshold,
							   $this->item->max_stock_threshold,
							   $this->item->stock, $this->item->allow_order);

      if(!empty($this->item->variants)) { //
        foreach($this->item->variants as $key => $variant) {
	  $this->item->variants[$key]['stock_state'] = ShopHelper::getStockState($this->item->min_stock_threshold,
										$this->item->max_stock_threshold,
										$variant['stock'], $this->item->allow_order);
	}
      }
    }
    else { //If product is not subtracted from stock, we assume that the stock is always full.
      if(empty($this->item->variants)) { //Regular product.
	$this->item->stock_state = 'maximum'; 
      }
      else { //Product with variants.      
        foreach($this->item->variants as $key => $variant) {
	  $this->item->variants[$key]['stock_state'] = 'maximum';
	}
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

