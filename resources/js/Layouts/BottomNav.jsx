import { Link, usePage } from '@inertiajs/react';
import {
    BoxIcon,
    ClipboardCheckIcon,
    DashboardIcon,
    PlusIcon,
    SettingsIcon,
    UserIcon,
    WalletIcon,
} from '../Components/Icons';

/**
 * Bottom navigation 5 slot, susunannya berbeda per role.
 * Owner: Dashboard, Pengeluaran, Catat, Gudang, Pengaturan.
 * Manajer: Dashboard, Stok, Terima, Opname, Akun.
 * Slot Opname memakai badge pengingat selama opname minggu ini belum ada.
 */
const NAV = {
    owner: [
        { key: 'dashboard', label: 'Dashboard', href: '/dashboard', icon: DashboardIcon },
        { key: 'expenses', label: 'Pengeluaran', href: '/expenses', icon: WalletIcon },
        { key: 'create', label: 'Catat', href: '/expenses/create', icon: PlusIcon, primary: true },
        { key: 'warehouse', label: 'Gudang', href: '/warehouse', icon: BoxIcon },
        { key: 'settings', label: 'Pengaturan', href: '/settings', icon: SettingsIcon },
    ],
    manager: [
        { key: 'dashboard', label: 'Dashboard', href: '/dashboard', icon: DashboardIcon },
        { key: 'products', label: 'Stok', href: '/products', icon: BoxIcon },
        { key: 'receipts', label: 'Terima', href: '/receipts/create', icon: PlusIcon, primary: true },
        { key: 'opname', label: 'Opname', href: '/stock-opnames', icon: ClipboardCheckIcon, badge: true },
        { key: 'account', label: 'Akun', href: '/profile', icon: UserIcon },
    ],
};

export default function BottomNav({ role = 'manager', reminder = false, reminderHref = '/stock-opnames' }) {
    const { url } = usePage();
    const items = NAV[role] ?? NAV.manager;

    function isActive(href) {
        if (url === href) {
            return true;
        }

        // Dashboard hanya aktif tepat di /dashboard, bukan di semua halaman.
        if (href === '/dashboard') {
            return false;
        }

        return url.startsWith(`${href}/`) || url.startsWith(href);
    }

    return (
        <nav className="bottom-nav" aria-label="Navigasi utama">
            <ul className="bottom-nav__list">
                {items.map((item) => {
                    const Icon = item.icon;
                    const active = isActive(item.href);
                    const showBadge = item.badge && reminder;

                    return (
                        <li key={item.key} className="bottom-nav__cell">
                            <Link
                                href={item.href}
                                className={[
                                    'bottom-nav__item',
                                    active ? 'is-active' : '',
                                    item.primary ? 'is-primary' : '',
                                ]
                                    .filter(Boolean)
                                    .join(' ')}
                                aria-current={active ? 'page' : undefined}
                                aria-label={
                                    showBadge ? `${item.label}, opname minggu ini belum dilakukan` : item.label
                                }
                            >
                                <span className="bottom-nav__icon">
                                    <Icon />

                                    {showBadge ? <span className="bottom-nav__badge" aria-hidden="true" /> : null}
                                </span>

                                <span className="bottom-nav__label">{item.label}</span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
