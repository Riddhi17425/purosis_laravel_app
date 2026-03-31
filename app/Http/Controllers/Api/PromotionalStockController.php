<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{PromotionalStock, PromotionalStockTransaction};
use Illuminate\Support\Facades\Validator;

class PromotionalStockController extends Controller
{
    public function stockInward(Request $request){
        $validator = Validator::make($request->all(), [
            'item_name' => 'required',
            'item_id' => 'nullable|exists:promotional_stocks,id',
            'qty' => 'required',
            'notes' => 'nullable|max:700',
        ], [
            'item_name.required' => 'Item name is required.',
            'qty.required' => 'Quantity is required.',
            'notes.max' => 'Notes may not be greater than 700 characters.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->item_id) {
            $promotionalStock = PromotionalStock::find($request->item_id);
        } else {
            $promotionalStock = new PromotionalStock();
            $promotionalStockTransaction = new PromotionalStockTransaction();
        }
        $promotionalStock->item_name = $request->item_name;
        $promotionalStock->qty = $request->qty ?? null;
        $promotionalStock->notes = $request->notes ?? null;
        $promotionalStock->save();

        $promotionalStock->serial_no = 'PROMO-'.$promotionalStock->id;
        $promotionalStock->save();

        if (!isset($request->item_id) && $request->item_id == '' ){
            $promotionalStockTransaction->item_id = $promotionalStock->id;
            $promotionalStockTransaction->qty = $promotionalStock->qty;
            $promotionalStockTransaction->notes = $promotionalStock->notes ?? '';
            $promotionalStockTransaction->type = 'inward';
            $promotionalStockTransaction->save();

            $promotionalStockTransaction->serial_no = 'IN-'. $promotionalStockTransaction->id;
            $promotionalStockTransaction->save();
        }   

        return response()->json([
            'success' => true,
            'message' => 'Promotional Stock inward Successfully',
            'data' => $promotionalStock
        ]);
    }

    public function stockOutward(Request $request){
        $validator = Validator::make($request->all(), [
            'item_id' => 'nullable|exists:promotional_stocks,id',
            'qty' => 'required', 
            'distributor_id' => 'required|exists:distributors,id',
            'notes' => 'nullable|max:700',
        ], [
            'item_id.required' => 'Item id is required.',
            'qty.required' => 'Quantity is required.',
            'distributor_id.required_if' => 'Distributor is required when type is outward.',
            'distributor_id.exists' => 'Selected distributor does not exist.',
            'notes.max' => 'Notes may not be greater than 700 characters.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $promotionalStock = PromotionalStock::where('id', $request->item_id)->whereNull('deleted_at')->first();
        if($promotionalStock == ''){
            return response()->json([
                'success' => false,
                'message' => 'Promotional stock not Found',
            ]);
        }
        $checkStock = PromotionalStockTransaction::where('item_id', $request->item_id)->where('type', 'outward')->sum('qty');
        if($checkStock >= $promotionalStock->qty){
            return response()->json([
                'success' => false,
                'message' => 'Out of Stock',
            ]);
        }
        $promotionalStockTransaction = new PromotionalStockTransaction();
        $promotionalStockTransaction->item_id = $promotionalStock->id;
        $promotionalStockTransaction->qty = $request->qty;
        $promotionalStockTransaction->notes = $request->notes ?? '';
        $promotionalStockTransaction->type = 'outward';
        $promotionalStockTransaction->recipient_id = $request->distributor_id;
        $promotionalStockTransaction->save();
        $promotionalStockTransaction->serial_no = 'OUT-'. $promotionalStockTransaction->id;
        $promotionalStockTransaction->save();

        return response()->json([
            'success' => true,
            'message' => 'Promotional Stock outward Successfully',
            'data' => $promotionalStockTransaction
        ]);
    }
}
