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

Alpine.start();
