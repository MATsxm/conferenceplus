<?php
/**
 * conferenceplus
 * @author Robert Deutz <rdeutz@googlemail.com>
 * @package conferenceplus
 **/

// No direct access
defined('_JEXEC') or die;

$displayData 	= new stdClass;
$form 			= $this->form;
$errors 		= $form->getErrors();
$params 		= JComponentHelper::getParams('COM_CONFERENCEPLUS');
$keys 			= array_keys($form->getFieldset());

$headerlevel    = $params->get('headerlevel', 2);

$title = JText::_('COM_CONFERENCEPLUS_PREBUY_TITLE');
$doc = JFactory::getDocument()->setTitle($title);

$baseLayoutPath = JPATH_ROOT . '/media/conferenceplus/layouts';

$Itemid = Conferenceplus\Route\Helper::getItemid('');

$currency = explode('|', $params->get('currency'))[0];

if (0)
{
	echo "#<div style='text-align:left;font_size:1.2em;'><pre>";
	//print_r($this->item->eventParams);
	print_r($keys);
	echo "</pre></div>#";
}

?>

<!-- ************************** START: conferenceplus ************************** -->
<div class="conferenceplus item">

	<?php if (count($errors) != 0)
	{
		echo "<h$headerlevel>" . JText::_('COM_CONFERENCEPLUS_ERROR') . "</h$headerlevel>";
		$displayData->pretext = JText::_('COM_CONFERENCEPLUS_ERRORSUBMIT');
		$displayData->errors = $errors;
		echo JLayoutHelper::render('form.error', $displayData, $baseLayoutPath);
		unset($displayData);
	}
	else
	{
		echo "<h$headerlevel>" . $title . "</h$headerlevel>";
		echo JText::_('COM_CONFERENCEPLUS_PREBUY_TICKET');
	}

	?>

	<div class="selectedticket">
		<?php echo JText::_('COM_CONFERENCEPLUS_PREBUY_TICKET_YOURSELECTION');?>
		<dl>
			<dt><?php echo JText::_('COM_CONFERENCEPLUS_TICKETTYPENAME');?></dt>
			<dd><?php echo $this->item->ticketType->name;?></dd>
			<dt><?php echo JText::_('COM_CONFERENCEPLUS_TICKETTYPEDESCRIPTION');?></dt>
			<dd><?php echo $this->item->ticketType->description;?></dd>
			<dt><?php echo JText::_('COM_CONFERENCEPLUS_TICKETTYPEFEE');?></dt>
			<dd><?php echo $currency; ?> <?php echo number_format($this->item->ticketType->fee/100, 0, ',', '');?></dd>
		</dl>
	</div>

	<?php echo JText::_('COM_CONFERENCEPLUS_PREBUY_TICKET_PRETEXT');?>
	
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
		<div class="row">

			<form action="index.php?option=com_conferenceplus&view=ticket&task=save&layout=buy&Itemid=<?php echo $Itemid;?>" method="post" id="adminForm" role="form">

				<?php $fields = array('firstname', 'lastname', 'email', 'ask4gender', 'ask4tshirtsize', 'ask4food', 'ask4food0'); ?>

				<?php foreach($fields AS $f) : ?>

					<?php if (in_array($f, $keys)) : ?>
						<?php echo JText::_(Conferenceplus\Helper::checkLangTag('COM_CONFERENCEPLUS_' . strtoupper($f) . 'PREINFO', '', 'COM_CONFERENCEPLUS_EMPTY', '')); ?>
						<?php $displayData->label = $form->getLabel($f); ?>
						<?php $displayData->input = $form->getInput($f); ?>
						<?php echo JLayoutHelper::render('form.formelement', $displayData, $baseLayoutPath); ?>
						<?php echo JText::_(Conferenceplus\Helper::checkLangTag('COM_CONFERENCEPLUS_' . strtoupper($f) . 'POSTINFO', '', 'COM_CONFERENCEPLUS_EMPTY', '')); ?>
					<?php endif; ?>

				<?php endforeach; ?>

				<div class="form-actions">
					<input type="submit" value="<?php echo JText::_('COM_CONFERENCEPLUS_SEND');?>" class="btn btn-primary" />
				</div>

				<input type="hidden" name="option" value="com_conferenceplus" />
				<input type="hidden" name="view" value="ticket" />
				<input type="hidden" name="task" value="save" />
				<input type="hidden" name="layout" value="buy" />
				<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
			</form>	

		</div>
	</div>		

</div>
<!-- ************************** END: conferenceplus ************************** -->