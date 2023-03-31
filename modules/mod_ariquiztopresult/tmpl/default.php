<?php
/*
 * ARI Quiz Top Result Joomla! module
 *
 * @package		ARI Quiz Top Result Joomla! module
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2010 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

if (!empty($results) || !empty($emptyMessage)):
?>
	<table class="aq-mod-quiztopresult">
		<?php if ($label): ?>
		<caption><?php echo $label; ?></caption>
		<?php endif; ?>
		<tr>
			<?php
				if (!$hideQuizName):
			?>
			<th class="aq-quiz"><?php echo JText::_('MOD_ARIQUIZTOPRESULT_LABEL_QUIZ'); ?></th>
			<?php
				endif; 
			?>
			<th class="aq-user"><?php echo JText::_('MOD_ARIQUIZTOPRESULT_LABEL_USER'); ?></th>
			<th class="aq-point"><?php echo JText::_('MOD_ARIQUIZTOPRESULT_LABEL_POINT'); ?></th>
		</tr>
<?php
		if (!empty($results)):
			$guest = JText::_('MOD_ARIQUIZTOPRESULT_LABEL_GUEST');
			foreach ($results as $result):
				$name = AriUtils::getParam($result, $nameField, '');
				if (empty($name)) 
					$name = $guest;
?>
		<tr<?php if ($userId > 0 && $result->UserId == $userId): ?> class="current"<?php endif; ?>>
			<?php
				if (!$hideQuizName):
			?>
			<td class="aq-quiz"><?php if ($showQuizLink): ?><a href="<?php echo JRoute::_('index.php?option=com_ariquiz&view=quiz&quizId=' . $result->QuizId . ($addMenuItemToLink ? '&Itemid=' . $linkItemId : '')); ?>"><?php echo $result->QuizName; ?></a><?php else: echo $result->QuizName;endif; ?></td>
			<?php
				endif; 
			?>
			<td class="aq-user"><?php echo $name; ?></td>
			<td class="aq-point"><?php echo $measureUnit == 'point' ? $result->UserScore : sprintf('%.2f %%', $result->PercentScore); ?></td>
		</tr>	
<?php
			endforeach;
		else:
?>
		<tr>
			<td colspan="<?php echo $colSpan; ?>">
				<?php echo $emptyMessage; ?>
			</td>
		</tr>
<?php
		endif;
?>
	</table>
<?php
endif;
?>