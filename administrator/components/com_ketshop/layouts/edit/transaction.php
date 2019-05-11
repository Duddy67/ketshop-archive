<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;
?>

<div class="row-fluid">
  <div class="span4">
    <div class="form-vertical">

      <div class="control-group">
	<div class="control-label">
	  <label id="payment_mode-lbl" for="payment_mode" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_MODE_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_MODE_DESC'); ?>.">
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_MODE_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <input type="text" name="payment_mode" id="payment_mode" value="<?php echo $displayData->payment_mode; ?>"
	  class="readonly" size="25" disabled=""/>
	</div>
      </div>

      <div class="control-group">
	<div class="control-label">
	  <label id="payment_amount-lbl" for="payment_amount" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_AMOUNT_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_AMOUNT_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_AMOUNT_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <input type="text" name="amount" id="amount"
		 value="<?php echo UtilityHelper::floatFormat($displayData->amount).' '.$displayData->currency; ?>"
	  class="readonly" size="25" disabled=""/>
	</div>
      </div>

    </div>
  </div>
  <div class="span4">
    <div class="form-vertical">

      <div class="control-group">
	<div class="control-label">
	  <label id="transaction_details-lbl" for="transaction_details" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_TRANSACTION_DETAIL_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_TRANSACTION_DETAIL_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_TRANSACTION_DETAIL_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <textarea name="transaction_details" id="transaction-details"
		  class="readonly" readonly="true" rows="5" cols="10"><?php echo $displayData->detail; ?>
	   </textarea>
	</div>
      </div>

    </div>
  </div>
</div>


