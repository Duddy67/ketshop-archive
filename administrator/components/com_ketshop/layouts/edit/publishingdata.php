<?php
/**
 * @package KetShop
 * @copyright Copyright (c) 2016 - 2019 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

$app = JFactory::getApplication();
$form = $displayData->getForm();

$fields = $displayData->get('fields') ?: array(
	'publish_up',
	'publish_down',
	'new_until', //Add the new_until field to the publishing panel.
	array('created', 'created_time'),
	array('created_by', 'created_user_id'),
	'created_by_alias',
	array('modified', 'modified_time'),
	array('modified_by', 'modified_user_id'),
	'version',
	'hits',
	'id'
);

$hiddenFields = $displayData->get('hidden_fields') ?: array();

foreach ($fields as $field)
{
	$field = is_array($field) ? $field : array($field);
	foreach ($field as $f)
	{
		if ($form->getField($f))
		{
			if (in_array($f, $hiddenFields))
			{
				$form->setFieldAttribute($f, 'type', 'hidden');
			}

			echo $form->renderField($f);
			break;
		}
	}
}
