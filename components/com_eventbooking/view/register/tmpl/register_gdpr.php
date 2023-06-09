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

/**
 * Layout variables
 * -----------------
 * @var   string $controlGroupClass
 * @var   int $articleId
 */

if ($this->config->show_privacy_policy_checkbox)
{
    if ($this->config->privacy_policy_url)
    {
        $link = $this->config->privacy_policy_url;
    }
	elseif ($this->config->privacy_policy_article_id > 0)
	{
		$privacyArticleId = $this->config->privacy_policy_article_id;

		if (JLanguageMultilang::isEnabled())
		{
			$associations = JLanguageAssociations::getAssociations('com_content', '#__content', 'com_content.item', $privacyArticleId);
			$langCode     = JFactory::getLanguage()->getTag();
			if (isset($associations[$langCode]))
			{
				$privacyArticle = $associations[$langCode];
			}
		}

		if (!isset($privacyArticle))
		{
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('id, catid')
				->from('#__content')
				->where('id = ' . (int) $privacyArticleId);
			$db->setQuery($query);
			$privacyArticle = $db->loadObject();
		}

		JLoader::register('ContentHelperRoute', JPATH_ROOT . '/components/com_content/helpers/route.php');

		$link = JRoute::_(ContentHelperRoute::getArticleRoute($privacyArticle->id, $privacyArticle->catid).'&tmpl=component&format=html');
	}
	else
	{
		$link = '';
	}
	?>
    <div class="<?php echo $controlGroupClass ?>">
        <div class="<?php echo $controlLabelClass; ?>">
            <input type="checkbox" name="agree_privacy_policy" value="1" class="validate[required]" data-errormessage="<?php echo JText::_('EB_AGREE_PRIVACY_POLICY_ERROR');?>" />
			<?php
			if ($link)
			{
				if (!$this->config->privacy_policy_url)
				{
					EventbookingHelperJquery::colorbox('eb-colorbox-privacy-policy');
					$linkClass = ' class="eb-colorbox-privacy-policy"';
				}
				else
				{
					$linkClass = ' target="_blank"';
				}
			?>
                <a href="<?php echo $link; ?>"<?php echo $linkClass; ?>><?php echo JText::_('EB_PRIVACY_POLICY');?></a>
			<?php
			}
			else
			{
				echo JText::_('EB_PRIVACY_POLICY');
			}
			?>
        </div>
        <div class="<?php echo $controlsClass; ?>">
			<?php
			$agreePrivacyPolicyMessage = JText::_('EB_AGREE_PRIVACY_POLICY_MESSAGE');

			if (strlen($agreePrivacyPolicyMessage))
			{
			?>
                <div class="eb-privacy-policy-message alert alert-info"><?php echo $agreePrivacyPolicyMessage;?></div>
			<?php
			}
			?>
        </div>
    </div>
	<?php
}

if ($this->config->show_subscribe_newsletter_checkbox)
{
?>
    <div class="<?php echo $controlGroupClass ?> eb-subscribe-to-newsletter-container">
        <label class="checkbox" for="subscribe_to_newsletter">
            <input type="checkbox" id="subscribe_to_newsletter" name="subscribe_to_newsletter" value="1" />
            <?php echo JText::_('EB_JOIN_NEWSLETTER'); ?>
        </label>
    </div>
<?php
}