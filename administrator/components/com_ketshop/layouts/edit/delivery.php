<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;
//var_dump($displayData->address);
?>

<div class="row-fluid">
  <div class="span4">
    <div class="form-vertical">
      <input type="hidden" name="delivery_id" value="<?php echo $displayData->id; ?>" />

      <div class="control-group">
	<div class="control-label">
	  <label id="delivery_status-lbl" for="delivery_status" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_STATUS_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_STATUS_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_STATUS_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	    <select id="delivery-status" name="delivery_status" class="inputbox chzn-done" size="1" style="display: none;">
	      <option value="completed" <?php echo ($displayData->status == 'completed' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_COMPLETED_STATUS'); ?></option>
	      <option value="pending" <?php echo ($displayData->status == 'pending' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_PENDING_STATUS'); ?></option>
	      <option value="cancelled" <?php echo ($displayData->status == 'cancelled' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_CANCELLED_STATUS'); ?></option>
	      <option value="other" <?php echo ($displayData->status == 'other' ? 'selected="selected"' : ''); ?>>
	      <?php echo JText::_('COM_KETSHOP_OPTION_OTHER_STATUS'); ?></option>
	    </select>
	</div>
      </div>

      <div class="control-group">
	<div class="control-label">
	  <label id="delivery_date-lbl" for="delivery_date" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_DATE_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_DATE_DESC'); ?>.">
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_DATE_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	    <?php echo JHTML::calendar($displayData->delivery_date,'delivery_date','delivery-date','%Y-%m-%d %H:%M:%S'); ?>
	</div>
      </div>

      <div class="control-group">
	<div class="control-label">
	  <label id="delivery_type-lbl" for="delivery_type" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_TYPE_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_TYPE_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_DELIVERY_TYPE_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <input type="text" name="delivery_type" id="delivery-type"
		 value="<?php echo JText::_('COM_KETSHOP_OPTION_'.strtoupper($displayData->delivery_type)); ?>"
	  class="readonly" size="25" disabled=""/>
	</div>
      </div>

      <div class="control-group">
	<div class="control-label">
	  <label id="shipping_cost-lbl" for="shipping_cost" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_SHIPPING_COST_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_SHIPPING_COST_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_SHIPPING_COST_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <input type="text" name="shipping_cost" id="shipping-cost"
		 value="<?php echo UtilityHelper::floatFormat($displayData->shipping_cost).' '.$displayData->currency; ?>"
	  class="readonly" size="25" disabled=""/>
	</div>
      </div>

      <div class="control-group">
	<div class="control-label">
	  <label id="final_shipping_cost-lbl" for="final_shipping_cost" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_FINAL_SHIPPING_COST_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_FINAL_SHIPPING_COST_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_FINAL_SHIPPING_COST_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <input type="text" name="final_shipping_cost" id="final-shipping-cost"
		 value="<?php echo UtilityHelper::floatFormat($displayData->final_shipping_cost).' '.$displayData->currency; ?>"
	  class="readonly" size="25" disabled=""/>
	</div>
      </div>

      <div class="control-group">
	<div class="control-label">
	  <label id="delivery_note-lbl" for="delivery_note" class="hasTooltip" title=""
	  data-original-title="<strong>
	  <?php echo JText::_('COM_KETSHOP_FIELD_NOTE_LABEL'); ?></strong><br />
	  <?php echo JText::_('COM_KETSHOP_FIELD_NOTE_DESC'); ?>">
	  <?php echo JText::_('COM_KETSHOP_FIELD_NOTE_LABEL'); ?></label>
	  </div>
	  <div class="controls">
	  <textarea name="delivery_note" id="delivery-note" rows="5" cols="10"><?php echo $displayData->note; ?></textarea>
	</div>
      </div>
    </div>
  </div>
  <div class="span6">
    <div class="form-vertical">
    <h3>
      <?php echo JText::_(($displayData->address['delivpnt_id']) ?  'COM_KETSHOP_DELIVERY_POINT_TITLE' : 'COM_KETSHOP_SHIPPING_ADDRESS_TITLE'); ?>
      </h3>

	  <table class="table">
	    <?php if($displayData->address['delivpnt_id']) : //Display the delivery point name. ?>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_NAME_LABEL'); ?>
	      </td><td>
	      <?php echo $displayData->address['delivpnt_name']; ?>
	      </td></tr>
	    <?php endif; ?>
	    <tr><td class="address-label">
	      <?php echo JText::_('COM_KETSHOP_FIELD_STREET_LABEL'); ?>
	    </td><td>
	    <?php echo $displayData->address['street']; ?>
	    </td></tr>
	    <tr><td class="address-label">
	      <?php echo JText::_('COM_KETSHOP_FIELD_POSTCODE_LABEL'); ?>
	    </td><td>
	    <?php echo $displayData->address['postcode']; ?>
	    </td></tr>
	    <tr><td class="address-label">
	      <?php echo JText::_('COM_KETSHOP_FIELD_CITY_LABEL'); ?>
	    </td><td>
	    <?php echo $displayData->address['city']; ?>
	    </td></tr>
	    <tr><td class="address-label">
	      <?php echo JText::_('COM_KETSHOP_FIELD_REGION_LABEL'); ?>
	    </td><td>
	    <?php echo JText::_($displayData->address['region_lang_var']); ?>
	    </td></tr>
	    <tr><td class="address-label">
	      <?php echo JText::_('COM_KETSHOP_FIELD_COUNTRY_LABEL'); ?>
	    </td><td>
	    <?php echo JText::_($displayData->address['country_lang_var']); ?>
	    </td></tr>
	    <tr><td class="address-label">
	      <?php echo JText::_('COM_KETSHOP_FIELD_PHONE_LABEL'); ?>
	    </td><td>
	    <?php echo JText::_($displayData->address['phone']); ?>
	    </td></tr>
	    <?php if($displayData->address['delivpnt_id']) : //Display the delivery point information ?>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_INFORMATION_LABEL'); ?>
	      </td><td>
	      <?php echo $displayData->address['information']; ?>
	      </td></tr>
	    <?php else : //or the customer address note. ?>
	      <tr><td class="address-label">
		<?php echo JText::_('COM_KETSHOP_FIELD_NOTE_LABEL'); ?>
	      </td><td>
	      <?php echo $displayData->address['note']; ?>
	      </td></tr>
	    <?php endif; ?>
	    </table>
    </div>
  </div>
</div>


