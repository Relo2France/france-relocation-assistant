<?php
/**
 * Template Name: France Relocation Homepage
 *
 * Modern landing page for France Relocation Assistant
 *
 * @package FRA_Member_Tools
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="fra-homepage">
    <!-- Hero Section -->
    <section class="fra-hero">
        <div class="fra-hero-bg">
            <div class="fra-hero-overlay"></div>
        </div>
        <div class="fra-container">
            <div class="fra-hero-content">
                <span class="fra-hero-badge">Your France Relocation Partner</span>
                <h1 class="fra-hero-title">
                    Move to France with<br>
                    <span class="fra-hero-highlight">Confidence</span>
                </h1>
                <p class="fra-hero-subtitle">
                    AI-powered guidance, personalized document generation, and step-by-step
                    tracking to make your French dream a reality.
                </p>
                <div class="fra-hero-cta">
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="fra-btn fra-btn-primary fra-btn-lg">
                        Start Your Journey
                        <svg class="fra-btn-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                    <a href="#features" class="fra-btn fra-btn-secondary fra-btn-lg">
                        See How It Works
                    </a>
                </div>
                <div class="fra-hero-stats">
                    <div class="fra-stat">
                        <span class="fra-stat-number">500+</span>
                        <span class="fra-stat-label">Successful Relocations</span>
                    </div>
                    <div class="fra-stat">
                        <span class="fra-stat-number">12</span>
                        <span class="fra-stat-label">Visa Types Supported</span>
                    </div>
                    <div class="fra-stat">
                        <span class="fra-stat-number">24/7</span>
                        <span class="fra-stat-label">AI Assistance</span>
                    </div>
                </div>
            </div>
            <div class="fra-hero-visual">
                <div class="fra-chat-preview">
                    <div class="fra-chat-header">
                        <div class="fra-chat-dot"></div>
                        <span>France Relocation Assistant</span>
                    </div>
                    <div class="fra-chat-messages">
                        <div class="fra-chat-message fra-chat-bot">
                            <p>Hello! I'm your France relocation assistant. How can I help you today?</p>
                        </div>
                        <div class="fra-chat-message fra-chat-user">
                            <p>I want to move to France as a retiree. What visa do I need?</p>
                        </div>
                        <div class="fra-chat-message fra-chat-bot">
                            <p>For retirees, the <strong>VLS-TS Visiteur</strong> (long-stay visitor visa) is typically the best option. You'll need to demonstrate sufficient income and health insurance. Would you like me to explain the requirements?</p>
                        </div>
                    </div>
                    <div class="fra-chat-input">
                        <span>Ask me anything about moving to France...</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="fra-features">
        <div class="fra-container">
            <div class="fra-section-header">
                <span class="fra-section-badge">Everything You Need</span>
                <h2 class="fra-section-title">Your Complete Relocation Toolkit</h2>
                <p class="fra-section-subtitle">
                    From initial planning to settling in, we guide you through every step of your French adventure.
                </p>
            </div>

            <div class="fra-features-grid">
                <!-- Feature 1: AI Assistant -->
                <div class="fra-feature-card fra-feature-highlight">
                    <div class="fra-feature-icon fra-feature-icon-ai">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1010 10A10 10 0 0012 2z"/>
                            <path d="M12 6v6l4 2"/>
                        </svg>
                    </div>
                    <h3 class="fra-feature-title">AI-Powered Guidance</h3>
                    <p class="fra-feature-desc">
                        Get instant answers to your France relocation questions. Our AI assistant
                        is trained on French immigration law and bureaucratic processes.
                    </p>
                    <ul class="fra-feature-list">
                        <li>Visa requirements &amp; eligibility</li>
                        <li>Document checklists</li>
                        <li>Timeline planning</li>
                        <li>Cost estimates</li>
                    </ul>
                </div>

                <!-- Feature 2: Document Generation -->
                <div class="fra-feature-card">
                    <div class="fra-feature-icon fra-feature-icon-docs">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <polyline points="14,2 14,8 20,8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10,9 9,9 8,9"/>
                        </svg>
                    </div>
                    <h3 class="fra-feature-title">Smart Documents</h3>
                    <p class="fra-feature-desc">
                        Generate personalized cover letters, attestations, and more -
                        all formatted to French standards.
                    </p>
                </div>

                <!-- Feature 3: Task Tracking -->
                <div class="fra-feature-card">
                    <div class="fra-feature-icon fra-feature-icon-tasks">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 11l3 3L22 4"/>
                            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                    </div>
                    <h3 class="fra-feature-title">Progress Tracking</h3>
                    <p class="fra-feature-desc">
                        Never miss a deadline with our Kanban-style task board and
                        visual pipeline tracking.
                    </p>
                </div>

                <!-- Feature 4: File Management -->
                <div class="fra-feature-card">
                    <div class="fra-feature-icon fra-feature-icon-files">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>
                        </svg>
                    </div>
                    <h3 class="fra-feature-title">Secure Storage</h3>
                    <p class="fra-feature-desc">
                        Keep all your important documents organized and accessible
                        in one secure location.
                    </p>
                </div>

                <!-- Feature 5: Timeline -->
                <div class="fra-feature-card">
                    <div class="fra-feature-icon fra-feature-icon-timeline">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                    </div>
                    <h3 class="fra-feature-title">Activity Timeline</h3>
                    <p class="fra-feature-desc">
                        Track your entire journey with a beautiful timeline view
                        of all your progress and milestones.
                    </p>
                </div>

                <!-- Feature 6: Notes -->
                <div class="fra-feature-card">
                    <div class="fra-feature-icon fra-feature-icon-notes">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                        </svg>
                    </div>
                    <h3 class="fra-feature-title">Personal Notes</h3>
                    <p class="fra-feature-desc">
                        Keep track of important information, contacts, and
                        reminders throughout your journey.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="fra-how-it-works">
        <div class="fra-container">
            <div class="fra-section-header">
                <span class="fra-section-badge">Simple Process</span>
                <h2 class="fra-section-title">How It Works</h2>
                <p class="fra-section-subtitle">
                    Get started in minutes and let us guide you through the entire process.
                </p>
            </div>

            <div class="fra-steps">
                <div class="fra-step">
                    <div class="fra-step-number">1</div>
                    <div class="fra-step-content">
                        <h3>Create Your Profile</h3>
                        <p>Tell us about yourself, your situation, and your goals. We'll personalize everything for you.</p>
                    </div>
                </div>
                <div class="fra-step-connector"></div>
                <div class="fra-step">
                    <div class="fra-step-number">2</div>
                    <div class="fra-step-content">
                        <h3>Get Your Roadmap</h3>
                        <p>Receive a customized checklist of tasks, documents, and deadlines specific to your visa type.</p>
                    </div>
                </div>
                <div class="fra-step-connector"></div>
                <div class="fra-step">
                    <div class="fra-step-number">3</div>
                    <div class="fra-step-content">
                        <h3>Track &amp; Complete</h3>
                        <p>Work through your tasks, generate documents, and watch your progress as you get closer to France.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portal Preview Section -->
    <section class="fra-portal-preview">
        <div class="fra-container">
            <div class="fra-preview-content">
                <span class="fra-section-badge">Members Portal</span>
                <h2 class="fra-section-title">Your Personal Command Center</h2>
                <p class="fra-section-subtitle">
                    Access your dashboard, track tasks, manage documents, and stay on top
                    of every detail of your relocation.
                </p>
                <ul class="fra-preview-features">
                    <li>
                        <svg class="fra-check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Visual progress tracking with stage pipeline
                    </li>
                    <li>
                        <svg class="fra-check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Drag-and-drop task management
                    </li>
                    <li>
                        <svg class="fra-check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Secure document storage with categories
                    </li>
                    <li>
                        <svg class="fra-check-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        AI-generated personalized documents
                    </li>
                </ul>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="fra-btn fra-btn-primary fra-btn-lg">
                    Get Portal Access
                </a>
            </div>
            <div class="fra-preview-visual">
                <div class="fra-dashboard-mockup">
                    <div class="fra-mockup-header">
                        <div class="fra-mockup-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span class="fra-mockup-title">Members Portal</span>
                    </div>
                    <div class="fra-mockup-body">
                        <div class="fra-mockup-sidebar"></div>
                        <div class="fra-mockup-content">
                            <div class="fra-mockup-progress">
                                <div class="fra-mockup-progress-bar" style="width: 65%"></div>
                            </div>
                            <div class="fra-mockup-cards">
                                <div class="fra-mockup-card"></div>
                                <div class="fra-mockup-card"></div>
                                <div class="fra-mockup-card"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Visa Types Section -->
    <section class="fra-visa-types">
        <div class="fra-container">
            <div class="fra-section-header">
                <span class="fra-section-badge">Comprehensive Support</span>
                <h2 class="fra-section-title">Supported Visa Types</h2>
                <p class="fra-section-subtitle">
                    Whether you're retiring, working, studying, or joining family, we've got you covered.
                </p>
            </div>

            <div class="fra-visa-grid">
                <div class="fra-visa-card">
                    <div class="fra-visa-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h3>VLS-TS Visiteur</h3>
                    <p>For retirees and those living on savings or passive income</p>
                </div>
                <div class="fra-visa-card">
                    <div class="fra-visa-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                        </svg>
                    </div>
                    <h3>Passeport Talent</h3>
                    <p>For entrepreneurs, investors, and skilled professionals</p>
                </div>
                <div class="fra-visa-card">
                    <div class="fra-visa-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                        </svg>
                    </div>
                    <h3>Student Visa</h3>
                    <p>For those pursuing education in France</p>
                </div>
                <div class="fra-visa-card">
                    <div class="fra-visa-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
                        </svg>
                    </div>
                    <h3>Family Visa</h3>
                    <p>For joining French citizens or residents</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="fra-cta">
        <div class="fra-container">
            <div class="fra-cta-content">
                <h2>Ready to Start Your French Adventure?</h2>
                <p>Join hundreds of expats who've successfully relocated to France with our help.</p>
                <div class="fra-cta-buttons">
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="fra-btn fra-btn-white fra-btn-lg">
                        Create Free Account
                    </a>
                    <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(home_url('/portal/')); ?>" class="fra-btn fra-btn-outline-white fra-btn-lg">
                        Go to Portal
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php get_footer(); ?>
