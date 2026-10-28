<?php

namespace App\Http\Requests;

/**
 * Aturan ubah penerimaan sama dengan aturan simpan: tanpa field harga.
 * Perbedaan perilakunya ada di controller, karena perubahan jumlah menulis
 * movement penyesuaian sebesar selisih.
 */
class UpdateReceiptRequest extends StoreReceiptRequest
{
}
