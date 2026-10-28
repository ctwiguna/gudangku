import Toast from '../Components/Toast';

export default function AuthLayout({ title, description, children }) {
    return (
        <div className="auth-shell">
            <Toast />

            <main className="auth-shell__inner">
                <header className="auth-shell__brand">
                    <p className="badge">gudangku</p>
                    <h1 className="auth-shell__title">{title}</h1>
                    {description ? <p className="muted">{description}</p> : null}
                </header>

                <section className="card">{children}</section>

                <p className="muted auth-shell__foot">
                    Pencatatan dan stock opname inventaris outlet.
                </p>
            </main>
        </div>
    );
}
