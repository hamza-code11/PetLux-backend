<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class SettingController extends Controller
{
    /**
     * Get Shipping Charges
     */
    public function getShippingCharges()
    {
        $charges = Setting::getValue('shipping_charges', '0');
        
        return response()->json([
            'status' => true,
            'data' => [
                'shipping_charges' => (float) $charges
            ]
        ]);
    }

    /**
     * Update Shipping Charges
     */
    public function updateShippingCharges(Request $request)
    {
        $request->validate([
            'shipping_charges' => 'required|numeric|min:0',
        ]);

        Setting::setValue('shipping_charges', $request->shipping_charges);

        return response()->json([
            'status' => true,
            'message' => 'Shipping charges updated successfully.',
            'data' => [
                'shipping_charges' => (float) $request->shipping_charges
            ]
        ]);
    }





    public function getPaymentSettings()
    {
        return response()->json([
            'status' => true,
            'data' => [
                'bank_name'       => Setting::getValue('bank_name', ''),
                'account_title'   => Setting::getValue('account_title', ''),
                'account_number'  => Setting::getValue('account_number', ''),
                'iban'            => Setting::getValue('iban', ''),
                'instructions'    => Setting::getValue('payment_instructions', ''),
                'qr_image_path'   => Setting::getValue('payment_qr_path', null), // 👈 Path return kar rahe hain
            ]
        ]);
    }

    


        public function updatePaymentSettings(Request $request)
    {
        // 1. Validation (SVG allowed)
        $request->validate([
            'bank_name'      => 'nullable|string|max:255',
            'account_title'  => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'iban'           => 'nullable|string|max:255',
            'instructions'   => 'nullable|string',
            'qr_image'       => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:5120', // 5MB max
            'remove_qr'      => 'nullable|string'
        ]);

        // 2. Text fields save karein
        Setting::setValue('bank_name', $request->bank_name ?? '');
        Setting::setValue('account_title', $request->account_title ?? '');
        Setting::setValue('account_number', $request->account_number ?? '');
        Setting::setValue('iban', $request->iban ?? '');
        Setting::setValue('payment_instructions', $request->instructions ?? '');

        // 3. Agar user ne image Remove ki hai
        if ($request->remove_qr == '1') {
            $oldImagePath = Setting::getValue('payment_qr_path');
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
            Setting::where('key', 'payment_qr_path')->delete();
        } 
        // 4. Agar user ne Nayi image Upload ki hai
        else if ($request->hasFile('qr_image')) {
            // Purani image delete karein
            $oldImagePath = Setting::getValue('payment_qr_path');
            if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }

            // Nayi image save karein 'storage/app/public/payments' me
            $path = $request->file('qr_image')->store('payments', 'public');
            
            // DB me sirf path save karein
            Setting::setValue('payment_qr_path', $path);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment settings updated successfully.',
            'data' => [
                'qr_image_path' => Setting::getValue('payment_qr_path') // 👈 Frontend ko naya path wapas bhejein
            ]
        ]);
    }

    

}




