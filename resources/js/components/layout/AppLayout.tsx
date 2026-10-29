import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/arc/badge/badge';
import { DropdownMenu } from '@/components/arc/dropdown-menu/dropdown-menu';
import { UserMenu } from '@/components/arc/user-menu/user-menu';
import { BusinessDateControl } from '@/components/layout/BusinessDateControl';
import {
    ArrowLeftRight,
    BookCopy,
    Building,
    ChartColumn,
    FileText,
    HandCoins,
    Landmark,
    Layers,
    LayoutDashboard,
    Menu,
    Package,
    PanelLeftClose,
    PanelLeftOpen,
    Scale,
    ScrollText,
    Settings,
    Users,
    X,
} from 'lucide-react';
import { moduleForComponent, MODULES, type AppModule } from '@/lib/modules';
import { useThemePreference } from '@/lib/theme';
import type { CompanyOption } from '@/types/ledger';
import styles from './layout.module.css';

const ACCOUNTS_HREF = '/general-ledger/accounts';
const OVERVIEW_HREF = '/general-ledger';
const TRANSACTIONS_HREF = '/general-ledger/transactions';
const TRIAL_BALANCE_HREF = '/general-ledger/trial-balance';
const BALANCE_SHEET_HREF = '/general-ledger/balance-sheet';
const SETTINGS_HREF = '/general-ledger/settings';
const LOAN_OVERVIEW_HREF = '/loans';
const LOAN_CUSTOMERS_HREF = '/loans/customers';
const LOAN_PRODUCTS_HREF = '/loans/products';
const LOAN_LOANS_HREF = '/loans/loans';

interface NavItem {
    label: string;
    icon: ReactNode;
    /** Omitted for sections that are not available in this sample. */
    href?: string;
    /** Inertia component that marks the item current. */
    activeOn?: string;
    /** Component prefix that keeps the item current on child pages. */
    activePrefix?: string;
}

interface NavSection {
    label: string;
    items: NavItem[];
}

const NAV_BY_MODULE: Record<string, NavSection[]> = {
    loan: [
        {
            label: 'Workspace',
            items: [
                {
                    label: 'Overview',
                    icon: <LayoutDashboard size={17} strokeWidth={1.75} />,
                    href: LOAN_OVERVIEW_HREF,
                    activeOn: 'Loan/Overview',
                },
            ],
        },
        {
            label: 'Loan',
            items: [
                {
                    label: 'Customers',
                    icon: <Users size={17} strokeWidth={1.75} />,
                    href: LOAN_CUSTOMERS_HREF,
                    activeOn: 'Loan/Customers/Index',
                    activePrefix: 'Loan/Customers',
                },
                {
                    label: 'Products',
                    icon: <Package size={17} strokeWidth={1.75} />,
                    href: LOAN_PRODUCTS_HREF,
                    activeOn: 'Loan/Products/Index',
                    activePrefix: 'Loan/Products',
                },
                {
                    label: 'Loans',
                    icon: <HandCoins size={17} strokeWidth={1.75} />,
                    href: LOAN_LOANS_HREF,
                    activeOn: 'Loan/Loans/Index',
                    activePrefix: 'Loan/Loans',
                },
            ],
        },
    ],
    'general-ledger': [
        {
            label: 'Workspace',
            items: [
                {
                    label: 'Overview',
                    icon: <LayoutDashboard size={17} strokeWidth={1.75} />,
                    href: OVERVIEW_HREF,
                    activeOn: 'GeneralLedger/Overview',
                },
            ],
        },
        {
            label: 'General ledger',
            items: [
                {
                    label: 'Chart of accounts',
                    icon: <BookCopy size={17} strokeWidth={1.75} />,
                    href: ACCOUNTS_HREF,
                    activeOn: 'GeneralLedger/Accounts/Index',
                    activePrefix: 'GeneralLedger/Accounts',
                },
                {
                    label: 'Transactions',
                    icon: <ArrowLeftRight size={17} strokeWidth={1.75} />,
                    href: TRANSACTIONS_HREF,
                    activeOn: 'GeneralLedger/Transactions/Index',
                    activePrefix: 'GeneralLedger/Transactions',
                },
                {
                    label: 'Ledgers',
                    icon: <Landmark size={17} strokeWidth={1.75} />,
                },
                {
                    label: 'Trial balance',
                    icon: <Scale size={17} strokeWidth={1.75} />,
                    href: TRIAL_BALANCE_HREF,
                    activeOn: 'GeneralLedger/Reports/TrialBalance',
                },
                {
                    label: 'Balance sheet',
                    icon: <FileText size={17} strokeWidth={1.75} />,
                    href: BALANCE_SHEET_HREF,
                    activeOn: 'GeneralLedger/Reports/BalanceSheet',
                },
            ],
        },
        {
            label: 'Reports',
            items: [
                {
                    label: 'Financial statements',
                    icon: <ChartColumn size={17} strokeWidth={1.75} />,
                },
                {
                    label: 'Audit trail',
                    icon: <ScrollText size={17} strokeWidth={1.75} />,
                },
            ],
        },
        {
            label: 'System',
            items: [
                {
                    label: 'Settings',
                    icon: <Settings size={17} strokeWidth={1.75} />,
                    href: SETTINGS_HREF,
                    activeOn: 'GeneralLedger/Settings/Index',
                },
            ],
        },
    ],
};

function Brand() {
    return (
        <div className={styles.brand}>
            <span className={styles.mark} aria-hidden="true">
                DM
            </span>
            <span className={styles.brandText}>
                <span className={styles.brandName}>Dams Micro</span>
                <span className={styles.brandCaption}>Finance suite</span>
            </span>
        </div>
    );
}

function ModuleSwitcher({ active }: { active: AppModule }) {
    return (
        <div className={styles.moduleSwitch}>
            <DropdownMenu
                label={active.label}
                icon={<Layers size={15} aria-hidden="true" />}
                items={MODULES.map((module) => ({
                    label: module.label,
                    disabled: module.key === active.key,
                    onSelect: () => router.visit(module.home),
                }))}
            />
        </div>
    );
}

function SidebarNav({
    sections,
    onNavigate,
    iconOnly = false,
}: {
    sections: NavSection[];
    onNavigate?: () => void;
    iconOnly?: boolean;
}) {
    const { component } = usePage();
    return (
        <nav aria-label="Primary" className={styles.nav}>
            {sections.map((section, sectionIndex) => (
                <div key={`${section.label}-${sectionIndex}`} className={styles.group}>
                    <h2 className={styles.groupLabel}>{section.label}</h2>
                    <ul className={styles.items}>
                        {section.items.map((item) => {
                            const active =
                                item.href !== undefined &&
                                (component === item.activeOn ||
                                    (item.activePrefix !== undefined &&
                                        component.startsWith(
                                            item.activePrefix,
                                        )));
                            return (
                                <li key={item.label}>
                                    {item.href ? (
                                        <Link
                                            href={item.href}
                                            className={styles.item}
                                            data-active={active || undefined}
                                            aria-current={
                                                active ? 'page' : undefined
                                            }
                                            title={
                                                iconOnly
                                                    ? item.label
                                                    : undefined
                                            }
                                            onClick={onNavigate}
                                        >
                                            <span
                                                className={styles.itemIcon}
                                                aria-hidden="true"
                                            >
                                                {item.icon}
                                            </span>
                                            <span className={styles.itemLabel}>
                                                {item.label}
                                            </span>
                                        </Link>
                                    ) : (
                                        <span
                                            className={styles.item}
                                            aria-disabled="true"
                                            title={`${item.label} is not available in this sample`}
                                        >
                                            <span
                                                className={styles.itemIcon}
                                                aria-hidden="true"
                                            >
                                                {item.icon}
                                            </span>
                                            <span className={styles.itemLabel}>
                                                {item.label}
                                            </span>
                                        </span>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </nav>
    );
}

interface AppLayoutProps {
    title: string;
    section?: string;
    children: ReactNode;
}

function readStoredCompany(): string | null {
    try {
        if (typeof window === 'undefined' || !window.localStorage) {
            return null;
        }
        return window.localStorage.getItem('dams.company');
    } catch {
        return null;
    }
}

export function AppLayout({ title, section, children }: AppLayoutProps) {
    const [navOpen, setNavOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(false);
    const { preference, setPreference } = useThemePreference();
    const { companies } = usePage().props as unknown as {
        companies?: CompanyOption[];
    };
    const companyOptions = companies ?? [];
    const [companyCode, setCompanyCode] = useState<string>(() => {
        const stored = readStoredCompany();
        if (stored && companyOptions.some((row) => row.code === stored)) {
            return stored;
        }
        return companyOptions[0]?.code ?? 'DMFB';
    });
    const company =
        companyOptions.find((row) => row.code === companyCode) ??
        companyOptions[0];

    function selectCompany(code: string): void {
        setCompanyCode(code);
        try {
            window.localStorage.setItem('dams.company', code);
        } catch {
            // Storage unavailable; the picker still works for this visit.
        }
    }
    const menuButtonRef = useRef<HTMLButtonElement>(null);
    const closeButtonRef = useRef<HTMLButtonElement>(null);
    const wasOpen = useRef(false);

    useEffect(() => {
        if (navOpen && !wasOpen.current) {
            closeButtonRef.current?.focus();
        }
        if (!navOpen && wasOpen.current) {
            menuButtonRef.current?.focus();
        }
        wasOpen.current = navOpen;
    }, [navOpen]);

    useEffect(() => {
        if (!navOpen || typeof document === 'undefined') {
            return undefined;
        }
        const previous = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        const onKey = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                setNavOpen(false);
            }
        };
        document.addEventListener('keydown', onKey);
        return () => {
            document.body.style.overflow = previous;
            document.removeEventListener('keydown', onKey);
        };
    }, [navOpen]);

    const closeNav = (): void => setNavOpen(false);
    const { component } = usePage();
    const activeModule = moduleForComponent(component);
    const sections = NAV_BY_MODULE[activeModule.key] ?? [];

    return (
        <div className={styles.shell} data-collapsed={collapsed || undefined}>
            <aside id="app-sidebar" className={styles.sidebar}>
                <Brand />
                <ModuleSwitcher active={activeModule} />
                <SidebarNav sections={sections} iconOnly={collapsed} />
                <p className={styles.sideFoot}>Sample workspace</p>
                <button
                    type="button"
                    className={styles.railToggle}
                    aria-label={
                        collapsed ? 'Expand sidebar' : 'Collapse sidebar'
                    }
                    aria-expanded={!collapsed}
                    aria-controls="app-sidebar"
                    onClick={() => setCollapsed((value) => !value)}
                >
                    {collapsed ? (
                        <PanelLeftOpen
                            style={{ zIndex: 21 }}
                            size={16}
                            strokeWidth={1.75}
                            aria-hidden="true"
                        />
                    ) : (
                        <PanelLeftClose
                            style={{ zIndex: 21 }}
                            size={16}
                            strokeWidth={1.75}
                            aria-hidden="true"
                        />
                    )}
                </button>
            </aside>

            <div className={styles.main}>
                <header className={styles.topbar}>
                    <button
                        ref={menuButtonRef}
                        type="button"
                        className={styles.menuButton}
                        aria-label="Open navigation"
                        aria-expanded={navOpen}
                        aria-controls="app-nav-drawer"
                        onClick={() => setNavOpen(true)}
                    >
                        <Menu size={20} strokeWidth={1.75} aria-hidden="true" />
                    </button>
                    <nav className={styles.crumbs} aria-label="Breadcrumb">
                        <ol>
                            {section ? (
                                <>
                                    <li className={styles.hideSmall}>
                                        {section}
                                    </li>
                                    <li
                                        aria-hidden="true"
                                        className={styles.hideSmall}
                                    >
                                        /
                                    </li>
                                </>
                            ) : null}
                            <li aria-current="page">{title}</li>
                        </ol>
                    </nav>
                    <div className={styles.topbarRight}>
                        <BusinessDateControl />
                        {/* Global company picker. Selecting a company does
                            not scope data yet; per-page filters do that. */}
                        {company ? (
                            <DropdownMenu
                                label={company.code}
                                icon={<Building size={15} aria-hidden="true" />}
                                items={companyOptions.map((option) => ({
                                    label: option.name,
                                    onSelect: () => selectCompany(option.code),
                                }))}
                            />
                        ) : null}
                        <Badge tone="neutral" size="sm">
                            Sample
                        </Badge>
                        <UserMenu
                            user={{
                                name: 'Aje Damilola',
                                email: 'aje@dams-micro.test',
                            }}
                            showName
                            theme={preference}
                            onThemeChange={setPreference}
                            // Sample shell: no auth endpoints yet.
                            onSignOut={() => undefined}
                        />
                    </div>
                </header>
                <div className={styles.content}>{children}</div>
            </div>

            <button
                type="button"
                tabIndex={-1}
                aria-label="Close navigation"
                className={styles.backdrop}
                data-open={navOpen || undefined}
                onClick={closeNav}
            />
            <div
                id="app-nav-drawer"
                role="dialog"
                aria-modal="true"
                aria-label="Navigation"
                className={styles.drawer}
                data-open={navOpen || undefined}
            >
                <div className={styles.drawerHead}>
                    <Brand />
                    <button
                        ref={closeButtonRef}
                        type="button"
                        className={styles.closeButton}
                        aria-label="Close navigation"
                        onClick={closeNav}
                    >
                        <X size={20} strokeWidth={1.75} aria-hidden="true" />
                    </button>
                </div>
                <ModuleSwitcher active={activeModule} />
                <SidebarNav sections={sections} onNavigate={closeNav} />
                <p className={styles.sideFoot}>Sample workspace</p>
            </div>
        </div>
    );
}
