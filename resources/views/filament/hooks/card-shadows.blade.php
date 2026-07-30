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

    .crm-topbar-nav {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
        flex-wrap: wrap;
    }

    .crm-topbar-links {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .crm-topbar-link {
        display: inline-flex;
        align-items: center;
        border-radius: 0.5rem;
        padding: 0.3rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 650;
        color: rgb(51 65 85);
        text-decoration: none;
        background: transparent;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .crm-topbar-link:hover {
        background: rgb(241 245 249);
        color: rgb(15 23 42);
    }

    .crm-topbar-link--active {
        background: rgb(204 251 241);
        color: rgb(15 118 110);
    }

    .dark .crm-topbar-link {
        color: rgb(203 213 225);
    }

    .dark .crm-topbar-link:hover {
        background: rgb(255 255 255 / 0.06);
        color: #fff;
    }

    .dark .crm-topbar-link--active {
        background: rgb(19 78 74 / 0.45);
        color: rgb(153 246 228);
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

    .dev-hero-card--wallet {
        background: linear-gradient(160deg, #0f766e 0%, #134e4a 50%, #0f172a 100%);
        color: #fff;
    }

    .dev-hero-card--earn {
        background: linear-gradient(160deg, #059669 0%, #065f46 50%, #0f172a 100%);
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

    /* Wallet page */
    .wallet-balance-grid {
        display: grid;
        gap: 0.875rem;
        grid-template-columns: 1fr;
    }

    @media (min-width: 640px) {
        .wallet-balance-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    .wallet-balance-card {
        position: relative;
        display: flex;
        min-height: 8.5rem;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
        border-radius: 0.875rem;
        padding: 1.1rem 1.15rem;
        box-shadow: var(--crm-shadow);
    }

    .wallet-balance-card::after {
        content: '';
        position: absolute;
        right: -1.5rem;
        top: -1.5rem;
        width: 7rem;
        height: 7rem;
        border-radius: 9999px;
        background: rgb(255 255 255 / 0.06);
        pointer-events: none;
    }

    .wallet-balance-card--main {
        color: #ecfeff;
        background: linear-gradient(145deg, #0f766e 0%, #134e4a 55%, #0f172a 100%);
    }

    .wallet-balance-card--earn {
        color: #ecfdf5;
        background: linear-gradient(145deg, #059669 0%, #065f46 55%, #0f172a 100%);
    }

    .wallet-balance-card__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .wallet-balance-card__label {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #99f6e4;
    }

    .wallet-balance-card--earn .wallet-balance-card__label {
        color: #a7f3d0;
    }

    .wallet-balance-card__chip {
        border-radius: 9999px;
        background: rgb(255 255 255 / 0.12);
        padding: 0.2rem 0.55rem;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: #ccfbf1;
    }

    .wallet-balance-card__chip--light {
        color: #d1fae5;
    }

    .wallet-balance-card__body {
        margin-top: 1rem;
    }

    .wallet-balance-card__amount {
        margin: 0;
        font-size: 1.85rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        line-height: 1.1;
        color: #fff;
    }

    .wallet-balance-card__hint {
        margin: 0.4rem 0 0;
        font-size: 0.72rem;
        line-height: 1.4;
        color: rgb(204 251 241 / 0.72);
    }

    .wallet-balance-card--earn .wallet-balance-card__hint {
        color: rgb(209 250 229 / 0.72);
    }

    .wallet-icon-wrap {
        display: inline-flex;
        height: 1.75rem;
        width: 1.75rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        background: rgb(13 148 136 / 0.1);
    }

    .dark .wallet-icon-wrap {
        background: rgb(45 212 191 / 0.12);
    }

    .wallet-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .wallet-preset {
        border-radius: 0.5rem;
        border: 1px solid rgb(15 23 42 / 0.1);
        background: rgb(248 250 252);
        padding: 0.45rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(51 65 85);
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .wallet-preset:hover {
        border-color: rgb(13 148 136 / 0.45);
        background: rgb(240 253 250);
        color: rgb(15 118 110);
    }

    .wallet-preset--active {
        border-color: rgb(13 148 136);
        background: rgb(13 148 136);
        color: #fff;
    }

    .wallet-preset--active:hover {
        border-color: rgb(15 118 110);
        background: rgb(15 118 110);
        color: #fff;
    }

    .dark .wallet-preset {
        border-color: rgb(148 163 184 / 0.2);
        background: rgb(30 41 59 / 0.6);
        color: rgb(226 232 240);
    }

    .dark .wallet-preset:hover {
        border-color: rgb(45 212 191 / 0.45);
        background: rgb(19 78 74 / 0.35);
        color: rgb(153 246 228);
    }

    .dark .wallet-preset--active {
        border-color: rgb(45 212 191);
        background: rgb(15 118 110);
        color: #fff;
    }

    .wallet-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border-radius: 0.75rem;
        background: rgb(248 250 252);
        padding: 2.25rem 1.25rem;
        text-align: center;
    }

    .dark .wallet-empty {
        background: rgb(255 255 255 / 0.04);
    }

    .wallet-empty__icon {
        display: flex;
        height: 3rem;
        width: 3rem;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.35rem;
        border-radius: 0.875rem;
        background: rgb(13 148 136 / 0.1);
        color: rgb(13 148 136);
    }

    .dark .wallet-empty__icon {
        background: rgb(45 212 191 / 0.12);
        color: rgb(94 234 212);
    }

    .wallet-empty__title {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .dark .wallet-empty__title {
        color: rgb(248 250 252);
    }

    .wallet-empty__hint {
        margin: 0;
        max-width: 18rem;
        font-size: 0.78rem;
        line-height: 1.45;
        color: rgb(100 116 139);
    }

    .dark .wallet-empty__hint {
        color: rgb(148 163 184);
    }

    .wallet-table-wrap {
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid rgb(15 23 42 / 0.08);
    }

    .dark .wallet-table-wrap {
        border-color: rgb(255 255 255 / 0.08);
    }

    .wallet-table {
        width: 100%;
        min-width: 28rem;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.8rem;
    }

    .wallet-table thead {
        background: rgb(248 250 252);
        color: rgb(100 116 139);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .dark .wallet-table thead {
        background: rgb(255 255 255 / 0.04);
        color: rgb(148 163 184);
    }

    .wallet-table th,
    .wallet-table td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
    }

    .wallet-table tbody tr {
        border-top: 1px solid rgb(15 23 42 / 0.06);
        transition: background 0.12s ease;
    }

    .dark .wallet-table tbody tr {
        border-top-color: rgb(255 255 255 / 0.06);
    }

    .wallet-table tbody tr:hover {
        background: rgb(248 250 252 / 0.8);
    }

    .dark .wallet-table tbody tr:hover {
        background: rgb(255 255 255 / 0.03);
    }

    .wallet-table__date {
        font-weight: 600;
        color: rgb(15 23 42);
    }

    .dark .wallet-table__date {
        color: rgb(241 245 249);
    }

    .wallet-table__time,
    .wallet-table__ref {
        margin-top: 0.15rem;
        font-size: 0.7rem;
        color: rgb(148 163 184);
    }

    .wallet-table__mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.75rem;
        font-weight: 500;
        color: rgb(71 85 105);
    }

    .dark .wallet-table__mono {
        color: rgb(203 213 225);
    }

    .wallet-table__amount {
        font-size: 0.875rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        color: rgb(15 23 42);
        white-space: nowrap;
    }

    .dark .wallet-table__amount {
        color: rgb(248 250 252);
    }

    /* Subscription plans */
    .subscription-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: 1fr;
        align-items: stretch;
    }

    @media (min-width: 768px) {
        .subscription-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1100px) {
        .subscription-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.1rem;
        }
    }

    .subscription-card {
        position: relative;
        display: flex;
        height: 100%;
        flex-direction: column;
        gap: 1rem;
        overflow: hidden;
        border-radius: 1rem;
        background: #fff;
        padding: 1.25rem 1.2rem 1.15rem;
        box-shadow: var(--crm-shadow);
        border: 1px solid rgb(15 23 42 / 0.06);
    }

    .dark .subscription-card {
        background: rgb(30 41 59 / 0.55);
        border-color: rgb(255 255 255 / 0.08);
    }

    .subscription-card--featured {
        border-color: rgb(13 148 136 / 0.45);
        background: linear-gradient(180deg, rgb(240 253 250) 0%, #fff 42%);
        box-shadow: 0 8px 24px rgb(13 148 136 / 0.12), var(--crm-shadow);
    }

    .dark .subscription-card--featured {
        background: linear-gradient(180deg, rgb(19 78 74 / 0.35) 0%, rgb(30 41 59 / 0.7) 50%);
        border-color: rgb(45 212 191 / 0.35);
    }

    .subscription-card__badge {
        position: absolute;
        top: 0.85rem;
        right: 0.85rem;
        border-radius: 9999px;
        background: rgb(13 148 136);
        padding: 0.2rem 0.55rem;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #fff;
    }

    .subscription-card__header {
        padding-right: 4rem;
    }

    .subscription-card__name {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: rgb(15 23 42);
    }

    .dark .subscription-card__name {
        color: #fff;
    }

    .subscription-card__desc {
        margin: 0.4rem 0 0;
        font-size: 0.8rem;
        line-height: 1.45;
        color: rgb(100 116 139);
    }

    .dark .subscription-card__desc {
        color: rgb(148 163 184);
    }

    .subscription-card__price-wrap {
        display: flex;
        align-items: baseline;
        gap: 0.15rem;
        flex-wrap: wrap;
    }

    .subscription-card__currency {
        font-size: 1rem;
        font-weight: 700;
        color: rgb(13 148 136);
    }

    .subscription-card__price {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        line-height: 1;
        color: rgb(15 23 42);
    }

    .dark .subscription-card__price {
        color: #fff;
    }

    .subscription-card__period {
        margin-left: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: rgb(100 116 139);
    }

    .dark .subscription-card__period {
        color: rgb(148 163 184);
    }

    .subscription-card__services {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        flex: 1 1 auto;
    }

    .subscription-card__services li {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 0.8rem;
        line-height: 1.35;
        color: rgb(51 65 85);
    }

    .dark .subscription-card__services li {
        color: rgb(203 213 225);
    }

    .subscription-card__check {
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
        margin-top: 0.1rem;
        color: rgb(13 148 136);
    }

    .subscription-card__method {
        display: inline-block;
        margin-right: 0.25rem;
        border-radius: 0.25rem;
        background: rgb(13 148 136 / 0.1);
        padding: 0.05rem 0.3rem;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: rgb(15 118 110);
        vertical-align: middle;
    }

    .dark .subscription-card__method {
        background: rgb(45 212 191 / 0.12);
        color: rgb(94 234 212);
    }

    .subscription-card__muted {
        color: rgb(148 163 184) !important;
    }

    .subscription-card__footer {
        margin-top: auto;
        padding-top: 0.75rem;
        border-top: 1px solid rgb(15 23 42 / 0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .dark .subscription-card__footer {
        border-top-color: rgb(255 255 255 / 0.08);
    }

    .subscription-card__meta {
        font-size: 0.72rem;
        font-weight: 600;
        color: rgb(100 116 139);
    }

    .dark .subscription-card__meta {
        color: rgb(148 163 184);
    }

    .subscription-card__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        background: rgb(13 148 136);
        padding: 0.45rem 0.9rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fff;
        border: none;
        cursor: pointer;
        transition: background 0.15s ease;
        white-space: nowrap;
    }

    .subscription-card__btn:hover:not(:disabled) {
        background: rgb(15 118 110);
    }

    .subscription-card__btn:disabled,
    .subscription-card__btn--disabled {
        background: rgb(148 163 184);
        cursor: not-allowed;
        opacity: 0.85;
    }

    .subscription-card__btn--warn {
        background: rgb(180 83 9);
    }

    .subscription-card__btn--warn:hover:not(:disabled) {
        background: rgb(146 64 14);
    }

    .subscription-card--current {
        border-color: rgb(13 148 136 / 0.55);
    }

    .subscription-card__badge--current {
        background: rgb(5 150 105);
    }

    .subscription-active-banner {
        margin-top: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        border-radius: 0.75rem;
        background: linear-gradient(145deg, #0f766e 0%, #134e4a 55%, #0f172a 100%);
        padding: 0.85rem 1rem;
        color: #ecfeff;
        box-shadow: var(--crm-shadow);
    }

    .subscription-active-banner__label {
        margin: 0;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #99f6e4;
    }

    .subscription-active-banner__value {
        margin: 0.15rem 0 0;
        font-size: 0.9rem;
        font-weight: 700;
        color: #fff;
    }

    .subscription-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border-radius: 1rem;
        background: rgb(248 250 252);
        padding: 2.5rem 1.25rem;
        text-align: center;
        box-shadow: var(--crm-shadow);
    }

    .dark .subscription-empty {
        background: rgb(255 255 255 / 0.04);
    }

    .subscription-empty__icon {
        display: flex;
        height: 3rem;
        width: 3rem;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.35rem;
        border-radius: 0.875rem;
        background: rgb(13 148 136 / 0.1);
        color: rgb(13 148 136);
    }

    .subscription-empty__title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: rgb(15 23 42);
    }

    .dark .subscription-empty__title {
        color: rgb(248 250 252);
    }

    .subscription-empty__hint {
        margin: 0;
        max-width: 20rem;
        font-size: 0.8rem;
        line-height: 1.45;
        color: rgb(100 116 139);
    }

    .dark .subscription-empty__hint {
        color: rgb(148 163 184);
    }
</style>
