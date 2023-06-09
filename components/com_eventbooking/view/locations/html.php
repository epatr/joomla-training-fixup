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

class EventbookingViewLocationsHtml extends RADViewHtml
{
	public function display()
	{
		if (!JFactory::getUser()->authorise('eventbooking.addlocation', 'com_eventbooking'))
		{
			$app = JFactory::getApplication();
			$app->enqueueMessage(JText::_('EB_NO_PERMISSION'), 'error');
			$app->redirect(JUri::root(), 403);

			return;
		}

		$this->findAndSetActiveMenuItem();

		$model            = $this->getModel();
		$this->items      = $model->getData();
		$this->pagination = $model->getPagination();

		$this->setLayout('default');

		parent::display();
	}
}
