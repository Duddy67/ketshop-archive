<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
?>
<script type="text/javascript">
var ketshop = {
  clearSearch: function() {
    document.getElementById('filter_search').value = '';
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

<div class="list<?php echo $this->pageclass_sfx;?>">
  <?php if ($this->params->get('show_page_heading')) : ?>
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

  <?php if($this->params->get('filter_field') != 'hide' || $this->params->get('show_pagination_limit') || $this->params->get('filter_ordering'))
: ?>
    <div class="ketshop-toolbar clearfix">
    <?php
            //Gets the filter fields.
	    $fieldset = $this->filterForm->getFieldset('filter');

	    //Loops through the fields.
	    foreach($fieldset as $field) {
	      $filterName = $field->getAttribute('name');

	      if($filterName == 'filter_search' && $this->params->get('filter_field') != 'hide') { ?>
		<div class="btn-group input-append span6">
	      <?php
		    $hint = JText::_('COM_KETSHOP_'.$this->params->get('filter_field').'_FILTER_LABEL');
		    $this->filterForm->setFieldAttribute($filterName, 'hint', $hint); 
		    //Displays only the input tag (without the div around).
		    echo $this->filterForm->getInput($filterName, null, $this->state->get('list.'.$filterName));
		    //Adds the search and clear buttons.  ?>
		<button type="submit" onclick="ketshop.submitForm();" class="btn hasTooltip"
			title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
		    <i class="icon-search"></i></button>

		<button type="button" onclick="ketshop.clearSearch()" class="btn hasTooltip js-stools-btn-clear"
			title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
		    <?php echo JText::_('JSEARCH_FILTER_CLEAR');?></button>
		</div>
      <?php	}
	      elseif(($filterName == 'filter_ordering' && $this->params->get('filter_ordering')) ||
		     ($filterName == 'limit' && $this->params->get('show_pagination_limit'))) {
		//Sets the field value to the currently selected value.
		$field->setValue($this->state->get('list.'.$filterName));
		echo $field->renderField(array('hiddenLabel' => true, 'class' => 'span3 ketshop-filters'));
	      }
	    }
     ?>
     </div>
    <?php endif; ?>

    <?php if(empty($this->items)) : ?>
	    <?php if($this->params->get('show_no_products', 1)) : ?>
	    <p><?php echo JText::_('COM_KETSHOP_NO_PRODUCTS'); ?></p>
	    <?php endif; ?>
    <?php else : ?>
      <?php echo $this->loadTemplate('products'); ?>
    <?php endif; ?>

    <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="pagination">
	    <?php echo $this->pagination->getListFooter(); ?>

	    <?php if ($this->params->def('show_pagination_results', 1) || $this->params->def('show_pagination_pages', 1)) : ?>
	      <div class="songbook-results">
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
</div><!-- list -->

<?php

if($this->params->get('filter_field') == 'name') {
  //Loads the JQuery autocomplete file.
  JHtml::_('script', 'media/jui/js/jquery.autocomplete.min.js');
  //Loads our js script.
  $doc = JFactory::getDocument();
  $doc->addScript(JURI::base().'components/com_ketshop/js/autocomplete.js');
}

