import { InjectionToken } from '@angular/core';

export interface KinintelModuleConfig {
    backendURL: string;
    guestURL?: string;
    externalURL?: string;
    tagLabel?: string;
    tagMenuLabel?: string;
    dataSearchTypeMapping?: any;
}

export const KININTEL_MODULE_CONFIG = new InjectionToken<KinintelModuleConfig>('KININTEL_MODULE_CONFIG');
