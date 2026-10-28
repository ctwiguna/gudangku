export default function Badge({ tone = 'primary', children, icon = null }) {
    return (
        <span className={`badge badge--${tone}`}>
            {icon}
            {children}
        </span>
    );
}
