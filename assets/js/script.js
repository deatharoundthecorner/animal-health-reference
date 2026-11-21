document.addEventListener('DOMContentLoaded', () => {
    // Admin "Add animal" page: optionally prefill fields from the public iNaturalist API.
    // Only the typed common name is sent; nothing is saved until the form is submitted.
    const autoFetchBtn = document.querySelector('#autoFetchBtn');
    if (!autoFetchBtn) {
        return;
    }

    autoFetchBtn.addEventListener('click', async () => {
        const nameField = document.querySelector('#common_name');
        const scientificField = document.querySelector('#scientific_name');
        const statusField = document.querySelector('#conservation_status');
        const habitatField = document.querySelector('#habitat');
        const descField = document.querySelector('#description');
        const resultBox = document.querySelector('#apiResult');

        if (!nameField || !resultBox) {
            return;
        }

        const query = nameField.value.trim();
        if (!query) {
            resultBox.className = 'alert alert-warning mt-3';
            resultBox.textContent = 'Enter an animal common name first.';
            return;
        }

        resultBox.className = 'alert alert-info mt-3';
        resultBox.textContent = 'Fetching species data from the public iNaturalist API...';

        try {
            const response = await fetch(`https://api.inaturalist.org/v1/taxa/autocomplete?q=${encodeURIComponent(query)}&per_page=1`);
            if (!response.ok) {
                throw new Error('API request failed');
            }

            const payload = await response.json();
            const item = payload?.results?.[0];

            if (!item) {
                resultBox.className = 'alert alert-warning mt-3';
                resultBox.textContent = 'No API data found. You can continue with manual entry.';
                return;
            }

            if (scientificField && !scientificField.value && item.name) {
                scientificField.value = item.name;
            }
            if (statusField && !statusField.value && item.conservation_status?.status_name) {
                statusField.value = item.conservation_status.status_name;
            }
            if (habitatField && !habitatField.value && item.iconic_taxon_name) {
                habitatField.value = `Typical ${item.iconic_taxon_name.toLowerCase()} habitat`;
            }
            if (descField && !descField.value && item.wikipedia_summary) {
                // The summary is HTML from Wikipedia; keep plain text only.
                const holder = document.createElement('div');
                holder.innerHTML = item.wikipedia_summary;
                descField.value = holder.textContent || '';
            }

            resultBox.className = 'alert alert-success mt-3';
            resultBox.textContent = 'Data loaded where available. Review every field and verify it against a reliable source before saving.';
        } catch (error) {
            resultBox.className = 'alert alert-warning mt-3';
            resultBox.textContent = 'API unavailable. Please continue with manual data entry.';
        }
    });
});
