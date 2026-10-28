const base = {
    width: 24,
    height: 24,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.8,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
    'aria-hidden': 'true',
    focusable: 'false',
};

export function DashboardIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M3 10.5 12 3l9 7.5" />
            <path d="M5 9.5V21h14V9.5" />
            <path d="M10 21v-6h4v6" />
        </svg>
    );
}

export function WalletIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H18a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3z" />
            <path d="M3 9h14" />
            <circle cx="17" cy="14" r="1.2" />
        </svg>
    );
}

export function PlusIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M12 5v14M5 12h14" />
        </svg>
    );
}

export function BoxIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M21 8.5 12 3 3 8.5v7L12 21l9-5.5z" />
            <path d="m3 8.5 9 5.5 9-5.5" />
            <path d="M12 21v-7" />
        </svg>
    );
}

export function SettingsIcon(props) {
    return (
        <svg {...base} {...props}>
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2 2 2 0 1 1-4 0 1.7 1.7 0 0 0-2.9-1.2l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 15a2 2 0 1 1 0-4 1.7 1.7 0 0 0 1.4-2.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 4.1a2 2 0 1 1 4 0 1.7 1.7 0 0 0 2.8 1.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1A1.7 1.7 0 0 0 21 11a2 2 0 1 1 0 4z" />
        </svg>
    );
}

export function ClipboardCheckIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1z" />
            <path d="M8 6H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-2" />
            <path d="m9 13.5 2 2 4-4" />
        </svg>
    );
}

export function TruckIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M3 7h10v9H3z" />
            <path d="M13 10h4l3 3v3h-7z" />
            <circle cx="7" cy="18" r="1.6" />
            <circle cx="17" cy="18" r="1.6" />
        </svg>
    );
}

export function UserIcon(props) {
    return (
        <svg {...base} {...props}>
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 20a7 7 0 0 1 14 0" />
        </svg>
    );
}

export function AlertIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M12 4 2.8 20h18.4z" />
            <path d="M12 10v4" />
            <circle cx="12" cy="17.2" r="0.9" fill="currentColor" stroke="none" />
        </svg>
    );
}

export function CheckIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="m5 13 4.5 4.5L19 7" />
        </svg>
    );
}

export function ChevronLeftIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="m14 6-6 6 6 6" />
        </svg>
    );
}

export function ChevronRightIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="m10 6 6 6-6 6" />
        </svg>
    );
}

export function CloseIcon(props) {
    return (
        <svg {...base} {...props}>
            <path d="M6 6l12 12M18 6 6 18" />
        </svg>
    );
}
