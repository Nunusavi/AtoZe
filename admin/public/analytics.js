/**
 * AtoZe Engineering Analytics Tracker
 * Tracks custom events for marketing analytics
 */
(function() {
    'use strict';

    const Analytics = {
        /**
         * Track a custom event
         * @param {string} eventType - Type of event (e.g., 'form_submit', 'button_click')
         * @param {object} eventData - Additional data about the event
         */
        trackEvent: function(eventType, eventData = {}) {
            fetch('/admin/events.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    event_type: eventType,
                    event_data: eventData
                })
            }).catch(function(error) {
                console.error('Analytics tracking error:', error);
            });
        },

        /**
         * Track form submission
         */
        trackFormSubmit: function(formName, formData = {}) {
            this.trackEvent('form_submit', {
                form_name: formName,
                ...formData
            });
        },

        /**
         * Track button click
         */
        trackButtonClick: function(buttonName, buttonLocation = '') {
            this.trackEvent('button_click', {
                button_name: buttonName,
                location: buttonLocation
            });
        },

        /**
         * Track product view
         */
        trackProductView: function(productName, productId = '') {
            this.trackEvent('product_view', {
                product_name: productName,
                product_id: productId
            });
        },

        /**
         * Track project view
         */
        trackProjectView: function(projectName) {
            this.trackEvent('project_view', {
                project_name: projectName
            });
        },

        /**
         * Track download
         */
        trackDownload: function(fileName, fileType = '') {
            this.trackEvent('download', {
                file_name: fileName,
                file_type: fileType
            });
        },

        /**
         * Track outbound link click
         */
        trackOutboundLink: function(url, linkText = '') {
            this.trackEvent('outbound_link', {
                url: url,
                link_text: linkText
            });
        },

        /**
         * Initialize automatic tracking
         */
        init: function() {
            // Track all form submissions automatically
            document.addEventListener('submit', function(e) {
                const form = e.target;
                const formId = form.id || 'unnamed_form';

                // Only track non-admin forms
                if (!window.location.pathname.includes('/admin/')) {
                    Analytics.trackFormSubmit(formId);
                }
            });

            // Track CTA button clicks
            document.querySelectorAll('a.btn-main, a[href*="contact"]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const btnText = this.textContent.trim();
                    const btnHref = this.getAttribute('href');
                    Analytics.trackButtonClick(btnText, btnHref);
                });
            });

            // Track phone number clicks
            document.querySelectorAll('a[href^="tel:"]').forEach(function(link) {
                link.addEventListener('click', function() {
                    Analytics.trackEvent('phone_click', {
                        phone_number: this.getAttribute('href').replace('tel:', '')
                    });
                });
            });

            // Track outbound links
            document.querySelectorAll('a[href^="http"]').forEach(function(link) {
                const href = link.getAttribute('href');
                const currentDomain = window.location.hostname;

                try {
                    const linkDomain = new URL(href).hostname;
                    if (linkDomain !== currentDomain) {
                        link.addEventListener('click', function() {
                            Analytics.trackOutboundLink(href, this.textContent.trim());
                        });
                    }
                } catch(e) {
                    // Invalid URL, skip
                }
            });

            console.log('AtoZe Analytics initialized');
        }
    };

    // Make Analytics available globally
    window.AtoZeAnalytics = Analytics;

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            Analytics.init();
        });
    } else {
        Analytics.init();
    }
})();
