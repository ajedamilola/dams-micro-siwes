/**
 * Switchable application modules. Each module owns a slice of the UI:
 * a home route and the Inertia component prefix of its pages.
 * Add entries here as new modules ship; the shell picks up the rest.
 */
export interface AppModule {
    key: string;
    label: string;
    home: string;
    /** Inertia component prefix, e.g. `GeneralLedger/`. */
    componentPrefix: string;
}

export const MODULES: AppModule[] = [
    {
        key: 'general-ledger',
        label: 'General ledger',
        home: '/general-ledger',
        componentPrefix: 'GeneralLedger/',
    },
    {
        key: 'loan',
        label: 'Loan',
        home: '/loans',
        componentPrefix: 'Loan/',
    },
];

export function moduleForComponent(component: string): AppModule {
    return (
        MODULES.find((module) => component.startsWith(module.componentPrefix)) ??
        MODULES[0]
    );
}
