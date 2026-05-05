<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    // Public validation for storefront
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric'
        ]);

        $promo = PromoCode::where('code', $request->code)->first();

        if (!$promo) {
            return response()->json(['message' => 'Invalid promo code.'], 404);
        }

        if (!$promo->isValid()) {
            return response()->json(['message' => 'This promo code has expired or reached its usage limit.'], 400);
        }

        $discount = $promo->calculateDiscount($request->subtotal);

        return response()->json([
            'code' => $promo->code,
            'type' => $promo->type,
            'value' => $promo->value,
            'discount_amount' => $discount
        ]);
    }

    // Admin CRUD
    public function index()
    {
        return response()->json(PromoCode::latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:promo_codes',
            'type' => 'required|in:fixed,percentage',
            'value' => 'required|numeric',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'usage_limit' => 'nullable|integer',
        ]);

        return response()->json(PromoCode::create($data), 201);
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $data = $request->validate([
            'code' => 'string|unique:promo_codes,code,' . $promoCode->id,
            'type' => 'in:fixed,percentage',
            'value' => 'numeric',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'usage_limit' => 'nullable|integer',
        ]);

        $promoCode->update($data);
        return response()->json($promoCode);
    }

    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();
        return response()->json(['message' => 'Promo code deleted.']);
    }
}
