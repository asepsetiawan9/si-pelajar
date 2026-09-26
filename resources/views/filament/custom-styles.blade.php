<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

<style>
    /* Global Typography & Font Smoothing */
    body, .fi-body, button, input, select, textarea {
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* Custom Modern Scrollbars */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, 0.4);
        border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, 0.6);
    }
    .dark ::-webkit-scrollbar-thumb {
        background: rgba(71, 85, 105, 0.5);
    }
    .dark ::-webkit-scrollbar-thumb:hover {
        background: rgba(100, 116, 139, 0.8);
    }

    /* Polish Filament Stats Overview Cards (better-ui & better-colors) */
    .fi-wi-stats-overview-stat {
        border-radius: 1.125rem !important; /* Concentric radius: 18px */
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.9)) !important;
        backdrop-filter: blur(12px);
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 4px 12px -2px rgba(0, 0, 0, 0.03) !important;
        transition: transform 0.22s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.22s cubic-bezier(0.2, 0, 0, 1), border-color 0.22s ease !important;
        overflow: hidden;
        position: relative;
    }

    .dark .fi-wi-stats-overview-stat {
        border: 1px solid rgba(51, 65, 85, 0.6) !important;
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.85), rgba(15, 23, 42, 0.9)) !important;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.25) !important;
    }

    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.04) !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
    }

    .dark .fi-wi-stats-overview-stat:hover {
        border-color: rgba(16, 185, 129, 0.4) !important;
        box-shadow: 0 14px 28px -6px rgba(0, 0, 0, 0.4) !important;
    }

    /* Subtle top glow indicator on stat cards */
    .fi-wi-stats-overview-stat::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #10b981, #06b6d4);
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .fi-wi-stats-overview-stat:hover::before {
        opacity: 1;
    }

    /* Stat Card Labels & Typography Polish */
    .fi-wi-stats-overview-stat-label {
        font-weight: 600 !important;
        letter-spacing: -0.01em !important;
        font-size: 0.875rem !important;
        color: #475569 !important;
    }
    .dark .fi-wi-stats-overview-stat-label {
        color: #94a3b8 !important;
    }

    .fi-wi-stats-overview-stat-value {
        font-weight: 700 !important;
        letter-spacing: -0.03em !important;
        color: #0f172a !important;
    }
    .dark .fi-wi-stats-overview-stat-value {
        color: #f8fafc !important;
    }

    /* Hero Banner Micro-Animations */
    @keyframes pulse-soft {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.04); opacity: 0.85; }
    }
    .animate-pulse-soft {
        animation: pulse-soft 3s infinite ease-in-out;
    }

    /* Polish Tables & Containers */
    .fi-ta-ctn {
        border-radius: 1.125rem !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 6px 16px -4px rgba(0, 0, 0, 0.03) !important;
        overflow: hidden;
    }
    .dark .fi-ta-ctn {
        border: 1px solid rgba(51, 65, 85, 0.6) !important;
        box-shadow: 0 6px 20px -4px rgba(0, 0, 0, 0.3) !important;
    }

    /* Filament Header Filters Form Polish */
    .fi-page-header form {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.85));
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 1rem;
        padding: 0.75rem 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
    }
    .dark .fi-page-header form {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.85));
        border: 1px solid rgba(51, 65, 85, 0.6);
    }

    /* Executive Hero Banner Card Guaranteed High-Contrast (Light & Dark) */
    .fi-wi-widget:has(.spko-hero-card) {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .spko-hero-card {
        background: linear-gradient(135deg, #090e1a 0%, #1e293b 55%, #064e3b 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(51, 65, 85, 0.7) !important;
        border-radius: 1.125rem !important;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2), 0 8px 10px -6px rgba(15, 23, 42, 0.15) !important;
    }

    .spko-hero-chip {
        background: rgba(15, 23, 42, 0.85) !important;
        border: 1px solid rgba(51, 65, 85, 0.8) !important;
        backdrop-filter: blur(12px) !important;
    }

    .spko-hero-btn-secondary {
        background: rgba(30, 41, 59, 0.85) !important;
        color: #e2e8f0 !important;
        border: 1px solid rgba(71, 85, 105, 0.8) !important;
    }
    .spko-hero-btn-secondary:hover {
        background: rgba(51, 65, 85, 0.95) !important;
        color: #ffffff !important;
    }

    /* Polish Chart Widgets */
    .fi-wi-chart {
        padding: 0 !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .fi-wi-chart .fi-section {
        border-radius: 1.125rem !important;
        border: 1px solid rgba(226, 232, 240, 0.85) !important;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92)) !important;
        backdrop-filter: blur(12px);
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 4px 12px -2px rgba(0, 0, 0, 0.03) !important;
        transition: transform 0.22s cubic-bezier(0.2, 0, 0, 1), box-shadow 0.22s cubic-bezier(0.2, 0, 0, 1), border-color 0.22s ease !important;
    }

    .dark .fi-wi-chart .fi-section {
        border: 1px solid rgba(51, 65, 85, 0.6) !important;
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.85), rgba(15, 23, 42, 0.9)) !important;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.25) !important;
    }

    .fi-wi-chart .fi-section:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.04) !important;
        border-color: rgba(99, 102, 241, 0.35) !important;
    }

    .dark .fi-wi-chart .fi-section:hover {
        border-color: rgba(99, 102, 241, 0.4) !important;
        box-shadow: 0 14px 28px -6px rgba(0, 0, 0, 0.4) !important;
    }

    /* Polish Chart Headings & Descriptions in Light and Dark Mode */
    .fi-wi-chart .fi-section-header-heading {
        font-size: 0.95rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.02em !important;
        color: #0f172a !important;
    }
    .dark .fi-wi-chart .fi-section-header-heading {
        color: #f8fafc !important;
    }

    .fi-wi-chart .fi-section-header-description {
        font-size: 0.8rem !important;
        color: #475569 !important;
        margin-top: 0.25rem !important;
    }
    .dark .fi-wi-chart .fi-section-header-description {
        color: #94a3b8 !important;
    }

    /* Mobile Responsive Polish */
    @media (max-width: 640px) {
        .fi-wi-stats-overview-stat {
            padding: 1rem !important;
        }
        .fi-wi-stats-overview-stat-value {
            font-size: 1.5rem !important;
        }
        .fi-wi-chart {
            padding: 1rem !important;
        }
    }
</style>
