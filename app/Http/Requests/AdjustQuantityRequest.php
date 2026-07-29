<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared validation for the adjust-quantity modal submitted from any
 * accessory / consumable / component listing or view page. Authorization
 * is left to the controller (it needs the resolved route-model to run
 * the 'update' policy) so authorize() here is unconditionally true.
 */
class AdjustQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Signed delta: positive to replenish, negative to decrement.
            // The trait rejects the actual below-in-use case; here we
            // just guard against zero (nothing to do) and non-integer.
            'amount' => ['required', 'integer', 'not_in:0'],
            'note' => ['required', 'string', 'max:65535'],
            'order_number' => ['nullable', 'string', 'max:191'],
            // Optional receipt/invoice/PO scan. Attaches to the same
            // QuantityAdjust action_log row via its filename column,
            // not a separate 'uploaded' entry, so the download surfaces
            // inline with the replenish history. Same allowlist and size
            // cap as the app-wide UploadFileRequest.
            'file' => 'nullable|mimes:'.config('filesystems.allowed_upload_extensions_for_validator').'|max:'.Helper::file_upload_max_size(),
        ];
    }
}
