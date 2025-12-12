/**
 * Product Video Provider Handler
 * Manages dynamic behavior for video provider selection
 */
(function() {
    'use strict';

    const PROVIDERS = {
        youtube: {
            name: 'YouTube',
            placeholder: 'Ex: https://www.youtube.com/embed/VIDEO_ID',
            hint: '(Используйте embed ссылку, не прямую)',
            examples: [
                'https://www.youtube.com/embed/5R06LRdUCSE',
                'https://youtube.com/embed/VIDEO_ID'
            ]
        },
        kinescope: {
            name: 'Kinescope',
            placeholder: 'Ex: https://kinescope.io/VIDEO_ID',
            hint: '(Можно вставить любой формат ссылки)',
            examples: [
                'https://kinescope.io/VIDEO_ID',
                'https://kinescope.io/embed/VIDEO_ID',
                'VIDEO_ID (только ID)'
            ]
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('video_provider');
        const urlInput = document.getElementById('video_url');
        const hintText = document.getElementById('video_url_hint');
        const examplesBlock = document.getElementById('video_url_examples');
        const examplesLabel = document.getElementById('examples_label');
        const examplesList = document.getElementById('examples_list');

        if (!providerSelect || !urlInput) {
            return;
        }

        // Handle provider change
        providerSelect.addEventListener('change', function() {
            const provider = this.value;

            if (!provider) {
                // No provider selected
                urlInput.disabled = true;
                urlInput.placeholder = 'Выберите провайдер сначала';
                urlInput.value = '';
                hintText.textContent = 'Выберите провайдер сначала';
                examplesBlock.style.display = 'none';
                return;
            }

            const config = PROVIDERS[provider];
            if (!config) {
                console.error('Unknown provider:', provider);
                return;
            }

            // Enable input and update UI
            urlInput.disabled = false;
            urlInput.placeholder = config.placeholder;
            hintText.textContent = config.hint;

            // Update examples
            examplesLabel.textContent = `Примеры ссылок для ${config.name}:`;
            examplesList.innerHTML = config.examples
                .map(example => `<li>${example}</li>`)
                .join('');
            examplesBlock.style.display = 'block';

            // Clear input if switching providers
            if (urlInput.value && urlInput.dataset.currentProvider !== provider) {
                if (confirm('Сменить провайдер? Текущая ссылка будет очищена.')) {
                    urlInput.value = '';
                } else {
                    // Revert provider selection
                    providerSelect.value = urlInput.dataset.currentProvider || '';
                    return;
                }
            }

            urlInput.dataset.currentProvider = provider;
        });

        // Trigger change event if provider already selected (edit mode)
        if (providerSelect.value) {
            providerSelect.dispatchEvent(new Event('change'));
        }
    });
})();
