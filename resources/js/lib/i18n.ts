import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

// Import translation files
import enCommon from '../locales/en/common.json';
import enAuth from '../locales/en/auth.json';
import enSettings from '../locales/en/settings.json';
import enDashboard from '../locales/en/dashboard.json';

import esCommon from '../locales/es/common.json';
import esAuth from '../locales/es/auth.json';
import esSettings from '../locales/es/settings.json';
import esDashboard from '../locales/es/dashboard.json';

import frCommon from '../locales/fr/common.json';
import frAuth from '../locales/fr/auth.json';
import frSettings from '../locales/fr/settings.json';
import frDashboard from '../locales/fr/dashboard.json';

import deCommon from '../locales/de/common.json';
import deAuth from '../locales/de/auth.json';
import deSettings from '../locales/de/settings.json';
import deDashboard from '../locales/de/dashboard.json';

import roCommon from '../locales/ro/common.json';
import roAuth from '../locales/ro/auth.json';
import roSettings from '../locales/ro/settings.json';
import roDashboard from '../locales/ro/dashboard.json';

const resources = {
    en: {
        common: enCommon,
        auth: enAuth,
        settings: enSettings,
        dashboard: enDashboard,
    },
    es: {
        common: esCommon,
        auth: esAuth,
        settings: esSettings,
        dashboard: esDashboard,
    },
    fr: {
        common: frCommon,
        auth: frAuth,
        settings: frSettings,
        dashboard: frDashboard,
    },
    de: {
        common: deCommon,
        auth: deAuth,
        settings: deSettings,
        dashboard: deDashboard,
    },
    ro: {
        common: roCommon,
        auth: roAuth,
        settings: roSettings,
        dashboard: roDashboard,
    },
};

i18n
    .use(LanguageDetector)
    .use(initReactI18next)
    .init({
        resources,
        fallbackLng: 'en',
        defaultNS: 'common',
        ns: ['common', 'auth', 'settings', 'dashboard'],

        interpolation: {
            escapeValue: false, // React already escapes
        },

        detection: {
            order: ['localStorage', 'cookie', 'navigator'],
            caches: ['localStorage', 'cookie'],
            lookupCookie: 'language',
            lookupLocalStorage: 'language',
            cookieMinutes: 525600, // 365 days
        },

        react: {
            useSuspense: false, // Important for SSR compatibility
        },
    });

export default i18n;
