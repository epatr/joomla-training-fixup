<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2018 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */
// no direct access
defined('_JEXEC') or die;

class EventbookingViewPasswordHtml extends RADViewHtml
{
	public function display()
	{
		$this->setLayout('default');
		$this->return          = $this->input->getBase64('return', '');
		$this->eventId         = $this->input->getInt('event_id', 0);
		$this->eventUrl        = JRoute::_(EventbookingHelperRoute::getEventRoute($this->eventId, 0, $this->Itemid), false);
		$config                = EventbookingHelper::getConfig();
		$this->bootstrapHelper = EventbookingHelperBootstrap::getInstance();

		parent::display();
	}
}
