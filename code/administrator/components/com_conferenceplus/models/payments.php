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

require_once 'default.php';

class ConferenceplusModelPayments extends ConferenceplusModelDefault
{
	/**
	 * This method runs after an item has been gotten from the database in a read
	 * operation. You can modify it before it's returned to the MVC triad for
	 * further processing.
	 *
	 * @param   FOFTable  &$record  The table instance we fetched
	 *
	 * @return  void
	 */
	protected function onAfterGetItem(&$record)
	{
		parent::onAfterGetItem($record);

		$ticketId = $this->getTicketId();

		if (empty($ticketId))
		{
			return;
		}

		$ticketData = $this->getTicketData($ticketId);

		$record->ticketData     = $ticketData;

		$paymentProviders = $this->getPaymentProviders($ticketData);

		$record->paymentProviders = $paymentProviders;
	}

	/**
	 * This method runs before the $data is saved to the $table. Return false to
	 * stop saving.
	 *
	 * @param   array     &$data   The data to save
	 * @param   FOFTable  &$table  The table to save the data to
	 *
	 * @return  boolean  Return false to prevent saving, true to allow it
	 */
	protected function onBeforeSave(&$data, &$table)
	{
		if ( ! parent::onBeforeSave($data, $table))
		{
			return false;
		}

		if (FOFPlatform::getInstance()->isFrontend())
		{
			$processData = json_decode($table->processdata, true);
			$ticket = $processData['ticket']['ticket'];
			$data['name'] = $ticket['firstname'] . ' ' . $ticket['lastname'] . ', ' . $ticket['email'];
		}

		return true;
	}
	/**
	 * This method runs after the data is saved to the $table.
	 *
	 * @param   FOFTable  &$table  The table which was saved
	 *
	 * @return  boolean
	 */
	protected function onAfterSave(&$table)
	{
		if ( ! parent::onAfterSave($table))
		{
			return false;
		}

		$processData = json_decode($table->processdata, true);
		$ticketId = $processData['ticket']['ticket']['conferenceplus_ticket_id'];

		$ticketTable = FOFTable::getAnInstance('tickets');
		$ticketTable->load($ticketId);

		$ticketTable->payment_id = $table->conferenceplus_payment_id;
		$ticketTable->modified   = JFactory::getDate()->toSql();

		$ticketTableresult = $ticketTable->store();

		if ($this->_isNewRecord && $table->userid == 0)
		{
			$task = new Conferenceplus\Task\AfterPayment;

			if ( ! $task->create($table))
			{
				return false;
			}
		}

		return $ticketTable->store();
	}

	/**
	 * @param $ticketData
	 *
	 * @return array
	 */
	private function getPaymentProviders($ticketData)
	{
		$params = $this->prepareParams($ticketData);

		$dispatcher = JEventDispatcher::getInstance();

		JPluginHelper::importPlugin('payment');

		$results = $dispatcher->trigger('onPaymentGetForm', array($params));
		$rvalue  = [];

		foreach ($results as $result)
		{
			if (!empty($result))
			{
				$rvalue[] = $result;
			}
		}

		return $rvalue;
	}

	/**
	 * runs the callback from a payment provider
	 *
	 * @param $paymentMethod
	 *
	 * @return bool
	 */
	public function runCallback($paymentMethod)
	{
		$rawDataPost = JRequest::get('POST', 2);
		$rawDataGet = JRequest::get('GET', 2);

		$data = array_merge($rawDataGet, $rawDataPost);

		// Some plugins result in an empty Itemid being added to the request
		// data, screwing up the payment callback validation in some cases (e.g.PayPal).
		if (array_key_exists('Itemid', $data))
		{
			if (empty($data['Itemid']))
			{
				unset($data['Itemid']);
			}
		}

		$ticketId = array_key_exists('custom', $data) ? (int)$data['custom'] : -1;

		if ($ticketId < 1)
		{
			return false;
		}

		$ticketData = $this->getTicketData($ticketId);
		$params     = $this->prepareParams($ticketData);

		$dispatcher = JEventDispatcher::getInstance();

		JPluginHelper::importPlugin('payment');

		$results = $dispatcher->trigger('onPaymentCallback', array($paymentMethod, $data, $params));

		foreach($results as $result)
		{
			if ($result !== false)
			{
				$pluginData = json_decode($result, true);

				// save payment
				$saveData = [];
				$saveData['processkey']  = $pluginData['processkey'];
				$saveData['state']       = $pluginData['state'];
				$saveData['processdata'] = $this->prepareProcessData($pluginData, $ticketData, $data);

				$result = parent::save($saveData);

				$this->deleteTicketId();

				return $result;
			}
		}

		return false;
	}

	/**
	 * merge data and json decode
	 *
	 * @param $data
	 * @param $ticketData
	 *
	 * @return mixed|string
	 */
	public function prepareProcessData($data, $ticketData, $ppvData)
	{
		$mergedData = [];
		$mergedData['paymentprovider'] = $data;
		$mergedData['ticket']          = $ticketData;
		$mergedData['ipn']             = $ppvData;

		return json_encode($mergedData);
	}

	/**
	 * get a ticket id from the session
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function getTicketId()
	{
		return JFactory::getApplication()->getUserState('com_conferenceplus.ticketId', null);
	}

	/**
	 * delete ticket id save in session
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function deleteTicketId()
	{
		return JFactory::getApplication()->setUserState('com_conferenceplus.ticketId', null);
	}


	/**
	 * get ticketdata based on a ticketId
	 *
	 * @param $ticketId
	 *
	 * @return stdClass
	 */
	protected function getTicketData($ticketId = 0)
	{


		$ticketTable = FOFTable::getAnInstance('tickets');
		$ticketTable->load($ticketId);
		$tickettypeId = $ticketTable->tickettype_id;

		$tickettypeTable = FOFTable::getAnInstance('tickettypes');
		$tickettypeTable->load($tickettypeId);

		$ticketData             = new stdClass;
		$ticketData->ticket     = $ticketTable;
		$ticketData->tickettype = $tickettypeTable;

		return $ticketData;
	}

	/**
	 * prepare params array set with ticket data
	 *
	 * @param $ticketData
	 *
	 * @return array
	 */
	private function prepareParams($ticketData)
	{
		$params = [];

		$params['net_amount']  = $ticketData->tickettype->fee / 100;
		$params['item_name']   = $ticketData->tickettype->name;
		$params['item_number'] = 1;
		$params['currency']    = 'EUR';
		$params['custom']      = $ticketData->ticket->conferenceplus_ticket_id;
		$params['firstname']   = $ticketData->ticket->firstname;
		$params['lastname']    = $ticketData->ticket->lastname;
		$params['email']       = $ticketData->ticket->email;

		$Itemid  = Conferenceplus\Route\Helper::getItemid('');
		$success = JUri::root()
					. 'index.php?option=com_conferenceplus&view=payment&layout=confirm&t='
					. $ticketData->ticket->conferenceplus_ticket_id
					. '&Itemid=' . $Itemid;

		$params['success'] = $success;

		$cancel = JUri::root()
			. 'index.php?option=com_conferenceplus&view=payment&layout=cancel&t='
			. $ticketData->ticket->conferenceplus_ticket_id
			. '&Itemid=' . $Itemid;

		$params['cancel'] = $cancel;

		return $params;
	}
}
