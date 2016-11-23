<?php
/**
 * @package KetShop
 * @copyright Copyright (c)2012 - 2016 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;
?>

<div class="row-fluid">
  <div class="span4">
    <div class="form-vertical">
      <input type="hidden" name="transaction_id" value="<?php echo $displayData->id; ?>" />

      <div class="control-group">
	<div class="control-label">
	  <label id="transaction_details-lbl" for="transaction_details" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_STATUS_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_STATUS_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_PAYMENT_STATUS_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	    <select id="transaction-status" name="transaction_status" class="inputbox chzn-done" size="1" style="display: none;">
	      <option value="completed" <?php echo ($displayData->status == 'completed' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_COMPLETED_STATUS'); ?></option>
	      <option value="pending" <?php echo ($displayData->status == 'pending' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_PENDING_STATUS'); ?></option>
	      <option value="error" <?php echo ($displayData->status == 'error' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_ERROR_STATUS'); ?></option>
	      <option value="unfinished" <?php echo ($displayData->status == 'unfinished' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_UNFINISHED_STATUS'); ?></option>
	      <option value="other" <?php echo ($displayData->status == 'other' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_OTHER_STATUS'); ?></option>
	    </select>
	</div>
      </div>

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
		 value="<?php echo UtilityHelper::formatNumber($displayData->amount).' '.$displayData->currency; ?>"
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
	  <?php echo JText::_('COM_KETSHOP_FIELD_TRANSACTION_DETAILS_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_TRANSACTION_DETAILS_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_TRANSACTION_DETAILS_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <textarea name="transaction_details" id="transaction-details"
		  class="readonly" readonly="true" rows="5" cols="10"><?php echo $displayData->details; ?>
	   </textarea>
	</div>
      </div>

      <div class="control-group">
	<div class="control-label">
	  <label id="transaction_note-lbl" for="transaction_note" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_NOTE_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_NOTE_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_NOTE_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <textarea name="transaction_note" id="transaction-note" rows="5" cols="10"><?php echo $displayData->note; ?></textarea>
	</div>
      </div>
    </div>
  </div>
</div>


