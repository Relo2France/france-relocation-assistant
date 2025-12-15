/**
 * France Relocation Assistant - Frontend JavaScript
 * Version: 2.0.0
 * Claude-style chat interface with AI integration
 */
(function() {
    "use strict";
    
    var FRA = window.FRA = {
        messages: [],
        currentCategory: null,
        currentTopic: null,
        conversationContext: null,
        isLoading: false,
        trips: [],
        currentYear: new Date().getFullYear(),
        
        init: function() {
            this.loadTrips();
            this.bindEvents();
            this.bindMobileEvents();
            this.renderCalendar();
            this.updateStats();
            this.checkAuthMessages();
        },
        
        /**
         * Check for auth-related URL parameters and display appropriate messages
         * Handles: login_error, logged_out, logged_in, new_signup
         */
        checkAuthMessages: function() {
            var urlParams = new URLSearchParams(window.location.search);
            var messagesContainer = document.getElementById('fra-chat-messages');
            
            // Clean URL helper
            var cleanUrl = function() {
                var newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            };
            
            // LOGIN ERROR - Show error and login form
            if (urlParams.get('login_error') === '1') {
                cleanUrl();
                
                var errorMessage = (typeof fraData !== 'undefined' && fraData.loginError) 
                    ? fraData.loginError 
                    : 'Login failed. Please try again.';
                
                if (messagesContainer) {
                    messagesContainer.innerHTML = '';
                    
                    var messageDiv = document.createElement('div');
                    messageDiv.className = 'fra-message fra-assistant';
                    messageDiv.innerHTML = '<div class="fra-message-content">' +
                        '<p><strong>⚠️ Login Error</strong></p>' +
                        '<p>' + errorMessage + '</p>' +
                        '<p style="margin-top: 1rem;"><button type="button" class="fra-inchat-login-trigger" style="padding: 0.5rem 1rem; background: #e85a1b; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">Try Again</button></p>' +
                        '</div>';
                    messagesContainer.appendChild(messageDiv);
                    
                    // Bind the login trigger
                    var loginTrigger = messageDiv.querySelector('.fra-inchat-login-trigger');
                    if (loginTrigger && window.FRAInChatAuth) {
                        loginTrigger.addEventListener('click', function() {
                            window.FRAInChatAuth.showView('login');
                        });
                        // Automatically show login form after a brief moment
                        setTimeout(function() {
                            window.FRAInChatAuth.showView('login');
                        }, 500);
                    }
                }
                return;
            }
            
            // LOGGED OUT - Show goodbye message
            if (urlParams.get('logged_out') === '1') {
                cleanUrl();
                
                if (messagesContainer) {
                    messagesContainer.innerHTML = '';
                    
                    var messageDiv = document.createElement('div');
                    messageDiv.className = 'fra-message fra-assistant';
                    messageDiv.innerHTML = '<div class="fra-message-content">' +
                        '<p><strong>👋 You\'ve been logged out successfully!</strong></p>' +
                        '<p>Thanks for using Relo2France. Your session has been securely ended.</p>' +
                        '<p style="margin-top: 1rem;">Feel free to browse the free Knowledge Base topics, or <button type="button" class="fra-inchat-login-trigger" style="background: none; border: none; color: #e85a1b; text-decoration: underline; cursor: pointer; padding: 0; font-size: inherit;">log back in</button> to access your member tools.</p>' +
                        '</div>';
                    messagesContainer.appendChild(messageDiv);
                    
                    // Bind the login trigger
                    var loginTrigger = messageDiv.querySelector('.fra-inchat-login-trigger');
                    if (loginTrigger && window.FRAInChatAuth) {
                        loginTrigger.addEventListener('click', function() {
                            window.FRAInChatAuth.showView('login');
                        });
                    }
                }
                return;
            }
            
            // LOGGED IN - Returning user, go directly to Member Dashboard
            if (urlParams.get('logged_in') === '1') {
                cleanUrl();
                
                // Navigate directly to member tools dashboard (not the auth tiles view)
                setTimeout(function() {
                    // Try member tools navigation first
                    if (window.FRAMemberTools && window.FRAMemberTools.navigateToSection) {
                        window.FRAMemberTools.navigateToSection('dashboard');
                    } else {
                        // Fallback: fire navigation event for member tools plugin
                        document.dispatchEvent(new CustomEvent('fra:navigate', { 
                            detail: { section: 'dashboard' } 
                        }));
                    }
                }, 100);
                return;
            }
            
            // NEW SIGNUP - Show comprehensive welcome message for new members
            if (urlParams.get('new_signup') === '1') {
                cleanUrl();
                
                if (messagesContainer) {
                    messagesContainer.innerHTML = '';
                    
                    var messageDiv = document.createElement('div');
                    messageDiv.className = 'fra-message fra-assistant fra-welcome-message';
                    messageDiv.innerHTML = '<div class="fra-message-content">' +
                        '<div style="text-align: center; margin-bottom: 1.5rem;">' +
                            '<div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🇫🇷</div>' +
                            '<h2 style="margin: 0 0 0.25rem 0; color: #1e3a5f; font-size: 1.5rem;">Welcome to Relo2France!</h2>' +
                            '<p style="color: #6b7280; margin: 0;">Your account has been created successfully</p>' +
                        '</div>' +
                        
                        '<div style="background: #f9fafb; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem;">' +
                            '<p style="margin: 0 0 1rem 0; line-height: 1.6; color: #374151;">Relo2France is your comprehensive resource for planning and executing your move to France. We\'re here to guide you through every step of the relocation process.</p>' +
                            
                            '<div style="display: grid; gap: 0.75rem;">' +
                                '<div style="display: flex; align-items: flex-start; gap: 0.75rem;">' +
                                    '<span style="font-size: 1.25rem;">📍</span>' +
                                    '<div><strong style="color: #1e3a5f;">Location Insights</strong><br><span style="color: #4b5563; font-size: 0.875rem;">Explore different regions of France to find the perfect place for your new life</span></div>' +
                                '</div>' +
                                '<div style="display: flex; align-items: flex-start; gap: 0.75rem;">' +
                                    '<span style="font-size: 1.25rem;">💬</span>' +
                                    '<div><strong style="color: #1e3a5f;">Expert Guidance</strong><br><span style="color: #4b5563; font-size: 0.875rem;">Ask questions about moving to France and receive AI-powered answers sourced from official resources</span></div>' +
                                '</div>' +
                                '<div style="display: flex; align-items: flex-start; gap: 0.75rem;">' +
                                    '<span style="font-size: 1.25rem;">📋</span>' +
                                    '<div><strong style="color: #1e3a5f;">Visa Assistance</strong><br><span style="color: #4b5563; font-size: 0.875rem;">Navigate the French visa process with personalized checklists and document templates</span></div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        
                        '<div style="background: linear-gradient(135deg, #1e3a5f 0%, #2d4a6f 100%); border-radius: 8px; padding: 1.25rem; color: white; margin-bottom: 1rem;">' +
                            '<p style="margin: 0 0 0.75rem 0; font-weight: 600; color: white;">🚀 Get Started: Complete Your Visa Profile</p>' +
                            '<p style="margin: 0 0 1rem 0; font-size: 0.875rem; opacity: 0.9; color: white;">To provide you with customized information throughout the site, we\'ll need some details about your relocation plans. This helps us tailor visa requirements, timelines, and recommendations specifically to your situation.</p>' +
                            '<button type="button" class="fra-welcome-action-btn" data-action="profile" style="width: 100%; padding: 0.75rem 1rem; background: white; color: #1e3a5f; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9375rem; font-weight: 600; transition: transform 0.15s, box-shadow 0.15s;">' +
                                '✏️ Complete Your Visa Profile' +
                            '</button>' +
                        '</div>' +
                        
                        '<div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">' +
                            '<button type="button" class="fra-welcome-action-btn" data-action="dashboard" style="flex: 1; min-width: 140px; padding: 0.625rem 1rem; background: #f3f4f6; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500;">📊 View Dashboard</button>' +
                            '<button type="button" class="fra-welcome-action-btn" data-action="browse" style="flex: 1; min-width: 140px; padding: 0.625rem 1rem; background: #f3f4f6; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem; font-weight: 500;">📚 Explore Topics</button>' +
                        '</div>' +
                        '</div>';
                    messagesContainer.appendChild(messageDiv);
                    
                    // Bind action buttons
                    messageDiv.querySelectorAll('.fra-welcome-action-btn').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            var action = this.getAttribute('data-action');
                            if (action === 'profile' && window.FRAMemberTools) {
                                window.FRAMemberTools.navigateToSection('profile');
                            } else if (action === 'dashboard' && window.FRAInChatAuth) {
                                window.FRAInChatAuth.showView('dashboard');
                            }
                            // 'browse' just stays on current view
                        });
                        
                        // Add hover effects
                        btn.addEventListener('mouseenter', function() {
                            if (this.getAttribute('data-action') === 'profile') {
                                this.style.transform = 'translateY(-1px)';
                                this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                            }
                        });
                        btn.addEventListener('mouseleave', function() {
                            this.style.transform = '';
                            this.style.boxShadow = '';
                        });
                    });
                }
                return;
            }
        },
        
        /**
         * Bind mobile menu events
         */
        bindMobileEvents: function() {
            var navPanel = document.getElementById('fra-nav-panel');
            var overlay = document.getElementById('fra-mobile-overlay');
            var menuBtn = document.getElementById('fra-mobile-menu-btn');
            var closeBtn = document.getElementById('fra-mobile-close-btn');
            
            function openMobileMenu() {
                if (navPanel) navPanel.classList.add('mobile-open');
                if (overlay) overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            function closeMobileMenu() {
                if (navPanel) navPanel.classList.remove('mobile-open');
                if (overlay) overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            if (menuBtn) menuBtn.addEventListener('click', openMobileMenu);
            if (closeBtn) closeBtn.addEventListener('click', closeMobileMenu);
            if (overlay) overlay.addEventListener('click', closeMobileMenu);
            
            // Close menu when a topic is selected on mobile
            document.querySelectorAll('.fra-subtopic-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        closeMobileMenu();
                    }
                });
            });
            
            // Handle swipe to close
            var touchStartX = 0;
            var touchEndX = 0;
            
            if (navPanel) {
                navPanel.addEventListener('touchstart', function(e) {
                    touchStartX = e.changedTouches[0].screenX;
                }, { passive: true });
                
                navPanel.addEventListener('touchend', function(e) {
                    touchEndX = e.changedTouches[0].screenX;
                    if (touchStartX - touchEndX > 50) {
                        closeMobileMenu();
                    }
                }, { passive: true });
            }
        },
        
        /**
         * Track analytics event
         * @param {string} event - Event name (chat_message, topic_click, modal_open)
         * @param {object} data - Additional event data
         */
        trackEvent: function(event, data) {
            if (typeof fraData === 'undefined' || !fraData.ajaxUrl || !fraData.nonce) {
                return;
            }
            
            var formData = new FormData();
            formData.append('action', 'fra_track_event');
            formData.append('nonce', fraData.nonce);
            formData.append('event', event);
            
            // Add additional data
            if (data) {
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        formData.append(key, data[key]);
                    }
                }
            }
            
            // Fire and forget - don't wait for response
            fetch(fraData.ajaxUrl, {
                method: 'POST',
                body: formData
            }).catch(function() {
                // Silently fail - analytics shouldn't break the app
            });
        },
        
        bindEvents: function() {
            var self = this;
            
            // Category expansion
            document.querySelectorAll(".fra-category-header").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    self.toggleCategory(this.dataset.category);
                });
            });
            
            // Subtopic selection
            document.querySelectorAll(".fra-subtopic-btn").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    self.loadTopic(this.dataset.category, this.dataset.topic);
                });
            });
            
            // Quick topics
            document.querySelectorAll(".fra-quick-topic").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    self.loadTopic(this.dataset.category, this.dataset.topic);
                });
            });
            
            // Chat input
            var input = document.getElementById("fra-chat-input");
            var sendBtn = document.getElementById("fra-chat-send");
            
            if (input) {
                input.addEventListener("keydown", function(e) {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        self.sendMessage();
                    }
                });
                input.addEventListener("input", function() {
                    this.style.height = "auto";
                    this.style.height = Math.min(this.scrollHeight, 120) + "px";
                });
            }
            
            if (sendBtn) {
                sendBtn.addEventListener("click", function() { self.sendMessage(); });
            }
            
            // Day counter modal
            var dcBtn = document.getElementById("fra-day-counter-btn");
            var closeModal = document.getElementById("fra-close-modal");
            var modal = document.getElementById("fra-day-counter-modal");
            
            if (dcBtn) dcBtn.addEventListener("click", function() { 
                modal.classList.add("active"); 
                self.trackEvent('modal_open', { modal: 'day_counter' });
            });
            if (closeModal) closeModal.addEventListener("click", function() { modal.classList.remove("active"); });
            if (modal) modal.addEventListener("click", function(e) { if (e.target === modal) modal.classList.remove("active"); });
            
            // Help/Tips modal
            var helpBtn = document.getElementById("fra-help-btn");
            var closeHelpModal = document.getElementById("fra-close-help-modal");
            var helpModal = document.getElementById("fra-help-modal");
            
            if (helpBtn) helpBtn.addEventListener("click", function() { 
                helpModal.classList.add("active"); 
                self.trackEvent('modal_open', { modal: 'help' });
            });
            if (closeHelpModal) closeHelpModal.addEventListener("click", function() { helpModal.classList.remove("active"); });
            if (helpModal) helpModal.addEventListener("click", function(e) { if (e.target === helpModal) helpModal.classList.remove("active"); });
            
            // Help topic buttons - insert question and close modal
            document.querySelectorAll(".fra-help-topic-btn").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    var question = this.dataset.question;
                    var input = document.getElementById("fra-chat-input");
                    if (input && question) {
                        input.value = question;
                        input.focus();
                        // Trigger input event to resize textarea
                        input.dispatchEvent(new Event("input"));
                    }
                    if (helpModal) helpModal.classList.remove("active");
                });
            });
            
            this.bindDayCounterEvents();
        },
        
        toggleCategory: function(category) {
            var header = document.querySelector('.fra-category-header[data-category="' + category + '"]');
            var subtopics = document.querySelector('.fra-subtopics[data-category="' + category + '"]');
            
            if (header && subtopics) {
                var isExpanded = header.classList.contains("expanded");
                
                // Reset all headers and icons
                document.querySelectorAll(".fra-category-header").forEach(function(h) { 
                    h.classList.remove("expanded");
                    var icon = h.querySelector('.fra-expand-icon');
                    if (icon) icon.textContent = '+';
                });
                document.querySelectorAll(".fra-subtopics").forEach(function(s) { s.classList.remove("expanded"); });
                
                // Expand this one if it wasn't already expanded
                if (!isExpanded) {
                    header.classList.add("expanded");
                    subtopics.classList.add("expanded");
                    var icon = header.querySelector('.fra-expand-icon');
                    if (icon) icon.textContent = '−';
                }
            }
        },
        
        loadTopic: function(category, topic) {
            var kb = (typeof fraData !== "undefined" && fraData.knowledgeBase) ? fraData.knowledgeBase : {};
            if (!kb[category] || !kb[category][topic]) { return; }
            
            // CRITICAL: Reset view state before showing KB content
            var chatPanel = document.querySelector('.fra-chat-panel');
            if (chatPanel) {
                chatPanel.classList.remove('showing-inchat-auth');
                chatPanel.classList.remove('showing-member-content');
            }
            
            // Hide all auth view elements
            var authViews = ['login', 'signup', 'dashboard', 'account', 'subscriptions', 'payments'];
            authViews.forEach(function(v) {
                var el = document.getElementById('fra-inchat-' + v);
                if (el) el.style.display = 'none';
            });
            
            // Also call hideAuthViews if available
            if (window.FRAInChatAuth && window.FRAInChatAuth.hideViews) {
                window.FRAInChatAuth.hideViews();
            }
            
            // Track topic click for analytics
            this.trackEvent('topic_click', { category: category, topic: topic });
            
            var topicData = kb[category][topic];
            
            document.querySelectorAll(".fra-subtopic-btn").forEach(function(btn) { btn.classList.remove("active"); });
            var activeBtn = document.querySelector('.fra-subtopic-btn[data-category="' + category + '"][data-topic="' + topic + '"]');
            if (activeBtn) activeBtn.classList.add("active");
            
            var header = document.querySelector('.fra-category-header[data-category="' + category + '"]');
            var subtopics = document.querySelector('.fra-subtopics[data-category="' + category + '"]');
            if (header && !header.classList.contains("expanded")) {
                // Reset all other expanded states first
                document.querySelectorAll(".fra-category-header.expanded").forEach(function(h) { 
                    h.classList.remove("expanded");
                    var icon = h.querySelector('.fra-expand-icon');
                    if (icon) icon.textContent = '+';
                });
                document.querySelectorAll(".fra-subtopics.expanded").forEach(function(s) { s.classList.remove("expanded"); });
                
                // Expand this category
                header.classList.add("expanded");
                if (subtopics) subtopics.classList.add("expanded");
                var icon = header.querySelector('.fra-expand-icon');
                if (icon) icon.textContent = '−';
            }
            
            this.clearChat();
            this.currentCategory = category;
            this.currentTopic = topic;
            this.conversationContext = { category: category, topic: topic, title: topicData.title };
            
            // Pass update history if available
            var updateHistory = topicData.updateHistory || null;
            var lastVerified = topicData.lastVerified || null;
            
            // Pass true to scroll to top for topic loads
            this.addAIMessage(topicData.title, topicData.content, topicData.sources, true, true, updateHistory, lastVerified);
        },
        
        clearChat: function() {
            var welcome = document.getElementById("fra-welcome");
            var container = document.getElementById("fra-chat-messages");
            if (welcome) welcome.style.display = "none";
            if (container) container.querySelectorAll(".fra-message").forEach(function(msg) { msg.remove(); });
            this.messages = [];
        },
        
        sendMessage: function() {
            var input = document.getElementById("fra-chat-input");
            var message = input ? input.value.trim() : "";
            if (!message || this.isLoading) return;
            
            // Track chat message for analytics
            this.trackEvent('chat_message');
            
            input.value = "";
            input.style.height = "auto";
            
            var welcome = document.getElementById("fra-welcome");
            if (welcome) welcome.style.display = "none";
            
            this.addUserMessage(message);
            
            // Detect if this is a follow-up question
            var isFollowUp = this.isFollowUpQuestion(message);
            var kbResult = this.searchKnowledgeBase(message);
            
            // Detect if this is a meta question about the site itself
            var isMetaQuestion = this.isMetaQuestion(message);
            
            // Meta questions about the site should always go to AI
            if (isMetaQuestion && typeof fraData !== "undefined" && fraData.aiEnabled) {
                this.getAIResponse(message, null); // No KB context for meta questions
            } else if (isFollowUp && typeof fraData !== "undefined" && fraData.aiEnabled) {
                // Follow-up questions go to AI with context
                this.getAIResponse(message, kbResult);
            } else if (kbResult && kbResult.score >= 15) {
                // Good match - but check if it's the same as last response
                var lastResponse = this.getLastAssistantContent();
                if (lastResponse && kbResult.content === lastResponse) {
                    // Same content - this is a follow-up, use AI or explain
                    if (typeof fraData !== "undefined" && fraData.aiEnabled) {
                        this.getAIResponse(message, kbResult);
                    } else {
                        this.addAIMessage("More Specific Question?", "I've already shared the general information on this topic. Could you ask a more specific question? For example:\n\n• What documents are needed?\n• What are the costs?\n• How long does the process take?\n• What are the requirements?\n\nOr, if you have AI enabled in settings, I can provide more detailed answers.", [], false);
                    }
                } else {
                    this.addAIMessage(kbResult.title, kbResult.content, kbResult.sources, true);
                    this.conversationContext = { category: kbResult.category, topic: kbResult.title, title: kbResult.title };
                }
            } else if (typeof fraData !== "undefined" && fraData.aiEnabled) {
                this.getAIResponse(message, kbResult);
            } else if (kbResult && kbResult.score >= 8) {
                this.addAIMessage(kbResult.title + " (Possible Match)", kbResult.content, kbResult.sources, false);
            } else {
                this.addAIMessage("Topic Not Found", "I don't have specific information about that in my knowledge base. Try selecting a topic from the menu, or ask about:\n\n• Visas (visitor, work, talent, spouse)\n• Property purchase process\n• Healthcare enrollment (PUMA, Carte Vitale)\n• Tax obligations (183-day rule, FBAR)\n• Driving and license exchange\n• Shipping and pets\n• Banking\n\nYou can also enable AI in the plugin settings for more detailed answers.", [], false);
            }
        },
        
        isFollowUpQuestion: function(message) {
            var lower = message.toLowerCase();
            // Check for follow-up indicators
            var followUpPatterns = [
                /^(what about|how about|and what|but what|also|additionally)/i,
                /^(can you|could you|would you|tell me more|more about|explain)/i,
                /^(what if|is there|are there|do they|does it|does this)/i,
                /\?$/, // ends with question mark
                /(specific|specifically|particular|example|instance)/i
            ];
            
            // If conversation has context and message is short/a question, likely follow-up
            if (this.conversationContext && message.length < 100) {
                for (var i = 0; i < followUpPatterns.length; i++) {
                    if (followUpPatterns[i].test(lower)) return true;
                }
            }
            
            // Check if query words don't match a new topic well
            var words = lower.split(/\s+/).filter(function(w) { return w.length > 3; });
            var hasTopicKeyword = /(visa|property|mortgage|health|tax|drive|ship|bank|pet|settle)/i.test(lower);
            
            // Short question without topic keywords = likely follow-up
            if (this.conversationContext && !hasTopicKeyword && words.length < 8) {
                return true;
            }
            
            return false;
        },
        
        /**
         * Detect if question is about the site/service itself (meta question)
         */
        isMetaQuestion: function(message) {
            var lower = message.toLowerCase();
            
            // Keywords that indicate a question about the site itself
            var metaPatterns = [
                /member\s*(tool|feature|benefit|access)/i,
                /membership/i,
                /this\s*(site|website|service|app)/i,
                /relo2france/i,
                /what\s*(can|do)\s*you\s*(do|offer|help|provide)/i,
                /what\s*(are|is)\s*(the|your)\s*(feature|tool|service)/i,
                /tell\s*me\s*(about|more).*(site|member|feature|tool|service)/i,
                /how\s*(does|do)\s*(this|the)\s*(site|service|work)/i,
                /what('s| is)\s*included/i,
                /premium\s*(feature|member|access)/i,
                /free\s*vs\s*(paid|premium|member)/i
            ];
            
            for (var i = 0; i < metaPatterns.length; i++) {
                if (metaPatterns[i].test(lower)) {
                    return true;
                }
            }
            
            return false;
        },
        
        getLastAssistantContent: function() {
            for (var i = this.messages.length - 1; i >= 0; i--) {
                if (this.messages[i].role === "assistant" && this.messages[i].content) {
                    return this.messages[i].content;
                }
            }
            return null;
        },
        
        addUserMessage: function(text) {
            var container = document.getElementById("fra-chat-messages");
            if (!container) return;
            var div = document.createElement("div");
            div.className = "fra-message fra-message-user";
            div.innerHTML = "<p>" + this.escapeHtml(text) + "</p>";
            container.appendChild(div);
            this.messages.push({ role: "user", content: text });
            this.scrollToBottom();
        },
        
        addAIMessage: function(title, content, sources, isConfident, scrollToTop, updateHistory, lastVerified) {
            var container = document.getElementById("fra-chat-messages");
            if (!container) return;
            
            var div = document.createElement("div");
            div.className = "fra-message fra-message-ai";
            
            var html = '<div class="fra-message-ai-header"><span>🇫🇷 France Relocation Assistant</span>';
            if (isConfident) html += '<span class="fra-ai-badge">Knowledge Base</span>';
            if (lastVerified) html += '<span class="fra-verified-badge">Verified ' + this.escapeHtml(lastVerified) + '</span>';
            html += '</div><div class="fra-message-ai-content">';
            if (title) html += '<h3>' + this.escapeHtml(title) + '</h3>';
            html += this.formatContent(content, updateHistory);
            
            // Add update history section if there are updates
            if (updateHistory && updateHistory.length > 0) {
                // Filter out updates with undefined/null values
                var validUpdates = updateHistory.filter(function(update) {
                    return update.from && update.to && update.from !== 'undefined' && update.to !== 'undefined';
                });
                
                if (validUpdates.length > 0) {
                    html += '<div class="fra-update-history">';
                    html += '<div class="fra-update-history-title"><span style="color:#dc2626">*</span> Recent Updates</div>';
                    // Show most recent 3 updates
                    var recentUpdates = validUpdates.slice(-3).reverse();
                    recentUpdates.forEach(function(update) {
                        var dateStr = update.date ? new Date(update.date).toLocaleDateString() : 'Recently';
                        html += '<div class="fra-update-history-item">';
                        html += '<span class="fra-old-value">' + update.from + '</span> → <strong>' + update.to + '</strong>';
                        html += ' <span class="fra-update-date">(' + dateStr + ')</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }
            }
            
            if (sources && sources.length > 0) {
                html += '<div class="fra-sources"><span class="fra-sources-label">Sources</span>';
                sources.forEach(function(src) { html += '<a href="' + src.url + '" target="_blank">' + src.name + '</a>'; });
                html += '</div>';
            }
            html += '</div>';
            html += '<div class="fra-topic-actions"><p>Need more details or want to save this?</p>';
            html += '<button class="fra-action-btn fra-action-btn-secondary fra-print-topic">Print Summary</button></div>';
            
            div.innerHTML = html;
            container.appendChild(div);
            
            var self = this;
            var printBtn = div.querySelector(".fra-print-topic");
            if (printBtn) printBtn.addEventListener("click", function() { self.printConversation(); });
            
            this.messages.push({ role: "assistant", content: content, title: title });
            
            // Scroll to top for initial topic load, bottom for chat responses
            if (scrollToTop) {
                this.scrollToTop();
            } else {
                this.scrollToBottom();
            }
        },
        
        addLoadingMessage: function() {
            var container = document.getElementById("fra-chat-messages");
            if (!container) return;
            var div = document.createElement("div");
            div.className = "fra-message fra-loading";
            div.id = "fra-loading-message";
            
            var messages = [
                "Researching your question",
                "Checking the latest info",
                "Finding the best answer",
                "Consulting the knowledge base"
            ];
            var randomMsg = messages[Math.floor(Math.random() * messages.length)];
            
            div.innerHTML = '<div class="fra-loading-spinner"></div>' +
                '<div class="fra-loading-content">' +
                '<div class="fra-loading-title">AI is thinking<span class="fra-loading-dots"><span></span><span></span><span></span></span></div>' +
                '<div class="fra-loading-subtitle">' + randomMsg + '</div>' +
                '</div>';
            container.appendChild(div);
            this.scrollToBottom();
        },
        
        removeLoadingMessage: function() {
            var loading = document.getElementById("fra-loading-message");
            if (loading) loading.remove();
        },
        
        scrollToBottom: function() {
            var container = document.getElementById("fra-chat-messages");
            if (container) container.scrollTop = container.scrollHeight;
        },
        
        scrollToTop: function() {
            var container = document.getElementById("fra-chat-messages");
            if (container) container.scrollTop = 0;
        },
        
        searchKnowledgeBase: function(query) {
            var kb = (typeof fraData !== "undefined" && fraData.knowledgeBase) ? fraData.knowledgeBase : {};
            var queryLower = query.toLowerCase();
            var queryWords = queryLower.split(/\s+/).filter(function(w) { return w.length > 2; });
            var results = [];
            
            var wantsWork = /\b(work|job|employ|hired|working)\b/i.test(query);
            var wantsNoWork = /\b(retire|retirement|not work|no work|without work|visitor)\b/i.test(query);
            
            for (var category in kb) {
                if (!kb.hasOwnProperty(category)) continue;
                var topics = kb[category];
                
                for (var topicKey in topics) {
                    if (!topics.hasOwnProperty(topicKey)) continue;
                    var topic = topics[topicKey];
                    var score = 0;
                    
                    if (topic.keywords) {
                        for (var i = 0; i < topic.keywords.length; i++) {
                            var kw = topic.keywords[i].toLowerCase();
                            if (queryLower.indexOf(kw) !== -1) score += 15;
                            for (var w = 0; w < queryWords.length; w++) {
                                if (kw.indexOf(queryWords[w]) !== -1 || queryWords[w].indexOf(kw) !== -1) score += 8;
                            }
                        }
                    }
                    
                    if (topic.title) {
                        var titleLower = topic.title.toLowerCase();
                        for (var t = 0; t < queryWords.length; t++) {
                            if (titleLower.indexOf(queryWords[t]) !== -1) score += 10;
                        }
                    }
                    
                    // Intent scoring
                    if (wantsWork && topicKey === "work") score += 25;
                    if (wantsWork && topicKey === "visitor") score -= 20;
                    if (wantsNoWork && topicKey === "visitor") score += 20;
                    if (wantsNoWork && topicKey === "work") score -= 15;
                    
                    if (this.conversationContext && this.conversationContext.category === category) score += 10;
                    
                    if (score > 0) {
                        results.push({ title: topic.title, content: topic.content, keywords: topic.keywords, sources: topic.sources, score: score, category: category, topic: topicKey });
                    }
                }
            }
            
            results.sort(function(a, b) { return b.score - a.score; });
            return results.length > 0 ? results[0] : null;
        },
        
        getAIResponse: function(message, kbContext) {
            var self = this;
            
            // Check if AI is available
            if (typeof fraData === "undefined" || !fraData.aiEnabled) {
                this.addAIMessage("AI Not Enabled", 
                    "AI responses require configuration in the plugin settings. To enable AI:\n\n" +
                    "1. Go to WordPress Admin → FR Assistant → API Settings\n" +
                    "2. Enter your Claude API key\n" +
                    "3. Enable AI responses\n\n" +
                    "In the meantime, you can browse topics from the menu on the left.",
                    [], false);
                return;
            }
            
            this.isLoading = true;
            this.addLoadingMessage();
            
            var systemPrompt = "You are a helpful assistant for Americans relocating to France. Provide accurate, practical information. Be specific and detailed in your answers.";
            
            if (kbContext && kbContext.content) {
                systemPrompt += "\n\nRelevant knowledge base information:\n" + kbContext.content.substring(0, 1500);
            }
            
            if (this.conversationContext) {
                systemPrompt += "\n\nCurrent topic being discussed: " + this.conversationContext.title;
            }
            
            var conversationHistory = [];
            var recentMessages = this.messages.slice(-6);
            recentMessages.forEach(function(msg) {
                if (msg.role === "user") {
                    conversationHistory.push("User: " + msg.content);
                } else if (msg.role === "assistant" && msg.content) {
                    conversationHistory.push("Assistant: " + msg.content.substring(0, 300) + "...");
                }
            });
            
            if (conversationHistory.length > 0) {
                systemPrompt += "\n\nRecent conversation:\n" + conversationHistory.join("\n");
            }
            
            var requestData = {
                action: "fra_ai_query",
                nonce: fraData.nonce,
                message: message,
                context: JSON.stringify({ 
                    system: systemPrompt, 
                    category: this.conversationContext ? this.conversationContext.category : null,
                    topic: this.conversationContext ? this.conversationContext.title : null
                })
            };
            
            // Use fetch API for reliability
            var formData = new FormData();
            for (var key in requestData) {
                formData.append(key, requestData[key]);
            }
            
            fetch(fraData.ajaxUrl, {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error("HTTP " + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                try {
                    self.removeLoadingMessage();
                    self.isLoading = false;
                    
                    if (data.success && data.data && data.data.response) {
                        self.addAIMessageFromAI(data.data.response, data.data.membership_url);
                    } else {
                        var errorMsg = data.data || "Unknown error";
                        console.warn("FRA: API returned error:", errorMsg);
                        self.addAIMessage("AI Response Error", 
                            "Error: " + errorMsg,
                            [], false);
                    }
                } catch (e) {
                    console.error("FRA: Error processing response:", e);
                    self.removeLoadingMessage();
                    self.isLoading = false;
                    self.addAIMessage("Processing Error", 
                        "Error displaying response: " + e.message,
                        [], false);
                }
            })
            .catch(function(error) {
                console.error("FRA: Network error:", error);
                self.removeLoadingMessage();
                self.isLoading = false;
                self.addAIMessage("Connection Error", 
                    "Could not connect to AI service. Please try again.",
                    [], false);
            });
        },
        
        // Separate function for AI-generated responses (different badge)
        addAIMessageFromAI: function(content, membershipUrl) {
            var container = document.getElementById("fra-chat-messages");
            if (!container) return;
            
            var div = document.createElement("div");
            div.className = "fra-message fra-message-ai";
            
            var html = '<div class="fra-message-ai-header">';
            html += '<span>🇫🇷 France Relocation Assistant</span>';
            html += '<span class="fra-ai-badge fra-ai-badge-ai">AI Response</span>';
            html += '</div>';
            html += '<div class="fra-message-ai-content">';
            html += this.formatContent(content, null, membershipUrl);
            html += '</div>';
            html += '<div class="fra-topic-actions"><p>Need more details or want to save this?</p>';
            html += '<button class="fra-action-btn fra-action-btn-secondary fra-print-topic">Print Summary</button></div>';
            
            div.innerHTML = html;
            container.appendChild(div);
            
            var self = this;
            var printBtn = div.querySelector(".fra-print-topic");
            if (printBtn) printBtn.addEventListener("click", function() { self.printConversation(); });
            
            this.messages.push({ role: "assistant", content: content, title: null });
            this.scrollToBottom();
        },
        
        escapeHtml: function(text) {
            if (!text) return "";
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        },
        
        formatContent: function(content, updateHistory, membershipUrl) {
            if (!content) return "";
            var html = this.escapeHtml(content);
            html = html.replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>");
            
            // Handle membership link placeholder %%MEMBERLINK%%
            if (membershipUrl) {
                // Escape the URL for safe HTML insertion
                var safeUrl = this.escapeHtml(membershipUrl);
                html = html.replace(/%%MEMBERLINK%%/g, 
                    '<a href="' + safeUrl + '" class="fra-upsell-link">Become a member</a>');
            }
            
            // Mark updated values with red asterisk if we have update history
            if (updateHistory && Array.isArray(updateHistory) && updateHistory.length > 0) {
                updateHistory.forEach(function(update) {
                    if (update && update.to && update.to !== 'undefined') {
                        // Escape special regex characters in the value
                        var escapedValue = String(update.to).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        var regex = new RegExp('(' + escapedValue + ')', 'g');
                        html = html.replace(regex, '<span class="fra-updated-value">$1</span>');
                    }
                });
            }
            
            var lines = html.split("\n"), result = [], inList = false, listType = null;
            var inPracticeStarted = false;
            var inPracticeContent = [];
            
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i], trimmed = line.trim();
                
                // Check for markdown headers (## Header or ### Header)
                var h2Match = trimmed.match(/^##\s+(.+)$/);
                if (h2Match) {
                    if (inList) { result.push("</" + listType + ">"); inList = false; listType = null; }
                    result.push("<h3 class='fra-content-header'>" + h2Match[1] + "</h3>");
                    continue;
                }
                var h3Match = trimmed.match(/^###\s+(.+)$/);
                if (h3Match) {
                    if (inList) { result.push("</" + listType + ">"); inList = false; listType = null; }
                    result.push("<h4 class='fra-content-subheader'>" + h3Match[1] + "</h4>");
                    continue;
                }
                
                // Check for In Practice section start - flexible matching
                // Matches: "**In Practice**", "**In Practice:**", "In Practice", "In practice:", etc.
                var inPracticeMatch = trimmed.match(/^(<strong>)?\s*In\s+Practice:?\s*(<\/strong>)?:?\s*$/i);
                if (inPracticeMatch) {
                    // Close any open list
                    if (inList) { result.push("</" + listType + ">"); inList = false; listType = null; }
                    inPracticeStarted = true;
                    continue;
                }
                
                // Also check for In Practice as a header with content following
                var inPracticeInlineMatch = trimmed.match(/^(<strong>)?\s*In\s+Practice:?\s*(<\/strong>)?:?\s+(.+)$/i);
                if (inPracticeInlineMatch && !inPracticeStarted) {
                    if (inList) { result.push("</" + listType + ">"); inList = false; listType = null; }
                    inPracticeStarted = true;
                    // Add the content after "In Practice:" to the collection
                    inPracticeContent.push(inPracticeInlineMatch[3]);
                    continue;
                }
                
                // If we're in the In Practice section, collect content
                if (inPracticeStarted) {
                    inPracticeContent.push(line);
                    continue;
                }
                
                var bulletMatch = trimmed.match(/^[•\-\*]\s+(.+)$/);
                if (bulletMatch) {
                    if (!inList || listType !== "ul") { if (inList) result.push("</" + listType + ">"); result.push("<ul>"); inList = true; listType = "ul"; }
                    result.push("<li>" + bulletMatch[1] + "</li>");
                    continue;
                }
                var numMatch = trimmed.match(/^(\d+)[.)]\s+(.+)$/);
                if (numMatch) {
                    if (!inList || listType !== "ol") { if (inList) result.push("</" + listType + ">"); result.push("<ol>"); inList = true; listType = "ol"; }
                    result.push("<li>" + numMatch[2] + "</li>");
                    continue;
                }
                if (inList) { result.push("</" + listType + ">"); inList = false; listType = null; }
                if (trimmed === "") { /* skip empty lines to tighten spacing */ } else result.push("<p>" + line + "</p>");
            }
            if (inList) result.push("</" + listType + ">");
            
            // If we collected In Practice content, format and add it
            if (inPracticeContent.length > 0) {
                result.push('<div class="fra-in-practice-box">');
                result.push('<div class="fra-in-practice-header">✨ In Practice</div>');
                result.push('<div class="fra-in-practice-content">');
                
                // Process In Practice content
                var ipInList = false, ipListType = null;
                for (var j = 0; j < inPracticeContent.length; j++) {
                    var ipLine = inPracticeContent[j], ipTrimmed = ipLine.trim();
                    
                    var ipBulletMatch = ipTrimmed.match(/^[•\-\*]\s+(.+)$/);
                    if (ipBulletMatch) {
                        if (!ipInList || ipListType !== "ul") { if (ipInList) result.push("</" + ipListType + ">"); result.push("<ul>"); ipInList = true; ipListType = "ul"; }
                        result.push("<li>" + ipBulletMatch[1] + "</li>");
                        continue;
                    }
                    if (ipInList) { result.push("</" + ipListType + ">"); ipInList = false; ipListType = null; }
                    if (ipTrimmed !== "") result.push("<p>" + ipLine + "</p>");
                }
                if (ipInList) result.push("</" + ipListType + ">");
                
                result.push('</div></div>');
            }
            
            return result.join("");
        },
        
        printConversation: function() {
            var container = document.getElementById("fra-chat-messages");
            if (!container) return;
            
            var w = window.open("", "_blank");
            if (w) {
                var html = '<!DOCTYPE html><html><head><title>France Relocation Summary</title><style>body{font-family:-apple-system,sans-serif;max-width:700px;margin:40px auto;padding:20px;line-height:1.7}h3{margin-top:24px}ul,ol{margin:12px 0;padding-left:24px}li{margin:6px 0}.fra-sources{margin-top:16px;padding-top:12px;border-top:1px solid #ddd;font-size:13px;color:#666}.fra-sources a{color:#ff6600;margin-right:16px}</style></head><body>';
                html += '<h1>🇫🇷 France Relocation Summary</h1><p style="color:#888">Generated ' + new Date().toLocaleDateString() + '</p><hr>';
                container.querySelectorAll(".fra-message-ai").forEach(function(msg) {
                    var content = msg.querySelector(".fra-message-ai-content");
                    if (content) html += content.innerHTML;
                });
                html += '</body></html>';
                w.document.write(html);
                w.document.close();
                w.print();
            }
        },
        
        // Day Counter
        bindDayCounterEvents: function() {
            var self = this;
            var prevYear = document.getElementById("fra-prev-year");
            var nextYear = document.getElementById("fra-next-year");
            var addTripBtn = document.getElementById("fra-add-trip-btn");
            var saveTrip = document.getElementById("fra-save-trip");
            var cancelTrip = document.getElementById("fra-cancel-trip");
            var exportBtn = document.getElementById("fra-export-data");
            var importInput = document.getElementById("fra-import-data");
            var clearAllBtn = document.getElementById("fra-clear-all-data");
            
            if (prevYear) prevYear.addEventListener("click", function() { self.currentYear--; self.renderCalendar(); self.updateStats(); });
            if (nextYear) nextYear.addEventListener("click", function() { self.currentYear++; self.renderCalendar(); self.updateStats(); });
            if (addTripBtn) addTripBtn.addEventListener("click", function() { document.getElementById("fra-add-trip-form").classList.toggle("active"); });
            if (saveTrip) saveTrip.addEventListener("click", function() { self.saveTrip(); });
            if (cancelTrip) cancelTrip.addEventListener("click", function() { document.getElementById("fra-add-trip-form").classList.remove("active"); });
            if (exportBtn) exportBtn.addEventListener("click", function() { self.exportData(); });
            if (importInput) importInput.addEventListener("change", function() { self.importData(this); });
            if (clearAllBtn) clearAllBtn.addEventListener("click", function() { self.clearAllTrips(); });
        },
        
        loadTrips: function() { try { var saved = localStorage.getItem("fra_trips"); if (saved) this.trips = JSON.parse(saved); } catch (e) { this.trips = []; } },
        saveTrips: function() { try { localStorage.setItem("fra_trips", JSON.stringify(this.trips)); } catch (e) {} },
        
        saveTrip: function() {
            var startDate = document.getElementById("fra-trip-start").value;
            var endDate = document.getElementById("fra-trip-end").value;
            var location = document.getElementById("fra-trip-location").value;
            var notes = document.getElementById("fra-trip-notes").value;
            
            if (!startDate || !endDate) { alert("Please enter both dates."); return; }
            if (new Date(endDate) < new Date(startDate)) { alert("End date must be after start."); return; }
            
            this.trips.push({ id: Date.now(), startDate: startDate, endDate: endDate, location: location, notes: notes });
            this.trips.sort(function(a, b) { return new Date(a.startDate) - new Date(b.startDate); });
            this.saveTrips(); this.renderCalendar(); this.updateStats();
            document.getElementById("fra-add-trip-form").classList.remove("active");
            document.getElementById("fra-trip-start").value = "";
            document.getElementById("fra-trip-end").value = "";
            document.getElementById("fra-trip-notes").value = "";
        },
        
        deleteTrip: function(id) { if (confirm("Delete?")) { this.trips = this.trips.filter(function(t) { return t.id !== id; }); this.saveTrips(); this.renderCalendar(); this.updateStats(); } },
        clearAllTrips: function() { if (confirm("Delete all?")) { this.trips = []; this.saveTrips(); this.renderCalendar(); this.updateStats(); } },
        
        getLocationForDate: function(dateStr) {
            var date = new Date(dateStr + "T12:00:00");
            for (var i = 0; i < this.trips.length; i++) {
                var t = this.trips[i];
                if (date >= new Date(t.startDate + "T00:00:00") && date <= new Date(t.endDate + "T23:59:59")) return t.location;
            }
            return null;
        },
        
        calculateDaysForYear: function(year) {
            var result = { france: 0, us: 0, other: 0, untracked: 0 };
            var end = new Date(year, 11, 31), today = new Date();
            if (today < end) end = today;
            var current = new Date(year, 0, 1);
            while (current <= end) {
                var dateStr = current.getFullYear() + "-" + String(current.getMonth() + 1).padStart(2, "0") + "-" + String(current.getDate()).padStart(2, "0");
                var loc = this.getLocationForDate(dateStr);
                if (loc === "france") result.france++; else if (loc === "us") result.us++; else if (loc === "other") result.other++; else result.untracked++;
                current.setDate(current.getDate() + 1);
            }
            return result;
        },
        
        calculateRolling183: function() {
            var today = new Date(), start = new Date(today); start.setDate(start.getDate() - 182);
            var count = 0, current = new Date(start);
            while (current <= today) {
                var dateStr = current.getFullYear() + "-" + String(current.getMonth() + 1).padStart(2, "0") + "-" + String(current.getDate()).padStart(2, "0");
                if (this.getLocationForDate(dateStr) === "france") count++;
                current.setDate(current.getDate() + 1);
            }
            return count;
        },
        
        updateStats: function() {
            var stats = this.calculateDaysForYear(this.currentYear);
            var rolling = this.calculateRolling183();
            var remaining = Math.max(0, 183 - rolling);
            
            var el = document.getElementById("fra-stat-france"); if (el) el.textContent = stats.france;
            el = document.getElementById("fra-stat-us"); if (el) el.textContent = stats.us;
            el = document.getElementById("fra-stat-other"); if (el) el.textContent = stats.other;
            el = document.getElementById("fra-stat-untracked"); if (el) el.textContent = stats.untracked;
            el = document.getElementById("fra-rolling-used"); if (el) el.textContent = rolling;
            el = document.getElementById("fra-current-year"); if (el) el.textContent = this.currentYear;
            
            var bar = document.getElementById("fra-progress-bar");
            if (bar) {
                bar.style.width = Math.min(100, (rolling / 183) * 100) + "%";
                bar.className = "fra-progress-bar";
                if (rolling >= 183) bar.classList.add("fra-danger");
                else if (remaining < 30) bar.classList.add("fra-warning");
            }
            
            var status = document.getElementById("fra-status-message");
            if (status) {
                if (rolling >= 183) { status.className = "fra-status-message fra-status-danger"; status.innerHTML = "<strong>⚠ Warning:</strong> Exceeded 183 days in France."; }
                else if (remaining < 30) { status.className = "fra-status-message fra-status-warning"; status.innerHTML = "<strong>⚠ Caution:</strong> Only " + remaining + " days remaining."; }
                else { status.className = "fra-status-message fra-status-ok"; status.innerHTML = "<strong>✓ OK:</strong> " + remaining + " days remaining."; }
            }
        },
        
        renderCalendar: function() {
            var grid = document.getElementById("fra-calendar-grid");
            if (!grid) return;
            grid.innerHTML = "";
            
            var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            var todayStr = new Date().getFullYear() + "-" + String(new Date().getMonth() + 1).padStart(2, "0") + "-" + String(new Date().getDate()).padStart(2, "0");
            
            for (var m = 0; m < 12; m++) {
                var monthDiv = document.createElement("div"); monthDiv.className = "fra-calendar-month";
                var nameDiv = document.createElement("div"); nameDiv.className = "fra-month-name"; nameDiv.textContent = months[m]; monthDiv.appendChild(nameDiv);
                var daysDiv = document.createElement("div"); daysDiv.className = "fra-month-days";
                
                ["S", "M", "T", "W", "T", "F", "S"].forEach(function(d) { var h = document.createElement("div"); h.className = "fra-day-header"; h.textContent = d; daysDiv.appendChild(h); });
                
                var firstDay = new Date(this.currentYear, m, 1).getDay();
                for (var e = 0; e < firstDay; e++) { var empty = document.createElement("div"); empty.className = "fra-day fra-day-empty"; daysDiv.appendChild(empty); }
                
                var daysInMonth = new Date(this.currentYear, m + 1, 0).getDate();
                for (var d = 1; d <= daysInMonth; d++) {
                    var dateStr = this.currentYear + "-" + String(m + 1).padStart(2, "0") + "-" + String(d).padStart(2, "0");
                    var loc = this.getLocationForDate(dateStr);
                    var dayDiv = document.createElement("div"); dayDiv.className = "fra-day";
                    if (loc === "france") dayDiv.classList.add("fra-day-france");
                    else if (loc === "us") dayDiv.classList.add("fra-day-us");
                    else if (loc === "other") dayDiv.classList.add("fra-day-other");
                    if (dateStr === todayStr) dayDiv.classList.add("fra-day-today");
                    dayDiv.textContent = d; daysDiv.appendChild(dayDiv);
                }
                monthDiv.appendChild(daysDiv); grid.appendChild(monthDiv);
            }
            this.renderTripList();
        },
        
        renderTripList: function() {
            var list = document.getElementById("fra-trip-list");
            if (!list) return;
            var self = this;
            var yearTrips = this.trips.filter(function(t) { return new Date(t.startDate).getFullYear() === self.currentYear; });
            
            if (yearTrips.length === 0) { list.innerHTML = '<div class="fra-no-trips">No trips for ' + this.currentYear + '</div>'; return; }
            
            list.innerHTML = "";
            var labels = { france: "France", us: "United States", other: "Other" };
            yearTrips.forEach(function(t) {
                var days = Math.ceil((new Date(t.endDate) - new Date(t.startDate)) / 86400000) + 1;
                var item = document.createElement("div"); item.className = "fra-trip-item fra-trip-" + t.location;
                var info = document.createElement("div"); info.className = "fra-trip-info";
                info.innerHTML = '<span class="fra-trip-dates">' + t.startDate + ' → ' + t.endDate + '</span><span class="fra-trip-duration">' + days + 'd</span><span class="fra-trip-location">' + labels[t.location] + '</span>';
                if (t.notes) info.innerHTML += '<div class="fra-trip-notes">' + t.notes + '</div>';
                item.appendChild(info);
                var del = document.createElement("button"); del.className = "fra-delete-trip"; del.textContent = "×";
                del.addEventListener("click", function() { self.deleteTrip(t.id); });
                item.appendChild(del); list.appendChild(item);
            });
        },
        
        exportData: function() {
            var self = this;
            var today = new Date();
            var dateStr = today.toISOString().split('T')[0];
            var lines = [];
            
            // Header
            lines.push("═══════════════════════════════════════════════════════════════");
            lines.push("           183-DAY TAX RESIDENCY TRACKER - EXPORT REPORT");
            lines.push("═══════════════════════════════════════════════════════════════");
            lines.push("");
            lines.push("Generated: " + today.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
            lines.push("Time: " + today.toLocaleTimeString('en-US'));
            lines.push("");
            
            // Rolling 183-day status
            var rolling = this.calculateRolling183();
            var remaining = Math.max(0, 183 - rolling);
            lines.push("───────────────────────────────────────────────────────────────");
            lines.push("CURRENT STATUS (Rolling 183-Day Window)");
            lines.push("───────────────────────────────────────────────────────────────");
            lines.push("");
            lines.push("  Days in France (last 183 days): " + rolling);
            lines.push("  Days remaining before threshold: " + remaining);
            lines.push("  Status: " + (rolling >= 183 ? "⚠ EXCEEDED - French tax residency triggered" : 
                                      remaining < 30 ? "⚠ CAUTION - Approaching limit" : 
                                      "✓ OK - Within safe limits"));
            lines.push("");
            
            // Get all years with data
            var years = [];
            this.trips.forEach(function(t) {
                var year = new Date(t.startDate).getFullYear();
                if (years.indexOf(year) === -1) years.push(year);
            });
            years.sort();
            
            // Stats for each year
            if (years.length > 0) {
                lines.push("───────────────────────────────────────────────────────────────");
                lines.push("ANNUAL SUMMARIES");
                lines.push("───────────────────────────────────────────────────────────────");
                
                years.forEach(function(year) {
                    var stats = self.calculateDaysForYear(year);
                    lines.push("");
                    lines.push("  " + year + ":");
                    lines.push("    🇫🇷 France:    " + self.padNumber(stats.france, 3) + " days");
                    lines.push("    🇺🇸 US:        " + self.padNumber(stats.us, 3) + " days");
                    lines.push("    🌍 Other:      " + self.padNumber(stats.other, 3) + " days");
                    lines.push("    Untracked:   " + self.padNumber(stats.untracked, 3) + " days");
                    lines.push("    ─────────────────────");
                    lines.push("    Total tracked: " + (stats.france + stats.us + stats.other) + " days");
                });
            }
            
            // All trips
            lines.push("");
            lines.push("───────────────────────────────────────────────────────────────");
            lines.push("ALL RECORDED TRIPS (" + this.trips.length + " total)");
            lines.push("───────────────────────────────────────────────────────────────");
            
            if (this.trips.length === 0) {
                lines.push("");
                lines.push("  No trips recorded yet.");
            } else {
                var labels = { france: "France", us: "United States", other: "Other" };
                var sortedTrips = this.trips.slice().sort(function(a, b) {
                    return new Date(a.startDate) - new Date(b.startDate);
                });
                
                sortedTrips.forEach(function(t, index) {
                    var days = Math.ceil((new Date(t.endDate) - new Date(t.startDate)) / 86400000) + 1;
                    lines.push("");
                    lines.push("  " + (index + 1) + ". " + labels[t.location]);
                    lines.push("     Dates: " + t.startDate + " → " + t.endDate + " (" + days + " days)");
                    if (t.notes) {
                        lines.push("     Notes: " + t.notes);
                    }
                });
            }
            
            // Footer
            lines.push("");
            lines.push("───────────────────────────────────────────────────────────────");
            lines.push("IMPORTANT TAX NOTES");
            lines.push("───────────────────────────────────────────────────────────────");
            lines.push("");
            lines.push("  • The 183-day rule is ONE factor in determining French tax");
            lines.push("    residency. Other factors include primary home location,");
            lines.push("    center of economic interests, and family ties.");
            lines.push("");
            lines.push("  • France uses a rolling 183-day calculation, not calendar year.");
            lines.push("");
            lines.push("  • US citizens remain subject to US taxation worldwide,");
            lines.push("    regardless of where they live.");
            lines.push("");
            lines.push("  • Consult a cross-border tax professional for your situation.");
            lines.push("");
            lines.push("═══════════════════════════════════════════════════════════════");
            lines.push("         Report generated by France Relocation Assistant");
            lines.push("                    https://relo2france.com");
            lines.push("═══════════════════════════════════════════════════════════════");
            
            // Create and download file
            var content = lines.join("\n");
            var blob = new Blob([content], { type: "text/plain;charset=utf-8" });
            var a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = "183-day-tracker-report-" + dateStr + ".txt";
            a.click();
        },
        
        padNumber: function(num, width) {
            var s = String(num);
            while (s.length < width) s = " " + s;
            return s;
        },
        
        importData: function(input) {
            var self = this, file = input.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var data = JSON.parse(e.target.result);
                    if (data.trips && confirm("Import " + data.trips.length + " trips?")) {
                        self.trips = self.trips.concat(data.trips);
                        self.trips.sort(function(a, b) { return new Date(a.startDate) - new Date(b.startDate); });
                        self.saveTrips(); self.renderCalendar(); self.updateStats(); alert("Imported!");
                    }
                } catch (err) { alert("Error: " + err.message); }
            };
            reader.readAsText(file); input.value = "";
        }
    };
    
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", function() { FRA.init(); });
    else FRA.init();
})();

/* =============================================================================
   MEMBER TOOLS INTEGRATION (v2.9.0+)
   JavaScript for France Relocation Member Tools add-on integration
   ============================================================================= */
(function() {
    'use strict';
    
    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initMemberTools();
    });
    
    function initMemberTools() {
        // User dropdown toggle
        var dropdownBtn = document.getElementById('fra-user-dropdown-btn');
        var dropdownMenu = document.getElementById('fra-user-dropdown-menu');
        
        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownBtn.parentElement.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownBtn.parentElement.classList.remove('active');
                }
            });
            
            // Handle dropdown item clicks for Member Tools sections
            dropdownMenu.querySelectorAll('.fra-dropdown-item[data-section]').forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    var section = this.getAttribute('data-section');
                    navigateToSection(section);
                    dropdownBtn.parentElement.classList.remove('active');
                });
            });
            
            // Handle My Membership Account click - show in-chat account view
            var accountLink = document.getElementById('fra-dropdown-account');
            if (accountLink) {
                accountLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    dropdownBtn.parentElement.classList.remove('active');
                    // Use the in-chat auth module to show account view
                    if (window.FRAInChatAuth && window.FRAInChatAuth.showView) {
                        window.FRAInChatAuth.showView('account');
                    }
                });
            }
            
            // Handle Subscriptions/Payments child links
            document.querySelectorAll('[data-account-section]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var section = this.getAttribute('data-account-section');
                    dropdownBtn.parentElement.classList.remove('active');
                    if (window.FRAInChatAuth && window.FRAInChatAuth.showView) {
                        window.FRAInChatAuth.showView(section);
                    }
                });
            });
        }
        
        // Member navigation buttons
        document.querySelectorAll('.fra-member-nav-btn[data-section]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var section = this.getAttribute('data-section');
                navigateToSection(section);
                
                // Update active state
                document.querySelectorAll('.fra-member-nav-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    }
    
    function navigateToSection(section) {
        var chatPanel = document.querySelector('.fra-chat-panel');
        var memberContent = document.getElementById('fra-member-content');
        var memberTitle = document.getElementById('fra-member-content-title');
        var memberBody = document.getElementById('fra-member-content-body');
        
        // CRITICAL: Remove ALL view state classes FIRST to avoid CSS conflicts
        if (chatPanel) {
            chatPanel.classList.remove('showing-inchat-auth');
            chatPanel.classList.remove('showing-member-content');
        }
        
        // Hide all auth view elements (set display: none)
        var authViews = ['login', 'signup', 'dashboard', 'account', 'subscriptions', 'payments'];
        authViews.forEach(function(v) {
            var el = document.getElementById('fra-inchat-' + v);
            if (el) el.style.display = 'none';
        });
        
        // Also call hideAuthViews if available (for any additional cleanup)
        if (window.FRAInChatAuth && window.FRAInChatAuth.hideViews) {
            window.FRAInChatAuth.hideViews();
        }
        
        // NOW add the showing-member-content class (after auth class is definitely gone)
        if (chatPanel) {
            chatPanel.classList.add('showing-member-content');
        }
        
        if (memberContent) {
            // Set title based on section
            var titles = {
                'dashboard': 'Dashboard',
                'my-checklists': 'My Checklists',
                'create-documents': 'Create Documents',
                'upload-verify': 'Upload & Verify',
                'glossary': 'Glossary',
                'guides': 'Guides',
                'profile': 'My Visa Profile',
                'my-documents': 'My Visa Documents'
            };
            if (memberTitle) {
                memberTitle.textContent = titles[section] || section.replace(/-/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            }
            
            // Show loading
            if (memberBody) {
                memberBody.innerHTML = '<div class="fra-loading">Loading...</div>';
            }
        }
        
        // Trigger custom event for add-on plugins to handle content loading
        var event = new CustomEvent('fra:navigate', {
            detail: { section: section }
        });
        document.dispatchEvent(event);
        
        // If FRA global exists, use it
        if (typeof FRA !== 'undefined' && FRA.navigateTo) {
            FRA.navigateTo(section);
        }
        
        // Close mobile menu if open
        var navPanel = document.getElementById('fra-nav-panel');
        var overlay = document.getElementById('fra-mobile-overlay');
        if (navPanel) navPanel.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
    }
    
    function backToChat() {
        var chatPanel = document.querySelector('.fra-chat-panel');
        if (chatPanel) {
            chatPanel.classList.remove('showing-member-content');
        }
        
        // Remove active states from member nav
        document.querySelectorAll('.fra-member-nav-btn').forEach(function(btn) {
            btn.classList.remove('active');
        });
    }
    
    // Back to chat button
    var backBtn = document.getElementById('fra-back-to-chat');
    if (backBtn) {
        backBtn.addEventListener('click', backToChat);
    }
    
    // Expose for external access
    window.FRAMemberTools = {
        navigateToSection: navigateToSection,
        backToChat: backToChat
    };
})();

/* =============================================================================
   IN-CHAT AUTHENTICATION MODULE
   Handles auth views (login/signup/dashboard/account) in the chat window
   ============================================================================= */
(function() {
    'use strict';
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInChatAuth);
    } else {
        initInChatAuth();
    }
    
    function initInChatAuth() {
        var chatPanel = document.querySelector('.fra-chat-panel');
        if (!chatPanel) return; // Not on a page with the chat interface
        
        // Login button in sidebar
        var loginBtn = document.getElementById('fra-inchat-login-btn');
        if (loginBtn) {
            loginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showAuthView('login');
            });
        }
        
        // Signup button in sidebar
        var signupBtn = document.getElementById('fra-inchat-signup-btn');
        if (signupBtn) {
            signupBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showAuthView('signup');
            });
        }
        
        // All signup trigger buttons (upgrade prompts)
        document.querySelectorAll('.fra-inchat-signup-trigger').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showAuthView('signup');
            });
        });
        
        // Logout button in sidebar
        var logoutBtn = document.getElementById('fra-inchat-logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                doLogout();
            });
        }

        // Utility bar login button (in site header)
        var utilityLoginBtn = document.getElementById('fra-utility-login-btn');
        if (utilityLoginBtn) {
            utilityLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showAuthView('login');
            });
        }

        // Switch to signup from login view
        var switchToSignup = document.getElementById('fra-inchat-switch-to-signup');
        if (switchToSignup) {
            switchToSignup.addEventListener('click', function(e) {
                e.preventDefault();
                showAuthView('signup');
            });
        }
        
        // Switch to login from signup view
        var switchToLogin = document.getElementById('fra-inchat-switch-to-login');
        if (switchToLogin) {
            switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                showAuthView('login');
            });
        }
        
        // Back buttons in auth views
        document.querySelectorAll('.fra-inchat-back-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var backTo = this.dataset.back || 'chat';
                if (backTo === 'dashboard') {
                    showAuthView('dashboard');
                } else {
                    hideAuthViews();
                }
            });
        });
        
        // Account settings button in dashboard
        var accountBtn = document.getElementById('fra-inchat-account-btn');
        if (accountBtn) {
            accountBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showAuthView('account');
            });
        }
        
        // Logout button in dashboard
        var dashboardLogout = document.getElementById('fra-inchat-dashboard-logout');
        if (dashboardLogout) {
            dashboardLogout.addEventListener('click', function(e) {
                e.preventDefault();
                doLogout();
            });
        }
        
        // Dashboard tiles - navigate to member tools sections
        document.querySelectorAll('.fra-inchat-dashboard-tile').forEach(function(tile) {
            tile.addEventListener('click', function(e) {
                e.preventDefault();
                var section = this.dataset.section;
                
                if (section === 'day-counter') {
                    // Open day counter modal
                    var modal = document.getElementById('fra-day-counter-modal');
                    if (modal) modal.classList.add('active');
                    hideAuthViews();
                } else {
                    // Navigate to member tools section
                    hideAuthViews();
                    
                    // Try to click the corresponding member nav button
                    var memberBtn = document.querySelector('.fra-member-nav-btn[data-section="' + section + '"]');
                    if (memberBtn) {
                        memberBtn.click();
                    } else {
                        // Fire custom event for Member Tools plugin
                        document.dispatchEvent(new CustomEvent('fra-open-section', { 
                            detail: { section: section } 
                        }));
                    }
                }
            });
        });
    }
    
    /**
     * Show an auth view in the chat panel
     */
    function showAuthView(view) {
        var chatPanel = document.querySelector('.fra-chat-panel');
        var views = ['login', 'signup', 'dashboard', 'account', 'subscriptions', 'payments'];
        
        // CRITICAL: Remove ALL view state classes FIRST to avoid CSS conflicts
        if (chatPanel) {
            chatPanel.classList.remove('showing-inchat-auth');
            chatPanel.classList.remove('showing-member-content');
        }
        
        // Hide all auth view elements first
        views.forEach(function(v) {
            var el = document.getElementById('fra-inchat-' + v);
            if (el) el.style.display = 'none';
        });
        
        // NOW show the requested view and add the auth class
        var targetView = document.getElementById('fra-inchat-' + view);
        if (targetView) {
            targetView.style.display = 'flex';
            if (chatPanel) {
                chatPanel.classList.add('showing-inchat-auth');
            }
        }
    }
    
    /**
     * Hide all auth views, return to normal chat
     */
    function hideAuthViews() {
        var chatPanel = document.querySelector('.fra-chat-panel');
        var views = ['login', 'signup', 'dashboard', 'account', 'subscriptions', 'payments'];
        
        views.forEach(function(v) {
            var el = document.getElementById('fra-inchat-' + v);
            if (el) el.style.display = 'none';
        });
        
        if (chatPanel) {
            chatPanel.classList.remove('showing-inchat-auth');
        }
    }
    
    /**
     * Perform logout
     */
    function doLogout() {
        var logoutUrl = (typeof fraData !== 'undefined' && fraData.logoutUrl) 
            ? fraData.logoutUrl 
            : '/wp-login.php?action=logout';
        window.location.href = logoutUrl;
    }
    
    // Expose functions globally
    window.FRAInChatAuth = {
        showView: showAuthView,
        hideViews: hideAuthViews,
        logout: doLogout
    };
})();
