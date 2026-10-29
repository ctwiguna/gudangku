import Skeleton from './Skeleton';

/**
 * Tabel data dengan caption untuk pembaca layar.
 * Di layar di bawah 640 piksel tabel boleh di-scroll horizontal
 * dan kolom pertama tetap menempel di kiri.
 */
export default function DataTable({
    caption,
    columns,
    rows,
    rowKey = (row) => row.id,
    renderCell,
    loading = false,
    loadingRows = 4,
    empty = null,
    rowClassName = () => null,
}) {
    if (loading) {
        return (
            <div className="data-table__loading">
                <Skeleton lines={loadingRows} />
            </div>
        );
    }

    const isEmpty = !rows || rows.length === 0;

    return (
        <div className="data-table__scroll">
            <table className="data-table">
                <caption className="sr-only">{caption}</caption>

                <thead>
                    <tr>
                        {columns.map((column) => (
                            <th
                                key={column.key}
                                scope="col"
                                className={column.align === 'right' ? 'is-right' : undefined}
                            >
                                {column.label}
                            </th>
                        ))}
                    </tr>
                </thead>

                <tbody>
                    {isEmpty ? (
                        // Header tetap tampil walau belum ada data, supaya pengguna
                        // tahu kolom apa saja yang akan terisi.
                        <tr>
                            <td colSpan={columns.length} className="data-table__empty">
                                {empty}
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => (
                            <tr key={rowKey(row)} className={rowClassName(row)}>
                                {columns.map((column) => (
                                    <td
                                        key={column.key}
                                        className={column.align === 'right' ? 'is-right' : undefined}
                                    >
                                        {renderCell(row, column)}
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
