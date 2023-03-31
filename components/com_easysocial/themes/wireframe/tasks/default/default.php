<?php
/**
* @package      EasySocial
* @copyright    Copyright (C) 2010 - 2017 Stack Ideas Sdn Bhd. All rights reserved.
* @license      GNU/GPL, see LICENSE.php
* EasySocial is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/
defined('_JEXEC') or die('Unauthorized Access');
?>
<div class="es-container" data-es-container data-tasks-milestones data-id="<?php echo $cluster->id; ?>" data-type="<?php echo $cluster->getType();?>">
	<div class="es-sidebar" data-sidebar>
		<?php if ($cluster->canCreateMilestones()) { ?>
		<a href="<?php echo ESR::apps(array('layout' => 'canvas', 'uid' => $cluster->getAlias(), 'type' => $cluster->getType(), 'id' => $app->getAlias(), 'customView' => 'form')); ?>" class="btn btn-es-primary btn-block t-lg-mb--xl">
			<?php echo JText::_('APP_EVENT_TASKS_NEW_MILESTONE'); ?>
		</a>
		<?php } ?>

		<div class="es-side-widget">
			<?php echo $this->html('widget.title', 'COM_ES_STATISTICS'); ?>

			<div class="es-side-widget__bd">
				<ul class="o-nav o-nav--stacked">
					<li class="o-nav__item t-lg-mb--sm">
						<span class="o-nav__link t-text--muted">
							<i class="es-side-widget__icon fa fa-bullseye t-lg-mr--md"></i> <b><?php echo $counters['milestones'];?></b> <?php echo JText::_('COM_ES_MILESTONES'); ?>
						</span>
					</li>
					<li class="o-nav__item t-lg-mb--sm">
						<span class="o-nav__link t-text--muted">
							<i class="es-side-widget__icon fa fa-tasks t-lg-mr--md"></i> <b><?php echo $counters['tasks'];?></b> <?php echo JText::_('COM_ES_TASKS'); ?>
						</span>
					</li>
				</ul>
			</div>
		</div>
	</div>

	<div class="es-content">
		<div class="app-contents-wrap app-tasks">
			<div class="milestone-browser app-contents<?php echo !$milestones ? ' is-empty' : ''; ?>">
				<?php echo $this->html('html.loading'); ?>
				<?php echo $this->html('html.emptyBlock', 'APP_EVENT_TASKS_EMPTY_MILESTONES', 'fa-tasks'); ?>

				<?php if ($milestones) { ?>
				<div class="milestone-list">
					<?php foreach ($milestones as $milestone) { ?>
						<?php echo $this->loadTemplate('site/tasks/default/item', array('milestone' => $milestone, 'cluster' => $cluster, 'app' => $app, 'params' => $params)); ?>
					<?php } ?>
				</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>