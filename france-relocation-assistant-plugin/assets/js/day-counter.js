/**
 * France Relocation Assistant - Enhanced Day Counter
 * Tracks France/US/Other presence for tax residency determination
 * 
 * Key rule: 183+ days in France = French tax resident
 */

(function($) {
    'use strict';
    
    const DayCounter = {
        trips: [],
        currentYear: new Date().getFullYear(),
        selectedYear: new Date().getFullYear(),
        
        init: function() {
            this.loadTrips();
            this.bindEvents();
            this.renderCalendar();
            this.updateStats();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Toggle day counter
            $('#fra-toggle-day-counter').on('click', function() {
                $('#fra-day-counter-enhanced').slideToggle();
                $(this).toggleClass('active');
            });
            
            // Close day counter
            $('#fra-close-day-counter').on('click', function() {
                $('#fra-day-counter-enhanced').slideUp();
                $('#fra-toggle-day-counter').removeClass('active');
            });
            
            // Year navigation
            $('#fra-prev-year').on('click', function() {
                self.selectedYear--;
                self.renderCalendar();
                self.updateStats();
            });
            
            $('#fra-next-year').on('click', function() {
                self.selectedYear++;
                self.renderCalendar();
                self.updateStats();
            });
            
            // Add trip form
            $('#fra-add-trip-btn').on('click', function() {
                $('#fra-add-trip-form').slideToggle();
            });
            
            $('#fra-save-trip').on('click', function() {
                self.saveTrip();
            });
            
            $('#fra-cancel-trip').on('click', function() {
                $('#fra-add-trip-form').slideUp();
                self.clearTripForm();
            });
            
            // Clear all data
            $('#fra-clear-all-data').on('click', function() {
                if (confirm('Are you sure you want to delete all trip data? This cannot be undone.')) {
                    self.trips = [];
                    self.saveTrips();
                    self.renderCalendar();
                    self.updateStats();
                    self.renderTripList();
                }
            });
            
            // Export data
            $('#fra-export-data').on('click', function() {
                self.exportData();
            });
            
            // Import data
            $('#fra-import-data').on('change', function(e) {
                self.importData(e);
            });
        },
        
        loadTrips: function() {
            try {
                const saved = localStorage.getItem('fra_trips');
                if (saved) {
                    this.trips = JSON.parse(saved);
                }
            } catch (e) {
                this.trips = [];
            }
        },
        
        saveTrips: function() {
            try {
                localStorage.setItem('fra_trips', JSON.stringify(this.trips));
            } catch (e) {
                console.error('Could not save trips');
            }
        },
        
        saveTrip: function() {
            const startDate = $('#fra-trip-start').val();
            const endDate = $('#fra-trip-end').val();
            const location = $('#fra-trip-location').val();
            const notes = $('#fra-trip-notes').val();
            
            if (!startDate || !endDate) {
                alert('Please enter both start and end dates.');
                return;
            }
            
            if (new Date(endDate) < new Date(startDate)) {
                alert('End date must be after start date.');
                return;
            }
            
            const trip = {
                id: Date.now(),
                startDate: startDate,
                endDate: endDate,
                location: location,
                notes: notes
            };
            
            this.trips.push(trip);
            this.trips.sort((a, b) => new Date(a.startDate) - new Date(b.startDate));
            this.saveTrips();
            
            this.renderCalendar();
            this.updateStats();
            this.renderTripList();
            
            $('#fra-add-trip-form').slideUp();
            this.clearTripForm();
        },
        
        deleteTrip: function(id) {
            this.trips = this.trips.filter(t => t.id !== id);
            this.saveTrips();
            this.renderCalendar();
            this.updateStats();
            this.renderTripList();
        },
        
        clearTripForm: function() {
            $('#fra-trip-start').val('');
            $('#fra-trip-end').val('');
            $('#fra-trip-location').val('france');
            $('#fra-trip-notes').val('');
        },
        
        getDaysInMonth: function(year, month) {
            return new Date(year, month + 1, 0).getDate();
        },
        
        getLocationForDate: function(dateStr) {
            const date = new Date(dateStr);
            for (const trip of this.trips) {
                const start = new Date(trip.startDate);
                const end = new Date(trip.endDate);
                if (date >= start && date <= end) {
                    return trip.location;
                }
            }
            return null;
        },
        
        getTripForDate: function(dateStr) {
            const date = new Date(dateStr);
            for (const trip of this.trips) {
                const start = new Date(trip.startDate);
                const end = new Date(trip.endDate);
                if (date >= start && date <= end) {
                    return trip;
                }
            }
            return null;
        },
        
        calculateDaysForYear: function(year) {
            const result = { france: 0, us: 0, other: 0, untracked: 0 };
            const startOfYear = new Date(year, 0, 1);
            const endOfYear = new Date(year, 11, 31);
            const today = new Date();
            const endDate = today < endOfYear ? today : endOfYear;
            
            let currentDate = new Date(startOfYear);
            while (currentDate <= endDate) {
                const dateStr = currentDate.toISOString().split('T')[0];
                const location = this.getLocationForDate(dateStr);
                
                if (location === 'france') result.france++;
                else if (location === 'us') result.us++;
                else if (location === 'other') result.other++;
                else result.untracked++;
                
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            return result;
        },
        
        // Calculate days in France in the rolling 183-day window
        calculateRolling183: function(targetDate) {
            const target = new Date(targetDate);
            const startWindow = new Date(target);
            startWindow.setDate(startWindow.getDate() - 182); // 183-day window including target
            
            let franceDays = 0;
            let currentDate = new Date(startWindow);
            
            while (currentDate <= target) {
                const dateStr = currentDate.toISOString().split('T')[0];
                if (this.getLocationForDate(dateStr) === 'france') {
                    franceDays++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            return franceDays;
        },
        
        // Calculate how many more days can stay in France
        calculateRemainingDays: function() {
            const today = new Date();
            const franceDaysInWindow = this.calculateRolling183(today);
            return Math.max(0, 182 - franceDaysInWindow); // 183 would trigger residency
        },
        
        // Find the next date when user can return to France
        calculateNextEntryDate: function() {
            if (this.calculateRemainingDays() > 0) {
                return null; // Can enter now
            }
            
            // Find when the oldest France day in the window drops off
            const today = new Date();
            let checkDate = new Date(today);
            
            for (let i = 0; i < 365; i++) {
                checkDate.setDate(checkDate.getDate() + 1);
                const daysInWindow = this.calculateRolling183(checkDate);
                if (daysInWindow < 183) {
                    return checkDate;
                }
            }
            
            return null;
        },
        
        updateStats: function() {
            const yearStats = this.calculateDaysForYear(this.selectedYear);
            const remaining = this.calculateRemainingDays();
            const rolling = this.calculateRolling183(new Date());
            
            $('#fra-stat-france').text(yearStats.france);
            $('#fra-stat-us').text(yearStats.us);
            $('#fra-stat-other').text(yearStats.other);
            $('#fra-stat-untracked').text(yearStats.untracked);
            
            // Rolling window stats
            $('#fra-rolling-used').text(rolling);
            $('#fra-rolling-remaining').text(183 - rolling);
            
            // Progress bar
            const percentage = Math.min(100, (rolling / 183) * 100);
            $('#fra-progress-bar').css('width', percentage + '%');
            
            if (rolling >= 183) {
                $('#fra-progress-bar').addClass('fra-danger');
                $('#fra-status-message').html('<strong>⚠️ Warning:</strong> You have reached 183 days in France. You may be considered a French tax resident.');
                $('#fra-status-message').removeClass('fra-status-ok fra-status-warning').addClass('fra-status-danger');
            } else if (remaining < 30) {
                $('#fra-progress-bar').removeClass('fra-danger').addClass('fra-warning');
                $('#fra-status-message').html('<strong>⚠️ Caution:</strong> Only ' + remaining + ' days remaining before reaching 183-day threshold.');
                $('#fra-status-message').removeClass('fra-status-ok fra-status-danger').addClass('fra-status-warning');
            } else {
                $('#fra-progress-bar').removeClass('fra-danger fra-warning');
                $('#fra-status-message').html('<strong>✓ OK:</strong> ' + remaining + ' days remaining in your 183-day allowance.');
                $('#fra-status-message').removeClass('fra-status-warning fra-status-danger').addClass('fra-status-ok');
            }
            
            // Year label
            $('#fra-current-year').text(this.selectedYear);
        },
        
        renderCalendar: function() {
            const self = this;
            const $calendar = $('#fra-calendar-grid');
            $calendar.empty();
            
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const today = new Date();
            
            months.forEach(function(monthName, monthIndex) {
                const $month = $('<div class="fra-calendar-month">');
                $month.append('<div class="fra-month-name">' + monthName + '</div>');
                
                const $days = $('<div class="fra-month-days">');
                const daysInMonth = self.getDaysInMonth(self.selectedYear, monthIndex);
                
                // Add day of week headers
                const dayHeaders = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
                dayHeaders.forEach(function(d) {
                    $days.append('<div class="fra-day-header">' + d + '</div>');
                });
                
                // Add empty cells for first day offset
                const firstDay = new Date(self.selectedYear, monthIndex, 1).getDay();
                for (let i = 0; i < firstDay; i++) {
                    $days.append('<div class="fra-day fra-day-empty"></div>');
                }
                
                // Add days
                for (let day = 1; day <= daysInMonth; day++) {
                    const dateStr = self.selectedYear + '-' + 
                        String(monthIndex + 1).padStart(2, '0') + '-' + 
                        String(day).padStart(2, '0');
                    
                    const location = self.getLocationForDate(dateStr);
                    const trip = self.getTripForDate(dateStr);
                    const isToday = dateStr === today.toISOString().split('T')[0];
                    const isFuture = new Date(dateStr) > today;
                    
                    let classes = 'fra-day';
                    if (location === 'france') classes += ' fra-day-france';
                    else if (location === 'us') classes += ' fra-day-us';
                    else if (location === 'other') classes += ' fra-day-other';
                    if (isToday) classes += ' fra-day-today';
                    if (isFuture) classes += ' fra-day-future';
                    
                    const $day = $('<div class="' + classes + '" data-date="' + dateStr + '">' + day + '</div>');
                    
                    if (trip) {
                        $day.attr('title', trip.notes || (location === 'france' ? 'France' : location === 'us' ? 'United States' : 'Other'));
                    }
                    
                    $days.append($day);
                }
                
                $month.append($days);
                $calendar.append($month);
            });
            
            this.renderTripList();
        },
        
        renderTripList: function() {
            const self = this;
            const $list = $('#fra-trip-list');
            $list.empty();
            
            // Filter trips for selected year
            const yearTrips = this.trips.filter(function(trip) {
                const tripYear = new Date(trip.startDate).getFullYear();
                return tripYear === self.selectedYear;
            });
            
            if (yearTrips.length === 0) {
                $list.append('<p class="fra-no-trips">No trips recorded for ' + this.selectedYear + '</p>');
                return;
            }
            
            yearTrips.forEach(function(trip) {
                const start = new Date(trip.startDate);
                const end = new Date(trip.endDate);
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                
                const locationLabels = { france: '🇫🇷 France', us: '🇺🇸 United States', other: '🌍 Other' };
                const locationClass = 'fra-trip-' + trip.location;
                
                const $trip = $('<div class="fra-trip-item ' + locationClass + '">' +
                    '<div class="fra-trip-dates">' +
                        '<strong>' + self.formatDate(trip.startDate) + '</strong> → <strong>' + self.formatDate(trip.endDate) + '</strong>' +
                        '<span class="fra-trip-duration">' + days + ' days</span>' +
                    '</div>' +
                    '<div class="fra-trip-location">' + locationLabels[trip.location] + '</div>' +
                    (trip.notes ? '<div class="fra-trip-notes">' + trip.notes + '</div>' : '') +
                    '<button class="fra-delete-trip" data-id="' + trip.id + '">×</button>' +
                '</div>');
                
                $trip.find('.fra-delete-trip').on('click', function() {
                    if (confirm('Delete this trip?')) {
                        self.deleteTrip(trip.id);
                    }
                });
                
                $list.append($trip);
            });
        },
        
        formatDate: function(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        },
        
        exportData: function() {
            const data = {
                version: '1.0',
                exportDate: new Date().toISOString(),
                trips: this.trips
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'france-day-counter-' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
            URL.revokeObjectURL(url);
        },
        
        importData: function(e) {
            const self = this;
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(event) {
                try {
                    const data = JSON.parse(event.target.result);
                    if (data.trips && Array.isArray(data.trips)) {
                        if (confirm('Import ' + data.trips.length + ' trips? This will replace your current data.')) {
                            self.trips = data.trips;
                            self.saveTrips();
                            self.renderCalendar();
                            self.updateStats();
                            alert('Successfully imported ' + data.trips.length + ' trips.');
                        }
                    }
                } catch (err) {
                    alert('Error importing file: ' + err.message);
                }
            };
            reader.readAsText(file);
            e.target.value = ''; // Reset input
        }
    };
    
    // Initialize when DOM ready
    $(document).ready(function() {
        if ($('#fra-day-counter-enhanced').length) {
            DayCounter.init();
        }
    });
    
})(jQuery);
