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
<div class="row">
	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_GENERAL_SETTINGS_LOCATION_GENERAL'); ?>

			<div class="panel-body">

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'location.provider', $this->config->get('location.provider'), array(
								array('value' => 'foursquare', 'text' => 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_FOURSQUARE'),
								array('value' => 'places', 'text' => 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_GOOGLE_PLACES'),
								array('value' => 'maps', 'text' => 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_GOOGLE_MAPS')
							), '', array('data-location-places')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LOCATION_SETTINGS_PROXIMITY_SEARCH_UNIT'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.selectlist', 'general.location.proximity.unit', $this->config->get('general.location.proximity.unit'), array(
								array('value' => 'mile', 'text' => 'COM_EASYSOCIAL_LOCATION_SETTINGS_IN_MILES'),
								array('value' => 'km', 'text' => 'COM_EASYSOCIAL_LOCATION_SETTINGS_IN_KILOMETERS')
							), ''); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LOCATION_SETTINGS_LANGUAGE_CODE'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'general.location.language', $this->config->get('general.location.language'), '', array('class' => 'input-short text-center')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-6">
		<div class="panel">
			<?php echo $this->html('panel.heading', 'COM_ES_LOCATION_SETTINGS_SERVICE_PROVIDER_GOOGLE_MAPS_REQUIRED'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_ES_LOCATION_SETTINGS_GOOGLE_MAPS_USE_SECURE_API'); ?>

					<div class="col-md-7">
						<?php echo $this->html('form.toggler', 'location.maps.secure.api', $this->config->get('location.maps.secure.api'),'', array('data-toggle-gmaps-secure')); ?>
					</div>
				</div>

				<div class="form-group <?php echo $this->config->get('location.maps.secure.api') ? 't-hidden' : ''; ?>" data-google-maps-normal>
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_GOOGLE_MAPS_API'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'location.maps.api', $this->config->get('location.maps.api')); ?>
					</div>
				</div>

				<div class="form-group <?php echo $this->config->get('location.maps.secure.api') ? '' : 't-hidden'; ?>" data-google-maps-secure>
					<?php echo $this->html('panel.label', 'COM_ES_LOCATION_SETTINGS_GOOGLE_MAPS_API_BROWSER'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'location.maps.secure.browser', $this->config->get('location.maps.secure.browser')); ?>
					</div>
				</div>
				<div class="form-group <?php echo $this->config->get('location.maps.secure.api') ? '' : 't-hidden'; ?>" data-google-maps-secure>
					<?php echo $this->html('panel.label', 'COM_ES_LOCATION_SETTINGS_GOOGLE_MAPS_API_SERVER'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'location.maps.secure.server', $this->config->get('location.maps.secure.server')); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="panel t-hidden" data-location-service="foursquare">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_FOURSQUARE'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_FOURSQUARE_CLIENT_ID'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'location.foursquare.clientid', $this->config->get('location.foursquare.clientid')); ?>
					</div>
				</div>

				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_FOURSQUARE_CLIENT_SECRET'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'location.foursquare.clientsecret', $this->config->get('location.foursquare.clientsecret')); ?>
					</div>
				</div>
			</div>
		</div>

		<div class="panel t-hidden" data-location-service="places">
			<?php echo $this->html('panel.heading', 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_GOOGLE_PLACES'); ?>

			<div class="panel-body">
				<div class="form-group">
					<?php echo $this->html('panel.label', 'COM_EASYSOCIAL_LOCATION_SETTINGS_SERVICE_PROVIDER_GOOGLE_PLACES_API'); ?>

					<div class="col-md-7">
						<?php echo $this->html('grid.inputbox', 'location.places.api', $this->config->get('location.places.api')); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>