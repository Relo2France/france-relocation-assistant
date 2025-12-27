/**
 * Schengen Tracker Frontend JavaScript
 *
 * @package R2F_Schengen_Tracker
 * @since   1.0.0
 */

(function($) {
	'use strict';

	var R2FSchengen = {
		apiUrl: '',
		nonce: '',

		/**
		 * Initialize the tracker.
		 */
		init: function() {
			if (typeof r2fSchengen === 'undefined') {
				console.error('Schengen Tracker: Configuration not found');
				return;
			}

			this.apiUrl = r2fSchengen.apiUrl;
			this.nonce = r2fSchengen.nonce;

			this.bindEvents();
			this.loadTrips();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			var self = this;

			// Add trip form
			$('#r2f-schengen-add-trip').on('submit', function(e) {
				e.preventDefault();
				self.addTrip($(this));
			});

			// Settings form
			$('#r2f-schengen-settings-form').on('submit', function(e) {
				e.preventDefault();
				self.saveSettings($(this));
			});

			// Delete trip (delegated)
			$('#r2f-schengen-trips').on('click', '.r2f-schengen-delete-trip', function(e) {
				e.preventDefault();
				var tripId = $(this).data('id');
				if (confirm('Delete this trip?')) {
					self.deleteTrip(tripId);
				}
			});
		},

		/**
		 * Make an API request.
		 *
		 * @param {string} endpoint - API endpoint.
		 * @param {string} method - HTTP method.
		 * @param {object} data - Request data.
		 * @return {Promise}
		 */
		apiRequest: function(endpoint, method, data) {
			method = method || 'GET';

			var options = {
				url: this.apiUrl + endpoint,
				method: method,
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', this.nonce);
				}.bind(this),
				contentType: 'application/json'
			};

			if (data && method !== 'GET') {
				options.data = JSON.stringify(data);
			}

			return $.ajax(options);
		},

		/**
		 * Load and display trips.
		 */
		loadTrips: function() {
			var self = this;
			var $container = $('#r2f-schengen-trips');

			$container.html('<p class="r2f-schengen-loading">Loading trips...</p>');

			this.apiRequest('trips').done(function(response) {
				if (response.success && response.data && response.data.length > 0) {
					self.renderTrips(response.data);
				} else if (response.length > 0) {
					self.renderTrips(response);
				} else {
					$container.html('<p class="r2f-schengen-empty">No trips recorded yet. Add your first trip above!</p>');
				}
			}).fail(function(xhr) {
				console.error('Failed to load trips:', xhr);
				$container.html('<p class="r2f-schengen-loading">Could not load trips. Please refresh the page.</p>');
			});
		},

		/**
		 * Render trips list.
		 *
		 * @param {array} trips - Array of trip objects.
		 */
		renderTrips: function(trips) {
			var $container = $('#r2f-schengen-trips');
			var html = '';

			trips.forEach(function(trip) {
				var startDate = new Date(trip.start_date);
				var endDate = new Date(trip.end_date);
				var days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

				html += '<div class="r2f-schengen-trip" data-id="' + trip.id + '">';
				html += '<div class="r2f-schengen-trip-info">';
				html += '<div class="r2f-schengen-trip-country">' + escapeHtml(trip.country) + '</div>';
				html += '<div class="r2f-schengen-trip-dates">' + formatDate(trip.start_date) + ' - ' + formatDate(trip.end_date) + '</div>';
				html += '<div class="r2f-schengen-trip-days">' + days + ' day' + (days !== 1 ? 's' : '') + '</div>';
				if (trip.notes) {
					html += '<div class="r2f-schengen-trip-notes">' + escapeHtml(trip.notes) + '</div>';
				}
				html += '</div>';
				html += '<div class="r2f-schengen-trip-actions">';
				html += '<button type="button" class="r2f-schengen-button r2f-schengen-button-small r2f-schengen-button-danger r2f-schengen-delete-trip" data-id="' + trip.id + '">Delete</button>';
				html += '</div>';
				html += '</div>';
			});

			$container.html(html);
		},

		/**
		 * Add a new trip.
		 *
		 * @param {jQuery} $form - The form jQuery object.
		 */
		addTrip: function($form) {
			var self = this;
			var $button = $form.find('button[type="submit"]');
			var originalText = $button.text();

			var data = {
				country: $form.find('[name="country"]').val(),
				start_date: $form.find('[name="start_date"]').val(),
				end_date: $form.find('[name="end_date"]').val(),
				notes: $form.find('[name="notes"]').val()
			};

			// Validate
			if (!data.country || !data.start_date || !data.end_date) {
				alert('Please fill in all required fields.');
				return;
			}

			if (data.start_date > data.end_date) {
				alert('End date must be after start date.');
				return;
			}

			$button.prop('disabled', true).text('Adding...');

			this.apiRequest('trips', 'POST', data).done(function(response) {
				$form[0].reset();
				self.loadTrips();
				// Reload page to update summary
				location.reload();
			}).fail(function(xhr) {
				console.error('Failed to add trip:', xhr);
				var message = 'Failed to add trip.';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					message = xhr.responseJSON.message;
				}
				alert(message);
			}).always(function() {
				$button.prop('disabled', false).text(originalText);
			});
		},

		/**
		 * Delete a trip.
		 *
		 * @param {number} tripId - The trip ID.
		 */
		deleteTrip: function(tripId) {
			var self = this;

			this.apiRequest('trips/' + tripId, 'DELETE').done(function() {
				// Reload page to update summary
				location.reload();
			}).fail(function(xhr) {
				console.error('Failed to delete trip:', xhr);
				alert('Failed to delete trip. Please try again.');
			});
		},

		/**
		 * Save settings.
		 *
		 * @param {jQuery} $form - The form jQuery object.
		 */
		saveSettings: function($form) {
			var $button = $form.find('button[type="submit"]');
			var originalText = $button.text();

			var data = {
				email_alerts: $form.find('[name="email_alerts"]').is(':checked')
			};

			$button.prop('disabled', true).text('Saving...');

			this.apiRequest('settings', 'POST', data).done(function() {
				alert('Settings saved!');
			}).fail(function(xhr) {
				console.error('Failed to save settings:', xhr);
				alert('Failed to save settings. Please try again.');
			}).always(function() {
				$button.prop('disabled', false).text(originalText);
			});
		}
	};

	/**
	 * Escape HTML entities.
	 *
	 * @param {string} text - Text to escape.
	 * @return {string} Escaped text.
	 */
	function escapeHtml(text) {
		if (!text) return '';
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Format a date string.
	 *
	 * @param {string} dateStr - Date string (YYYY-MM-DD).
	 * @return {string} Formatted date.
	 */
	function formatDate(dateStr) {
		if (!dateStr) return '';
		var date = new Date(dateStr + 'T00:00:00');
		return date.toLocaleDateString(undefined, {
			year: 'numeric',
			month: 'short',
			day: 'numeric'
		});
	}

	// Initialize when DOM is ready
	$(document).ready(function() {
		if ($('.r2f-schengen-dashboard').length) {
			R2FSchengen.init();
		}
	});

})(jQuery);
