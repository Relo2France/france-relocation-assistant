/**
 * France Relocation Assistant - Dashboard JavaScript
 *
 * @package FranceRelocationAssistant
 * @version 3.6.0
 */

(function() {
    'use strict';

    const FRADashboard = {
        nonce: '',
        apiBase: '/wp-json/fra/v1',

        /**
         * Initialize dashboard
         */
        init: function() {
            // Get nonce from data attribute
            const dashboard = document.querySelector('.fra-dashboard, .fra-onboarding');
            if (dashboard) {
                this.nonce = dashboard.dataset.nonce || '';
            }

            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Task checkboxes
            document.querySelectorAll('.fra-task-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', (e) => this.handleTaskToggle(e));
            });

            // Phase toggle (expand/collapse)
            document.querySelectorAll('.fra-phase-header').forEach(header => {
                header.addEventListener('click', (e) => this.handlePhaseToggle(e));
            });

            // Onboarding form
            const onboardingForm = document.getElementById('fra-onboarding-form');
            if (onboardingForm) {
                onboardingForm.addEventListener('submit', (e) => this.handleOnboardingSubmit(e));
            }

            // Counter buttons
            document.querySelectorAll('.fra-counter-btn').forEach(btn => {
                btn.addEventListener('click', (e) => this.handleCounter(e));
            });

            // Edit profile button
            document.querySelectorAll('[data-action="edit-profile"]').forEach(btn => {
                btn.addEventListener('click', (e) => this.handleEditProfile(e));
            });
        },

        /**
         * Handle task checkbox toggle
         */
        handleTaskToggle: function(e) {
            const checkbox = e.target;
            const taskId = checkbox.dataset.taskId;
            const complete = checkbox.checked;
            const taskEl = checkbox.closest('.fra-task');

            // Optimistic UI update
            taskEl.classList.toggle('fra-task-done', complete);

            // Send to API
            this.toggleTask(taskId, complete)
                .then(response => {
                    if (response.success) {
                        this.updateProgress(response.completion);
                        this.updatePhaseCount(taskEl.closest('.fra-phase'));
                    } else {
                        // Revert on error
                        checkbox.checked = !complete;
                        taskEl.classList.toggle('fra-task-done', !complete);
                    }
                })
                .catch(() => {
                    // Revert on error
                    checkbox.checked = !complete;
                    taskEl.classList.toggle('fra-task-done', !complete);
                });
        },

        /**
         * Toggle task via REST API
         */
        toggleTask: function(taskId, complete) {
            return fetch(this.apiBase + '/task', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.getWpNonce()
                },
                body: JSON.stringify({
                    task_id: taskId,
                    complete: complete
                }),
                credentials: 'same-origin'
            }).then(res => res.json());
        },

        /**
         * Update progress ring
         */
        updateProgress: function(percentage) {
            const ring = document.querySelector('.fra-progress-fill');
            const number = document.querySelector('.fra-progress-number');

            if (ring) {
                const circumference = 326.73;
                const offset = circumference - (circumference * percentage / 100);
                ring.style.strokeDashoffset = offset;
            }

            if (number) {
                number.textContent = percentage;
            }
        },

        /**
         * Update phase task count
         */
        updatePhaseCount: function(phaseEl) {
            if (!phaseEl) return;

            const tasks = phaseEl.querySelectorAll('.fra-task');
            const completed = phaseEl.querySelectorAll('.fra-task-done');
            const countEl = phaseEl.querySelector('.fra-phase-count');

            if (countEl) {
                countEl.textContent = completed.length + '/' + tasks.length;
            }

            // Update phase completion state
            const isComplete = completed.length === tasks.length;
            phaseEl.classList.toggle('fra-phase-completed', isComplete);

            // Update indicator
            const indicator = phaseEl.querySelector('.fra-phase-indicator');
            if (indicator) {
                if (isComplete) {
                    indicator.innerHTML = '<span class="fra-phase-check">✓</span>';
                } else {
                    const index = Array.from(phaseEl.parentNode.children).indexOf(phaseEl);
                    indicator.innerHTML = '<span class="fra-phase-number">' + (index + 1) + '</span>';
                }
            }
        },

        /**
         * Handle phase expand/collapse
         */
        handlePhaseToggle: function(e) {
            // Don't toggle if clicking a link
            if (e.target.tagName === 'A') return;

            const phase = e.currentTarget.closest('.fra-phase');
            const tasks = phase.querySelector('.fra-phase-tasks');
            const toggle = phase.querySelector('.fra-phase-toggle');

            const isExpanded = tasks.classList.contains('fra-phase-tasks-expanded');

            // Close other phases
            document.querySelectorAll('.fra-phase-tasks-expanded').forEach(el => {
                if (el !== tasks) {
                    el.classList.remove('fra-phase-tasks-expanded');
                    const otherToggle = el.closest('.fra-phase').querySelector('.fra-phase-toggle');
                    if (otherToggle) otherToggle.textContent = '▼';
                }
            });

            // Toggle current
            tasks.classList.toggle('fra-phase-tasks-expanded', !isExpanded);
            if (toggle) {
                toggle.textContent = isExpanded ? '▼' : '▲';
            }
        },

        /**
         * Handle onboarding form submit
         */
        handleOnboardingSubmit: function(e) {
            e.preventDefault();

            const form = e.target;
            const submitBtn = form.querySelector('.fra-onboarding-submit');
            const formData = new FormData(form);

            // Add action and nonce
            formData.append('action', 'fra_save_onboarding');
            formData.append('nonce', this.nonce);

            // Disable button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating...';

            fetch(fraData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    // Reload page to show dashboard
                    window.location.reload();
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Create My Dashboard';
                    alert('Error saving profile. Please try again.');
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Create My Dashboard';
                alert('Error saving profile. Please try again.');
            });
        },

        /**
         * Handle family size counter
         */
        handleCounter: function(e) {
            const btn = e.currentTarget;
            const action = btn.dataset.action;
            const input = btn.parentNode.querySelector('.fra-counter-input');
            let value = parseInt(input.value, 10) || 1;

            if (action === 'increase' && value < 10) {
                value++;
            } else if (action === 'decrease' && value > 1) {
                value--;
            }

            input.value = value;
        },

        /**
         * Handle edit profile
         */
        handleEditProfile: function(e) {
            e.preventDefault();
            // For now, reset onboarding to allow re-editing
            // In production, this would open a modal
            if (confirm('Do you want to update your relocation profile?')) {
                this.resetOnboarding();
            }
        },

        /**
         * Reset onboarding (allows re-editing profile)
         */
        resetOnboarding: function() {
            const formData = new FormData();
            formData.append('action', 'fra_update_dashboard_profile');
            formData.append('nonce', this.nonce);
            formData.append('onboarding_complete', '0');

            // We need to use the REST endpoint or add a separate AJAX handler
            fetch(this.apiBase + '/profile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.getWpNonce()
                },
                body: JSON.stringify({
                    onboarding_complete: false
                }),
                credentials: 'same-origin'
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    window.location.reload();
                }
            });
        },

        /**
         * Get WP REST nonce
         */
        getWpNonce: function() {
            // Try to get from wpApiSettings (if REST API enqueued)
            if (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce) {
                return wpApiSettings.nonce;
            }
            // Try to get from fraData
            if (typeof fraData !== 'undefined' && fraData.nonce) {
                return fraData.nonce;
            }
            return this.nonce;
        }
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => FRADashboard.init());
    } else {
        FRADashboard.init();
    }

    // Export for external use
    window.FRADashboard = FRADashboard;
})();
