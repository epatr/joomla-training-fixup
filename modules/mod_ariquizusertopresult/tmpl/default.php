<?php
/*
 * ARI Quiz User Top Result Joomla! module
 *
 * @package		ARI Quiz User Top Result Joomla! module
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2010 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

(defined('_JEXEC') && defined('ARI_FRAMEWORK_LOADED')) or die;

if (!empty($results) || !empty($emptyMessage)):
?>
	<table class="aq-mod-quizusertopresult">
		<?php if ($label): ?>
		<caption><?php echo $label; ?></caption>
		<?php endif; ?>
		<tr>
			<th class="aq-quiz"><?php echo JText::_('MOD_ARIQUIZUSERTOPRESULT_LABEL_QUIZ'); ?></th>
			<th class="aq-point"><?php echo JText::_('MOD_ARIQUIZUSERTOPRESULT_LABEL_POINT'); ?></th>
		</tr>
<?php
		if (!empty($results)):
			foreach ($results as $result):
?>
		<tr>
			<td class="aq-quiz"><?php if ($showQuizLink): ?><a href="<?php echo JRoute::_('index.php?option=com_ariquiz&view=quiz&quizId=' . $result->QuizId . ($addMenuItemToLink ? '&Itemid=' . $linkItemId : '')); ?>"><?php echo $result->QuizName; ?></a><?php else: echo $result->QuizName;endif; ?></td>
			<td class="aq-point"><?php echo $measureUnit == 'point' ? $result->UserScore : sprintf('%.2f %%', $result->PercentScore); ?></td>
		</tr>	
<?php
			endforeach;
		else:
?>
		<tr>
			<td colspan="2">
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