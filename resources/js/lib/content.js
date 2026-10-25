import { message } from './i18n';

const serviceDefinitions = [
    ['airport', 'services/airport-transfers', 'plane'],
    ['vip', 'services/vip-chauffeur-service', 'crown'],
    ['business', 'services/business-transportation', 'briefcase'],
    ['private', 'services/private-chauffeur-service', 'car'],
    ['city', 'services/city-to-city-transportation', 'route'],
    ['sightseeing', 'services/sightseeing-tours', 'compass'],
];

export function getServices(locale = 'en') {
    return serviceDefinitions.map(([key, slug, icon]) => ({
        slug,
        icon,
        title: message(locale, `service.${key}.title`),
        kicker: message(locale, `service.${key}.kicker`),
        description: message(locale, `service.${key}.description`),
    }));
}

export const fleetDefinitions = ['executive', 'premium', 'group'].map((key) => ({
    key,
    image: {
        executive: '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.07.jpeg',
        premium: '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.05-2.jpeg',
        group: '/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.37-2.jpeg',
    }[key],
}));

export function getFleet(locale = 'en') {
    return fleetDefinitions.map(({ key, image }) => ({
        image,
        label: message(locale, `fleet.${key}.label`),
        title: message(locale, `fleet.${key}.title`),
        description: message(locale, `fleet.${key}.description`),
    }));
}

export const cityGroups = [
    { province: 'Drenthe', cities: [['Assen', 'taxi-assen-naar-schiphol']] },
    {
        province: 'Flevoland',
        cities: [
            ['Almere', 'taxi-almere-naar-schiphol'],
            ['Biddinghuizen', 'taxi-biddinghuizen-naar-schiphol'],
            ['Dronten', 'taxi-dronten-naar-schiphol'],
            ['Emmeloord', 'taxi-emmeloord-naar-schiphol'],
            ['Lelystad', 'taxi-lelystad-naar-schiphol'],
            ['Zeewolde', 'taxi-zeewolde-naar-schiphol'],
        ],
    },
    {
        province: 'Gelderland',
        cities: [
            ['Apeldoorn', 'taxi-apeldoorn-naar-schiphol'],
            ['Arnhem', 'taxi-arnhem-naar-schiphol'],
            ['Ermelo', 'taxi-ermelo-naar-schiphol'],
            ['Lunteren', 'taxi-lunteren-naar-schiphol'],
        ],
    },
    { province: 'Groningen', cities: [['Groningen', 'taxi-groningen-naar-schiphol']] },
    {
        province: 'Noord-Holland',
        cities: [
            ['Bussum', 'taxi-bussum-naar-schiphol'],
            ['Hilversum', 'taxi-hilversum-naar-schiphol'],
            ['Laren', 'taxi-laren-naar-schiphol'],
            ['Naarden', 'taxi-naarden-naar-schiphol'],
            ['Weesp', 'taxi-weesp-naar-schiphol'],
        ],
    },
    {
        province: 'Overijssel',
        cities: [
            ['Giethoorn', 'taxi-giethoorn-naar-schiphol'],
            ['Zwolle', 'taxi-zwolle-naar-schiphol'],
        ],
    },
    {
        province: 'Utrecht',
        cities: [
            ['Amersfoort', 'taxi-amersfoort-naar-schiphol'],
            ['Baarn', 'taxi-baarn-naar-schiphol'],
            ['Soest', 'taxi-soest-naar-schiphol'],
        ],
    },
];

export function getTrustPoints(locale = 'en') {
    return [
        { icon: 'clock', title: message(locale, 'home.trust1'), text: message(locale, 'home.trust1Text') },
        { icon: 'plane', title: message(locale, 'home.trust2'), text: message(locale, 'home.trust2Text') },
        { icon: 'shield', title: message(locale, 'home.trust3'), text: message(locale, 'home.trust3Text') },
        { icon: 'pin', title: message(locale, 'home.trust4'), text: message(locale, 'home.trust4Text') },
    ];
}
