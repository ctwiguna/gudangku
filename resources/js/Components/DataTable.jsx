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
}) {
    if (loading) {
        return (
            <div className="data-table__loading">
                <Skeleton lines={loadingRows} />
            </div>
        );
    }

    if (!rows || rows.length === 0) {
        return empty;
    }

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
                    {rows.map((row) => (
                        <tr key={rowKey(row)}>
                            {columns.map((column) => (
                                <td
                                    key={column.key}
                                    className={column.align === 'right' ? 'is-right' : undefined}
                                >
                                    {renderCell(row, column)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
