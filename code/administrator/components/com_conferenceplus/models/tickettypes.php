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

class ConferenceplusModelTickettypes extends ConferenceplusModelDefault
{

	use Conferenceplus\Date\Helper;

	/**
	 * Class Constructor
	 *
	 * @param   array  $config  Configuration array
	 */
	public function __construct($config = array())
	{
		if (!isset($config['behaviors']) && FOFPlatform::getInstance()->isFrontend())
		{
			$config['behaviors'] = array('filters', 'access', 'enabled');
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * @return  void
	 *
	 * @note    Calling getState in this method will result in recursion.
	 * @since   12.2
	 */
	protected function populateState()
	{
		// Load the filters.
		$this->setState('filter.event_id',
			$this->getUserStateFromRequest('filter.tickettypes.event_id', 'eventname', ''));
	}
	/**
	 * Ajust the query
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  JDatabaseQuery
	 */
	public function buildQuery($overrideLimits = false)
	{
		$this->blacklistFilters('start');

		$query = parent::buildQuery($overrideLimits);

		// Get the current date
		$now = (new JDate())->toSql();

		$db    = $this->getDbo();

		$formName = $this->getState('form_name');

		if ($formName == 'form.default')
		{
			// Join events
			$query->join('INNER', '#__conferenceplus_events AS e ON e.conferenceplus_event_id = tt.event_id')
					->select('e.name as eventname')
					->where($db->qn('e.enabled') . ' = 1');

			// Filter
			$filterevent_id = $this->getState('filter.event_id');

			if ( ! empty($filterevent_id))
			{
				$query->where($db->qn('e.conferenceplus_event_id') . ' = ' . $db->q($filterevent_id));
			}

			if (FOFPlatform::getInstance()->isFrontend())
			{
				$query->where($db->qn('tt.start') . ' <= ' . $db->q($now))
						->where($db->qn('tt.end') . ' >= ' . $db->q($now));

			}
		}

		return $query;
	}


	/**
	 * check if a certain tickettype is valid
	 *
	 * @todo also check if time is set and if all tickets are sold
	 *
	 * @param $tickettypeId
	 *
	 * @return bool
	 */
	public function isValid($tickettypeId)
	{
		$tickettype = FOFTable::getAnInstance('tickettypes');

		$tickettype->load($tickettypeId);

		$now = JFactory::getDate()->toSql();

		$valid_from = $tickettype->sdate . ' ' . $tickettype->stime;
		$valid_to   = $tickettype->edate . ' ' . $tickettype->etime;

		$valid  = ($tickettype->enabled == 1 ) && ($valid_from <= $now) && ($valid_to >= $now);

		return $valid;
	}

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

		$this->prepareItemTimeFields($record);
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
		if (!parent::onBeforeSave($data, $table))
		{
			return false;
		}

		if ( ! $this->manageDateFields($data, ['s', 'e'], ['start', 'end']))
		{
			return false;
		}

		return true;
	}

}
