/**
 * Tab sederhana dengan peran ARIA. Dipakai halaman master data
 * yang menggabungkan kategori dan supplier dalam satu halaman.
 */
export default function Tabs({ tabs, active, onChange }) {
    function handleKeyDown(event, index) {
        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
            return;
        }

        event.preventDefault();

        const next = event.key === 'ArrowRight' ? index + 1 : index - 1;
        const wrapped = (next + tabs.length) % tabs.length;

        onChange(tabs[wrapped].key);
    }

    return (
        <div className="tabs" role="tablist" aria-label="Pilihan bagian">
            {tabs.map((tab, index) => {
                const selected = tab.key === active;

                return (
                    <button
                        key={tab.key}
                        type="button"
                        role="tab"
                        id={`tab-${tab.key}`}
                        aria-selected={selected}
                        aria-controls={`panel-${tab.key}`}
                        tabIndex={selected ? 0 : -1}
                        className={selected ? 'tabs__item is-active' : 'tabs__item'}
                        onClick={() => onChange(tab.key)}
                        onKeyDown={(event) => handleKeyDown(event, index)}
                    >
                        {tab.label}
                        {tab.badge ? <span className="tabs__badge">{tab.badge}</span> : null}
                    </button>
                );
            })}
        </div>
    );
}
