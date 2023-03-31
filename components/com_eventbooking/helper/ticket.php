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

class EventbookingHelperTicket
{
	/**
	 * Format ticket number
	 *
	 * @param string    $ticketPrefix
	 * @param int       $ticketNumber
	 * @param RADConfig $config
	 *
	 * @return string The formatted ticket number
	 */
	public static function formatTicketNumber($ticketPrefix, $ticketNumber, $config)
	{
		return $ticketPrefix . str_pad($ticketNumber, $config->ticket_number_length ? $config->ticket_number_length : 5, '0', STR_PAD_LEFT);
	}

	/**
	 * Generate Ticket PDFs
	 *
	 * @param EventbookingTableRegistrant $row
	 * @param RADConfig                   $config
	 */
	public static function generateTicketsPDF($row, $config)
	{
		if (EventbookingHelper::isMethodOverridden('EventbookingHelperOverrideHelperTicket', 'generateTicketsPDF'))
		{
			EventbookingHelperOverrideTicket::generateTicketsPDF($row, $config);

			return;
		}

		require_once JPATH_ROOT . "/components/com_eventbooking/tcpdf/tcpdf.php";
		require_once JPATH_ROOT . "/components/com_eventbooking/tcpdf/config/lang/eng.php";

		$pdf = new TCPDF($config->get('ticket_page_orientation', PDF_PAGE_ORIENTATION), PDF_UNIT, $config->get('ticket_page_format', PDF_PAGE_FORMAT), true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor(JFactory::getConfig()->get("sitename"));
		$pdf->SetTitle('Ticket');
		$pdf->SetSubject('Ticket');
		$pdf->SetKeywords('Ticket');
		$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(PDF_MARGIN_LEFT, 0, PDF_MARGIN_RIGHT);
		$pdf->setHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->setFooterMargin(PDF_MARGIN_FOOTER);
		//set auto page breaks
		$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		$font = empty($config->pdf_font) ? 'times' : $config->pdf_font;
		$pdf->SetFont($font, '', 8);

		EventbookingHelper::loadLanguage();

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);;
		$query->select('*')
			->from('#__eb_events')
			->where('id = ' . (int) $row->event_id);

		if ($fieldSuffix = EventbookingHelper::getFieldSuffix($row->language))
		{
			EventbookingHelperDatabase::getMultilingualFields($query, array('title', 'short_description', 'description'), $fieldSuffix);
		}


		$db->setQuery($query);
		$rowEvent = $db->loadObject();


		if (EventbookingHelper::isValidMessage($rowEvent->ticket_layout))
		{
			$ticketLayout = $rowEvent->ticket_layout;
		}
		else
		{
			$ticketLayout = $config->default_ticket_layout;
		}

		if ($rowEvent->ticket_bg_image)
		{
			$backgroundImage = $rowEvent->ticket_bg_image;
		}
		else
		{
			$backgroundImage = $config->get('default_ticket_bg_image');
		}

		if ($backgroundImage && file_exists(JPATH_ROOT . '/' . $backgroundImage))
		{
			$backgroundImagePath = JPATH_ROOT . '/' . $backgroundImage;

			if ($rowEvent->ticket_bg_left > 0)
			{
				$ticketBgLeft = $rowEvent->ticket_bg_left;
			}
			elseif ($config->default_ticket_bg_left > 0)
			{
				$ticketBgLeft = $config->default_ticket_bg_left;
			}
			else
			{
				$ticketBgLeft = 0;
			}

			if ($rowEvent->ticket_bg_top > 0)
			{
				$ticketBgTop = $rowEvent->ticket_bg_top;
			}
			elseif ($config->default_ticket_bg_top > 0)
			{
				$ticketBgTop = $config->default_ticket_bg_top;
			}
			else
			{
				$ticketBgTop = 0;
			}


			if ($rowEvent->ticket_bg_width > 0)
			{
				$ticketBgWidth = $rowEvent->ticket_bg_width;
			}
			elseif ($config->default_ticket_bg_width > 0)
			{
				$ticketBgWidth = $config->default_ticket_bg_width;
			}
			else
			{
				$ticketBgWidth = 0;
			}

			if ($rowEvent->ticket_bg_height > 0)
			{
				$ticketBgHeight = $rowEvent->ticket_bg_height;
			}
			elseif ($config->default_ticket_bg_height > 0)
			{
				$ticketBgHeight = $config->default_ticket_bg_height;
			}
			else
			{
				$ticketBgHeight = 0;
			}
		}
		else
		{
			$backgroundImagePath = '';
			$ticketBgTop         = $ticketBgLeft = '';
			$ticketBgWidth       = $ticketBgHeight = 0;
		}

		if ($rowEvent->collect_member_information === '')
		{
			$collectMemberInformation = $config->collect_member_information;
		}
		else
		{
			$collectMemberInformation = $rowEvent->collect_member_information;
		}

		if ($row->is_group_billing && $collectMemberInformation)
		{
			$query->clear()
				->select('*')
				->from('#__eb_registrants')
				->where('group_id = ' . $row->id);
			$db->setQuery($query);
			$rowMembers = $db->loadObjectList();

			foreach ($rowMembers as $rowMember)
			{
				$pdf->AddPage();

				if ($backgroundImagePath)
				{
					// Get current  break margin
					$breakMargin = $pdf->getBreakMargin();
					// get current auto-page-break mode
					$autoPageBreak = $pdf->getAutoPageBreak();
					// disable auto-page-break
					$pdf->SetAutoPageBreak(false, 0);
					// set background image
					$pdf->Image($backgroundImagePath, $ticketBgLeft, $ticketBgTop, $ticketBgWidth, $ticketBgHeight);
					// restore auto-page-break status
					$pdf->SetAutoPageBreak($autoPageBreak, $breakMargin);
					// set the starting point for the page content
					$pdf->setPageMark();
				}

				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);

				$form = new RADForm($rowFields);
				$data = EventbookingHelperRegistration::getRegistrantData($rowMember, $rowFields);
				$form->bind($data);
				$form->buildFieldsDependency();

				if (is_callable('EventbookingHelperOverrideRegistration::buildTags'))
				{
					$replaces = EventbookingHelperOverrideRegistration::buildTags($rowMember, $form, $rowEvent, $config);
				}
				elseif (is_callable('EventbookingHelperOverrideHelper::buildTags'))
				{
					$replaces = EventbookingHelperOverrideHelper::buildTags($rowMember, $form, $rowEvent, $config);
				}
				else
				{
					$replaces = EventbookingHelperRegistration::buildTags($rowMember, $form, $rowEvent, $config);
				}

				$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $rowMember->ticket_number, $config);
				$replaces['registration_date'] = JHtml::_('date', $row->register_date, $config->date_format);
				$replaces['event_title']       = $rowEvent->title;

				$output = $ticketLayout;

				foreach ($replaces as $key => $value)
				{
					$key    = strtoupper($key);
					$output = str_ireplace("[$key]", $value, $output);
				}

				$output = EventbookingHelperRegistration::processQRCODE($rowMember, $output, false);

				if (strpos($output, '[TICKET_NUMBER_QRCODE]') !== false)
				{
					EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
					$imgTag = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
					$output = str_ireplace("[TICKET_NUMBER_QRCODE]", $imgTag, $output);
				}

				$pdf->writeHTML($output, true, false, false, false, '');
			}
		}
		else
		{
			$pdf->AddPage();

			if ($backgroundImagePath)
			{
				// Get current  break margin
				$breakMargin = $pdf->getBreakMargin();
				// get current auto-page-break mode
				$autoPageBreak = $pdf->getAutoPageBreak();
				// disable auto-page-break
				$pdf->SetAutoPageBreak(false, 0);
				// set background image
				$pdf->Image($backgroundImagePath, $ticketBgLeft, $ticketBgTop, $ticketBgWidth, $ticketBgHeight);
				// restore auto-page-break status
				$pdf->SetAutoPageBreak($autoPageBreak, $breakMargin);
				// set the starting point for the page content
				$pdf->setPageMark();
			}

			if ($config->multiple_booking)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->id, 4, $row->language);
			}
			elseif ($row->is_group_billing)
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 1, $row->language);
			}
			else
			{
				$rowFields = EventbookingHelperRegistration::getFormFields($row->event_id, 0, $row->language);
			}

			$form = new RADForm($rowFields);
			$data = EventbookingHelperRegistration::getRegistrantData($row, $rowFields);
			$form->bind($data);
			$form->buildFieldsDependency();

			if (is_callable('EventbookingHelperOverrideRegistration::buildTags'))
			{
				$replaces = EventbookingHelperOverrideRegistration::buildTags($row, $form, $rowEvent, $config);
			}
			elseif (is_callable('EventbookingHelperOverrideHelper::buildTags'))
			{
				$replaces = EventbookingHelperOverrideHelper::buildTags($row, $form, $rowEvent, $config);
			}
			else
			{
				$replaces = EventbookingHelperRegistration::buildTags($row, $form, $rowEvent, $config);
			}

			$replaces['ticket_number']     = self::formatTicketNumber($rowEvent->ticket_prefix, $row->ticket_number, $config);
			$replaces['registration_date'] = JHtml::_('date', $row->register_date, $config->date_format);
			$replaces['event_title']       = $rowEvent->title;

			foreach ($replaces as $key => $value)
			{
				$key          = strtoupper($key);
				$ticketLayout = str_ireplace("[$key]", $value, $ticketLayout);
			}

			$ticketLayout = EventbookingHelperRegistration::processQRCODE($row, $ticketLayout, false);

			if (strpos($ticketLayout, '[TICKET_NUMBER_QRCODE]') !== false)
			{
				EventbookingHelperRegistration::generateTicketNumberQrcode($replaces['ticket_number']);
				$imgTag       = '<img src="media/com_eventbooking/qrcodes/' . $replaces['ticket_number'] . '.png" border="0" alt="QRCODE" />';
				$ticketLayout = str_ireplace("[TICKET_NUMBER_QRCODE]", $imgTag, $ticketLayout);
			}

			$pdf->writeHTML($ticketLayout, true, false, false, false, '');
		}

		$filePath = JPATH_ROOT . '/media/com_eventbooking/tickets/ticket_' . str_pad($row->id, 5, '0', STR_PAD_LEFT) . '.pdf';

		$pdf->Output($filePath, 'F');
	}

	/**
	 * Generate TICKET_QRCODE
	 *
	 * @param $row
	 */
	public static function generateTicketQrcode($row)
	{
		EventbookingHelperRegistration::generateTicketQrcode($row);
	}

	/**
	 * Process QRCODE for ticket. Support [QRCODE] and [TICKET_QRCODE] tag
	 *
	 * @param $row
	 * @param $output
	 *
	 * @return mixed
	 */
	protected static function processQRCODE($row, $output)
	{
		return EventbookingHelperRegistration::processQRCODE($row, $output, false);
	}
}
