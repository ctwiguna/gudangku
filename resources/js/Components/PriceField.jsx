import CurrencyInput from './CurrencyInput';

/**
 * Field harga yang hanya dirender untuk owner.
 *
 * Ini lapisan tampilan saja. Penyaringan sebenarnya terjadi di controller:
 * manajer tidak pernah menerima field harga di props Inertia, sehingga
 * menyembunyikan komponen ini bukan satu-satunya pengaman.
 */
export default function PriceField({ canSeePrice, id = 'price_buy', ...props }) {
    if (!canSeePrice) {
        return null;
    }

    return <CurrencyInput id={id} {...props} />;
}
