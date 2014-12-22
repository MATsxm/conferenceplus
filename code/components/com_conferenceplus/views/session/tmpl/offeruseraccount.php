<?php
/**
 * Conferenceplus
 *
 * @package    Conferenceplus
 * @author     Robert Deutz <rdeutz@googlemail.com>
 *
 * @copyright  2014 JandBeyond
 * @license    GNU General Public License version 2 or later
 */

// No direct access
defined('_JEXEC') or die;

$Itemid = Conferenceplus\Route\Helper::getItemid();
$uri = JUri::base() . "index.php?option=com_conferenceplus&view=callback&layout=register&Itemid=$Itemid";
?>

<!-- ************************** START: conferenceplus ************************** -->
<div class="conferenceplus item">

	<?php echo JText::_('COM_CONFERENCEPLUS_SUBMIT_SESSION_THANKYOU_OFFER2CREATEAACCOUNT'); ?>

	<a class="btn btn-success" href="<?php echo $uri; ?>"><?php echo JText::_('COM_CONFERENCEPLUS_CREATEACCOUNT'); ?></a>

</div>
<!-- ************************** END: conferenceplus ************************** -->