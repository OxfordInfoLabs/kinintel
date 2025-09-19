export interface KinintelModuleConfig {
    backendURL: string;
    guestURL?: string;
    externalURL?: string;
    tagLabel?: string;
    tagMenuLabel?: string;
    dataSearchTypeMapping?: any;
}

import { InjectionToken } from '@angular/core';

export const KININTEL_CONFIG = new InjectionToken<KinintelModuleConfig>('KinintelModuleConfig');
