<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

//Grab the user session.
$session = JFactory::getSession();

//Get the current location.
$location = ShopHelper::getLocation();
//This session variable is used by the addToCart controller function 
//to redirect the customer after a task is done.
//It's also used by the cart view for the link which brings back the customer to
//his previous location.
$session->set('location', $location, 'ketshop');
?>
<script type="text/javascript">
var ketshop = {
  clearSearch: function() {
    document.getElementById('filter_search').value = '';
    ketshop.submitForm();
  },

  clearFilters: function() {
    var filters = document.querySelectorAll('select[id^="filter_attrib_"]');
    for(var i = 0; i < filters.length; i++) {
      document.getElementById(filters[i].id).value = '';
    }

    ketshop.submitForm();
  },

  submitForm: function() {
    var action = document.getElementById('siteForm').action;
    //Set an anchor on the form.
    document.getElementById('siteForm').action = action+'#siteForm';
    document.getElementById('siteForm').submit();
  }
};
</script>

<div class="blog<?php echo $this->pageclass_sfx;?>">
  <?php if($this->params->get('show_page_heading')) : ?>
	  <h1>
	    <?php echo $this->escape($this->params->get('page_heading')); ?>
	  </h1>
  <?php endif; ?>
  <?php if($this->params->get('show_tag_title', 1)) : ?>
	  <h2 class="category-title">
	      <?php echo JHtml::_('content.prepare', $this->tag->title, ''); ?>
	  </h2>
  <?php endif; ?>

  <?php if($this->params->get('show_tag_description') || $this->params->def('show_tag_image')) : ?>
	  <div class="category-desc">
		  <?php if($this->params->get('show_tag_image') && $this->tag->images->get('image_intro')) : ?>
			  <img src="<?php echo $this->tag->images->get('image_intro'); ?>"/>
		  <?php endif; ?>
		  <?php if($this->params->get('show_tag_description') && $this->tag->description) : ?>
			  <?php echo JHtml::_('content.prepare', $this->tag->description, ''); ?>
		  <?php endif; ?>
		  <div class="clr"></div>
	  </div>
  <?php endif; ?>

  <form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="siteForm" id="siteForm">

    <?php echo JLayoutHelper::render('tag.filters', $this); ?>

    <?php if(empty($this->lead_items) && empty($this->link_items) && empty($this->intro_items)) : ?>
      <?php if($this->params->get('show_no_tagged_products')) : ?>
	      <p><?php echo JText::_('COM_KETSHOP_NO_PRODUCTS'); ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <?php $leadingcount = 0; ?>
    <?php if(!empty($this->lead_items)) : ?>
	    <div class="items-leading clearfix">
	  <?php foreach($this->lead_items as &$item) : ?>
		  <div class="leading-<?php echo $leadingcount; ?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>"
			  itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
			  <?php
			  $this->item = & $item;
			  echo $this->loadTemplate('item');
			  ?>
		  </div>
		  <?php $leadingcount++; ?>
	  <?php endforeach; ?>
	    </div><!-- end items-leading -->
    <?php endif; ?>

    <?php
    $introcount = (count($this->intro_items));
    $counter = 0;
    ?>

    <?php if(!empty($this->intro_items)) : ?>
      <?php foreach($this->intro_items as $key => &$item) : ?>
	  <?php $rowcount = ((int) $key % (int) $this->columns) + 1; ?>
	  <?php if($rowcount == 1) : ?>
		  <?php $row = $counter / $this->columns; ?>
		  <div class="items-row cols-<?php echo (int) $this->columns; ?> <?php echo 'row-'.$row; ?> row-fluid clearfix">
	  <?php endif; ?>
	  <div class="span<?php echo round((12 / $this->columns)); ?>">
		  <div class="item column-<?php echo $rowcount; ?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>"
		      itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
		      <?php
		      $this->item = & $item;
		      echo $this->loadTemplate('item');
		      ?>
		  </div>
		  <!-- end item -->
		  <?php $counter++; ?>
	  </div><!-- end span -->
	  <?php if(($rowcount == $this->columns) or ($counter == $introcount)) : ?>
		  </div><!-- end row -->
	  <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if(!empty($this->link_items)) : ?>
	    <div class="items-more">
	      <?php echo $this->loadTemplate('links'); ?>
	    </div>
    <?php endif; ?>

    <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="pagination">
	    <?php echo $this->pagination->getListFooter(); ?>

	    <?php if ($this->params->def('show_pagination_results', 1) || $this->params->def('show_pagination_pages', 1)) : ?>
	      <div class="ketshop-results">
		  <?php if ($this->params->def('show_pagination_results', 1)) : ?>
		      <p class="counter pull-left small">
			<?php echo $this->pagination->getResultsCounter(); ?>
		      </p>
		  <?php endif; ?>
		  <?php if ($this->params->def('show_pagination_pages', 1)) : ?>
		      <p class="counter pull-right small">
			<?php echo $this->pagination->getPagesCounter(); ?>
		      </p>
		  <?php endif; ?>
	      </div>
	    <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if(!empty($this->children) && $this->tagMaxLevel != 0) : ?>
	    <div class="cat-children">
	      <h3><?php echo JTEXT::_('COM_KETSHOP_SUBTAGS_TITLE'); ?></h3>
	      <?php echo $this->loadTemplate('children'); ?>
	    </div>
    <?php endif; ?>

    <input type="hidden" name="limitstart" value="" />
    <input type="hidden" id="token" name="<?php echo JSession::getFormToken(); ?>" value="1" />
    <input type="hidden" name="task" value="" />
  </form>
</div><!-- blog -->

<?php

if($this->params->get('filter_field') == 'name') {
  //Loads the JQuery autocomplete file.
  JHtml::_('script', 'media/jui/js/jquery.autocomplete.min.js');
  //Loads our js script.
  $doc = JFactory::getDocument();
  $doc->addScript(JURI::base().'components/com_ketshop/js/autocomplete.js');
}

