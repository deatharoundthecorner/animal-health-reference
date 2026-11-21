document.addEventListener('DOMContentLoaded', () => {
    // Ask for confirmation before submitting forms that carry data-confirm="message".
    document.addEventListener('submit', (event) => {
        const message = event.target.dataset ? event.target.dataset.confirm : '';
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    // Click an existing symptom chip to append it to the symptoms text area.
    document.querySelectorAll('[data-append-to]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const target = document.getElementById(chip.dataset.appendTo);
            if (!target) {
                return;
            }
            const value = chip.textContent.trim();
            const existing = target.value
                .split(/[,\n]+/)
                .map((item) => item.trim().toLowerCase());
            if (existing.includes(value.toLowerCase())) {
                return;
            }
            target.value = target.value.trim() === '' ? value : `${target.value.trim().replace(/,$/, '')}, ${value}`;
        });
    });
});
