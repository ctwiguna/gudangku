<?php

namespace App\Http\Requests;

/**
 * Aturan ubah sama dengan aturan simpan.
 * Dipisah agar pesan error dan aturan bisa berkembang sendiri-sendiri.
 */
class UpdateExpenseRequest extends StoreExpenseRequest
{
}
