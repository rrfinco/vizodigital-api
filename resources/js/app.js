import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        dark: localStorage.getItem('portal-theme') === 'dark'
            || (!localStorage.getItem('portal-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),

        init() {
            this.apply();
        },

        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('portal-theme', this.dark ? 'dark' : 'light');
            this.apply();
        },

        apply() {
            document.documentElement.classList.toggle('dark', this.dark);
        },
    });

    Alpine.data('docsSearch', (config = {}) => ({
        url: config.url,
        version: config.version,
        minChars: config.minChars ?? 2,
        debounceMs: config.debounceMs ?? 250,
        query: '',
        results: [],
        open: false,
        loading: false,
        activeIndex: -1,
        requestId: 0,

        async search() {
            const q = this.query.trim();
            this.activeIndex = -1;

            if (q.length < this.minChars) {
                this.results = [];
                this.open = false;
                this.loading = false;

                return;
            }

            const id = ++this.requestId;
            this.loading = true;
            this.open = true;

            try {
                const { data } = await window.axios.get(this.url, {
                    params: {
                        q,
                        version: this.version || undefined,
                    },
                });

                if (id !== this.requestId) {
                    return;
                }

                this.results = data.results ?? [];
                this.activeIndex = this.results.length ? 0 : -1;
            } catch (error) {
                if (id !== this.requestId) {
                    return;
                }

                this.results = [];
            } finally {
                if (id === this.requestId) {
                    this.loading = false;
                }
            }
        },

        move(delta) {
            if (! this.results.length) {
                return;
            }

            const next = this.activeIndex + delta;
            this.activeIndex = (next + this.results.length) % this.results.length;
        },

        go() {
            const result = this.results[this.activeIndex];
            if (result?.url) {
                window.location.href = result.url;
            }
        },

        close() {
            this.open = false;
            this.activeIndex = -1;
        },
    }));
});

Alpine.start();
