/**
 * Relo2France Theme JavaScript
 *
 * @package Relo2France
 * @version 1.2.4
 */

(function() {
    'use strict';
    
    /**
     * Mobile menu toggle
     */
    function initMobileMenu() {
        var toggle = document.querySelector('.menu-toggle');
        var nav = document.querySelector('.main-navigation');
        
        if (!toggle || !nav) return;
        
        toggle.addEventListener('click', function() {
            nav.classList.toggle('toggled');
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', !expanded);
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target) && nav.classList.contains('toggled')) {
                nav.classList.remove('toggled');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
    
    /**
     * Smooth scroll for anchor links
     */
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                var target = document.querySelector(targetId);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
    /**
     * Add scroll class to header
     */
    function initScrollHeader() {
        var header = document.querySelector('.site-header');
        if (!header) return;
        
        var scrollThreshold = 50;
        
        function checkScroll() {
            if (window.scrollY > scrollThreshold) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
        
        window.addEventListener('scroll', checkScroll);
        checkScroll();
    }
    
    /**
     * Scroll-snap control for FRA pages
     * 
     * Behavior:
     * 1. Page loads at top - header visible, NO scroll-snap
     * 2. User scrolls down past header - enable scroll-snap, app locks in view
     * 3. User can scroll up to see header, down to see footer
     */
    function initScrollSnapControl() {
        var fraContainer = document.querySelector('.fra-container');
        if (!fraContainer) return;
        
        var html = document.documentElement;
        var header = document.querySelector('.fra-site-header-wrapper') || document.querySelector('.site-header');
        var headerHeight = header ? header.offsetHeight : 150;
        var snapEnabled = false;
        
        // Force scroll to top on page load (prevents browser restore)
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.scrollTo(0, 0);
        
        // Enable scroll-snap only after user scrolls past the header
        function handleScroll() {
            var scrollY = window.scrollY || window.pageYOffset;
            
            if (!snapEnabled && scrollY > headerHeight / 2) {
                // User has scrolled past header - enable snap
                snapEnabled = true;
                html.classList.add('scroll-snap-enabled');
            } else if (snapEnabled && scrollY < 10) {
                // User scrolled back to very top - disable snap so header stays visible
                snapEnabled = false;
                html.classList.remove('scroll-snap-enabled');
            }
        }
        
        // Use passive listener for performance
        window.addEventListener('scroll', handleScroll, { passive: true });
    }
    
    /**
     * Initialize when DOM is ready
     */
    function init() {
        initMobileMenu();
        initSmoothScroll();
        initScrollHeader();
        initScrollSnapControl();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
