<style>
    /* Shared soft shadow — equal weight on cards, header, sidebar */
    :root {
        --crm-shadow: 0 1px 3px rgb(15 23 42 / 0.06), 0 1px 2px rgb(15 23 42 / 0.04);
        --crm-shadow-side: 2px 0 6px rgb(15 23 42 / 0.05);
    }

    .dark {
        --crm-shadow: 0 1px 3px rgb(0 0 0 / 0.25), 0 1px 2px rgb(0 0 0 / 0.15);
        --crm-shadow-side: 2px 0 6px rgb(0 0 0 / 0.2);
    }

    .fi-section:not(.fi-section-not-contained):not(.fi-aside),
    .fi-section.fi-aside > .fi-section-content-ctn,
    .fi-wi-stats-overview-stat {
        --tw-ring-shadow: 0 0 #0000 !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        --tw-shadow: var(--crm-shadow) !important;
        border: none !important;
        outline: none !important;
        box-shadow: var(--crm-shadow) !important;
    }

    .fi-sidebar,
    .fi-sidebar.fi-sidebar-open {
        border: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        box-shadow: var(--crm-shadow-side) !important;
    }

    .fi-sidebar-header {
        border: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        box-shadow: none !important;
    }

    .fi-topbar {
        border: none !important;
        border-bottom: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
        --tw-ring-offset-shadow: 0 0 #0000 !important;
        --tw-shadow: var(--crm-shadow) !important;
        box-shadow: var(--crm-shadow) !important;
    }

    /* Smaller topbar chip text */
    .crm-topbar-chip {
        font-size: 0.625rem !important;
        line-height: 1.2 !important;
        padding: 0.2rem 0.55rem !important;
        font-weight: 500 !important;
        gap: 0.25rem !important;
    }

    /* Hide DevPortal brand on user panel */
    .fi-panel-user .fi-logo,
    .fi-panel-user .fi-topbar .fi-logo,
    .fi-panel-user .fi-sidebar-header .fi-logo {
        display: none !important;
    }

    /* Developer dashboard hero cards — compact, equal, responsive */
    .dev-hero-grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: 1fr;
        align-items: stretch;
    }

    @media (min-width: 640px) {
        .dev-hero-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .dev-hero-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.875rem;
        }
    }

    .dev-hero-card {
        position: relative;
        display: flex;
        height: 100%;
        min-height: 8rem;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.5rem;
        overflow: hidden;
        border-radius: 0.875rem;
        padding: 0.85rem 0.95rem;
        box-shadow: var(--crm-shadow);
    }

    .dev-hero-card--greet {
        color: #ecfeff;
        background: linear-gradient(145deg, #0f766e 0%, #134e4a 55%, #0f172a 100%);
    }

    .dev-hero-card__badge {
        display: inline-flex;
        width: max-content;
        max-width: 100%;
        flex-shrink: 0;
        align-items: center;
        gap: 0.35rem;
        border-radius: 9999px;
        background: rgb(255 255 255 / 0.12);
        padding: 0.25rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        line-height: 1;
        white-space: nowrap;
        color: #a5f3fc;
    }

    .dev-hero-card__badge svg {
        width: 0.85rem;
        height: 0.85rem;
        flex-shrink: 0;
    }

    .dev-hero-card__badge time {
        white-space: nowrap;
    }

    .dev-hero-card__body {
        margin-top: auto;
    }

    .dev-hero-card__eyebrow {
        margin: 0;
        font-size: 0.7rem;
        font-weight: 500;
        color: #99f6e4;
    }

    .dev-hero-card__title {
        margin: 0.15rem 0 0;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1.25;
        color: #fff;
        word-break: break-word;
    }

    .dev-hero-card__title--dark {
        color: #0f172a;
        font-size: 0.9rem;
    }

    .dev-hero-card__hint {
        margin: 0.2rem 0 0;
        font-size: 0.65rem;
        color: rgb(204 251 241 / 0.75);
    }

    .dev-hero-card--access {
        background: linear-gradient(160deg, #115e59 0%, #0f172a 100%);
        color: #fff;
    }

    .dev-hero-card__row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .dev-hero-card__label {
        margin: 0;
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #5eead4;
    }

    .dev-hero-card__metric {
        margin: 0.2rem 0 0;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        line-height: 1;
        color: #fff;
    }

    .dev-hero-card__sub {
        margin: 0;
        font-size: 0.65rem;
        line-height: 1.35;
        color: rgb(153 246 228 / 0.7);
    }

    .dev-hero-card__icon {
        display: flex;
        height: 1.75rem;
        width: 1.75rem;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px solid rgb(94 234 212 / 0.35);
        color: #99f6e4;
        background: rgb(255 255 255 / 0.06);
    }

    .dev-hero-card__cta {
        display: inline-flex;
        width: 100%;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        background: #99f6e4;
        padding: 0.4rem 0.65rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: #134e4a;
        text-decoration: none;
        transition: background 0.15s ease;
    }

    .dev-hero-card__cta:hover {
        background: #ccfbf1;
    }

    .dev-hero-card--docs {
        background: linear-gradient(160deg, #0f766e 0%, #99f6e4 42%, #f1f5f9 100%);
        color: #0f172a;
    }

    .dev-hero-card__brand {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #fff;
    }

    .dev-hero-card__pill {
        display: inline-flex;
        width: fit-content;
        border-radius: 9999px;
        background: rgb(15 23 42 / 0.08);
        padding: 0.15rem 0.5rem;
        font-size: 0.58rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: #0f766e;
    }

    .dev-hero-card__link {
        display: inline-block;
        margin-top: 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: #0f766e;
        text-decoration: none;
    }

    .dev-hero-card__link:hover {
        text-decoration: underline;
    }
</style>
