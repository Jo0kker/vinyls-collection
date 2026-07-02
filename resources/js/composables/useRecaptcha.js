import { usePage } from '@inertiajs/vue3';

let recaptchaScriptPromise = null;

const loadRecaptchaScript = (siteKey) => {
    if (typeof window === 'undefined') {
        return Promise.resolve();
    }

    if (window.grecaptcha?.execute) {
        return Promise.resolve();
    }

    if (!recaptchaScriptPromise) {
        recaptchaScriptPromise = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-recaptcha-v3="true"]');

            if (existingScript) {
                existingScript.addEventListener('load', resolve, { once: true });
                existingScript.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
            script.async = true;
            script.defer = true;
            script.dataset.recaptchaV3 = 'true';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    return recaptchaScriptPromise;
};

export function useRecaptcha() {
    const page = usePage();

    const isEnabled = () => Boolean(page.props.recaptcha?.enabled && page.props.recaptcha?.siteKey);

    const executeRecaptcha = async (action) => {
        if (!isEnabled()) {
            return null;
        }

        try {
            const siteKey = page.props.recaptcha.siteKey;
            await loadRecaptchaScript(siteKey);

            return await new Promise((resolve, reject) => {
                window.grecaptcha.ready(() => {
                    window.grecaptcha
                        .execute(siteKey, { action })
                        .then(resolve)
                        .catch(reject);
                });
            });
        } catch (error) {
            console.warn('Impossible de charger reCAPTCHA.', error);

            return '';
        }
    };

    return {
        isRecaptchaEnabled: isEnabled,
        executeRecaptcha,
    };
}
