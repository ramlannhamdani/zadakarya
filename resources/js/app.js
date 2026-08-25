import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Auto-isi slug dari judul. Berhenti mengikuti saat slug diketik manual,
// aktif lagi saat slug dikosongkan. Pada data lama (locked) slug tidak
// diubah otomatis agar URL yang sudah terindeks tetap stabil.
Alpine.data('slugger', (title = '', slug = '', locked = false) => ({
    title: title || '',
    slug: slug || '',
    slugTouched: false,

    init() {
        this.slugTouched =
            (locked && this.slug !== '') ||
            (this.slug !== '' && this.slug !== this.slugify(this.title));
    },

    slugify(value) {
        return (value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s_-]/g, '')
            .trim()
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },

    syncSlug() {
        if (!this.slugTouched) this.slug = this.slugify(this.title);
    },

    touchSlug() {
        this.slugTouched = this.slug !== '';
    },
}));

// Media picker ala WordPress: input file biasa + tombol "Pilih dari Galeri"
// yang membuka popup berisi grid galeri dan upload langsung ke galeri.
Alpine.data('mediaPicker', ({ pickerUrl, uploadUrl, csrf, multiple = false }) => ({
    openModal: false,
    items: [],
    loaded: false,
    selected: [],
    picked: [],
    uploading: false,

    async open() {
        this.openModal = true;
        this.selected = this.picked.map((p) => p.id);
        if (!this.loaded) await this.load();
    },

    async load() {
        try {
            const res = await fetch(pickerUrl, { headers: { Accept: 'application/json' } });
            this.items = await res.json();
            this.loaded = true;
        } catch (e) {
            this.items = [];
        }
    },

    toggle(id) {
        if (multiple) {
            this.selected = this.selected.includes(id)
                ? this.selected.filter((i) => i !== id)
                : [...this.selected, id];
        } else {
            this.selected = this.selected.includes(id) ? [] : [id];
        }
    },

    async upload(event) {
        const files = event.target.files;
        if (!files.length) return;
        this.uploading = true;
        const form = new FormData();
        [...files].forEach((f) => form.append('photos[]', f));
        try {
            const res = await fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: form,
            });
            if (!res.ok) throw new Error();
            const created = await res.json();
            this.items = [...created, ...this.items];
            created.forEach((c) => this.toggle(c.id));
        } catch (e) {
            alert('Upload gagal. Periksa format (JPG/PNG/WebP) dan ukuran file (maks 8 MB).');
        }
        this.uploading = false;
        event.target.value = '';
    },

    use() {
        this.picked = this.items.filter((i) => this.selected.includes(i.id));
        if (this.$refs.file) this.$refs.file.value = '';
        this.openModal = false;
    },

    clearPicks() {
        this.picked = [];
        this.selected = [];
    },

    onFileChange() {
        this.picked = [];
        this.selected = [];
    },
}));

// Scroll reveal: elemen [data-reveal] muncul saat masuk viewport; anak
// [data-reveal-stagger] muncul berurutan (70ms per item, diulang tiap 6).
const initReveal = () => {
    document.querySelectorAll('[data-reveal-stagger]').forEach((container) => {
        [...container.children].forEach((child, i) => {
            child.setAttribute('data-reveal', '');
            child.style.transitionDelay = `${(i % 6) * 70}ms`;
        });
    });

    const targets = document.querySelectorAll('[data-reveal]');
    if (!('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -6% 0px' });

    targets.forEach((el) => io.observe(el));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReveal);
} else {
    initReveal();
}

Alpine.start();