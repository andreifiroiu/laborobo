import 'i18next';
import common from '../locales/en/common.json';
import auth from '../locales/en/auth.json';
import settings from '../locales/en/settings.json';
import dashboard from '../locales/en/dashboard.json';

declare module 'i18next' {
    interface CustomTypeOptions {
        defaultNS: 'common';
        resources: {
            common: typeof common;
            auth: typeof auth;
            settings: typeof settings;
            dashboard: typeof dashboard;
        };
    }
}
