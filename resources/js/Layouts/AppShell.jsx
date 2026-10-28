import { usePage } from '@inertiajs/react';
import PageHeader from '../Components/PageHeader';
import ReminderBanner from '../Components/ReminderBanner';
import Toast from '../Components/Toast';
import BottomNav from './BottomNav';

/**
 * Kerangka halaman: header, konten, bottom navigation, dan toast.
 * Lebar konten maksimum 720 piksel dan padding bawah 88 piksel
 * supaya konten tidak tertutup navigasi.
 */
export default function AppShell({
    title,
    subtitle = null,
    badge = null,
    backHref = null,
    actions = null,
    reminder = false,
    reminderTitle = 'Stock opname minggu ini belum dilakukan.',
    reminderDescription = 'Lakukan sebelum Minggu agar stok sistem tetap akurat.',
    reminderHref = '/stock-opnames',
    reminderActionLabel = 'Isi opname',
    children,
}) {
    const { auth } = usePage().props;
    const role = auth?.user?.is_owner ? 'owner' : 'manager';

    return (
        <div className="app-shell">
            <div className="app-shell__content">
                <Toast />

                <PageHeader
                    title={title}
                    subtitle={subtitle}
                    badge={badge}
                    backHref={backHref}
                    actions={actions}
                />

                {reminder ? (
                    <ReminderBanner
                        title={reminderTitle}
                        description={reminderDescription}
                        actionLabel={reminderActionLabel}
                        actionHref={reminderHref}
                    />
                ) : null}

                {children}
            </div>

            <BottomNav role={role} reminder={reminder} reminderHref={reminderHref} />
        </div>
    );
}
