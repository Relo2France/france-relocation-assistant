/**
 * Breadcrumb Navigation Manager
 *
 * Manages the breadcrumb trail state and renders updates.
 * Listens for panel change events and navigation requests.
 *
 * @package FranceRelocationAssistant
 * @version 3.0.0
 */

const FRABreadcrumb = {
    /**
     * Current breadcrumb trail
     * @type {Array<{label: string, panel: string}>}
     */
    trail: [],

    /**
     * DOM container element
     * @type {HTMLElement|null}
     */
    container: null,

    /**
     * Initialize the breadcrumb manager
     */
    init() {
        this.container = document.getElementById('fra-breadcrumb-list');
        if (!this.container) {
            console.warn('FRABreadcrumb: Container not found');
            return;
        }

        // Initialize with home
        this.trail = [{ label: '\u{1F3E0} Home', panel: 'home' }];
        this.render();

        // Listen for panel changes from navigation system
        document.addEventListener('fra:panel-change', (e) => {
            this.handlePanelChange(e.detail);
        });

        // Handle clicks on breadcrumb items
        this.container.addEventListener('click', (e) => {
            const link = e.target.closest('a[data-panel]');
            if (link) {
                e.preventDefault();
                this.navigateTo(link.dataset.panel);
            }
        });
    },

    /**
     * Handle panel change events
     * @param {Object} detail - Event detail object
     * @param {string} detail.panel - Panel identifier
     * @param {string} detail.label - Human-readable label
     * @param {string} detail.action - Action type: push, pop, replace, reset
     */
    handlePanelChange(detail) {
        const { panel, label, action } = detail;

        switch (action) {
            case 'push':
                // Add new item to trail
                this.trail.push({ label, panel });
                break;

            case 'pop':
                // Remove last item (go back)
                if (this.trail.length > 1) {
                    this.trail.pop();
                }
                break;

            case 'replace':
                // Replace current item
                if (this.trail.length > 0) {
                    this.trail[this.trail.length - 1] = { label, panel };
                }
                break;

            case 'reset':
                // Reset to home
                this.trail = [{ label: '\u{1F3E0} Home', panel: 'home' }];
                break;

            default:
                console.warn('FRABreadcrumb: Unknown action', action);
        }

        this.render();
    },

    /**
     * Navigate to a specific point in the trail
     * @param {string} targetPanel - Panel identifier to navigate to
     */
    navigateTo(targetPanel) {
        // Find the target in the trail
        const targetIndex = this.trail.findIndex(item => item.panel === targetPanel);

        if (targetIndex >= 0) {
            // Truncate trail to target
            this.trail = this.trail.slice(0, targetIndex + 1);
            this.render();

            // Dispatch navigation event for the app to handle
            document.dispatchEvent(new CustomEvent('fra:navigate', {
                detail: { panel: targetPanel }
            }));
        }
    },

    /**
     * Render the breadcrumb trail
     */
    render() {
        if (!this.container) return;

        const html = this.trail.map((item, index) => {
            const isLast = index === this.trail.length - 1;

            if (isLast) {
                // Current page (not a link)
                return `
                    <li class="fra-breadcrumb-item fra-breadcrumb-current">
                        <span aria-current="page">${this.escapeHtml(item.label)}</span>
                    </li>
                `;
            }

            // Previous pages (clickable links)
            return `
                <li class="fra-breadcrumb-item">
                    <a href="#" data-panel="${this.escapeHtml(item.panel)}">
                        ${this.escapeHtml(item.label)}
                    </a>
                    <span class="fra-breadcrumb-separator" aria-hidden="true">\u2192</span>
                </li>
            `;
        }).join('');

        this.container.innerHTML = html;
    },

    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    FRABreadcrumb.init();
});
