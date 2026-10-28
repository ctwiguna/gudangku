import TextInput from './TextInput';
import { signedNumber } from '../utils/format';

/**
 * Satu baris form stock opname: nama produk, stok sistem, jumlah hitung,
 * selisih yang dihitung langsung, dan catatan opsional.
 */
export default function StockCountRow({
    product,
    systemQty,
    counted,
    note = '',
    onCountChange,
    onNoteChange,
}) {
    const difference = counted === '' || counted === null ? null : Number(counted) - Number(systemQty);

    const tone = difference === null || difference === 0 ? 'is-zero' : difference > 0 ? 'is-plus' : 'is-minus';

    return (
        <div className="stock-row">
            <div className="stock-row__head">
                <div>
                    <p className="stock-row__name">{product.name}</p>
                    <p className="muted">
                        {product.sku} · satuan {product.unit}
                    </p>
                </div>

                <div className="stock-row__system">
                    <span className="muted">Stok sistem</span>
                    <strong>{systemQty}</strong>
                </div>
            </div>

            <div className="stock-row__inputs">
                <label className="field">
                    <span className="field__label">Jumlah hitung</span>
                    <TextInput
                        type="number"
                        inputMode="numeric"
                        min="0"
                        value={counted}
                        onChange={(event) => onCountChange(event.target.value)}
                        aria-label={`Jumlah hitung ${product.name}`}
                    />
                </label>

                <div className={`stock-row__diff ${tone}`}>
                    <span className="muted">Selisih</span>
                    <strong>{difference === null ? '-' : signedNumber(difference)}</strong>
                </div>
            </div>

            <label className="field">
                <span className="field__label">Catatan (opsional)</span>
                <TextInput
                    type="text"
                    value={note}
                    placeholder="Contoh: rusak, bocor, salah hitung"
                    onChange={(event) => onNoteChange(event.target.value)}
                />
            </label>
        </div>
    );
}
