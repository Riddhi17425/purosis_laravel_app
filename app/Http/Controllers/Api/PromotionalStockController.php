<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\Admin\PromotionalStockOutwardAdminMail;
use App\Mail\Distributor\PromotionalStockOutwardDistributorSendMail;
use Illuminate\Http\Request;
use App\Models\{PromotionalStock, PromotionalStockTransaction};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

        $promotionalStockTransaction->load(['item', 'recipient']);

        $adminEmail = config('global_values.admin_email');

        try {
            Mail::to($adminEmail)->send(new PromotionalStockOutwardAdminMail($promotionalStockTransaction));

            if (!empty($promotionalStockTransaction->recipient?->email)) {
                Mail::to($promotionalStockTransaction->recipient->email)->send(new PromotionalStockOutwardDistributorSendMail($promotionalStockTransaction));
            }
        } catch (\Exception $e) {
            Log::error('Promotional stock outward mail sending failed: ' . $e->getMessage());
        }

        return response()->json([ 
            'success' => true,
            'message' => 'Promotional Stock outward Successfully',
            'data' => $promotionalStockTransaction
        ]);
    }

    public function updateStock(Request $request){
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:promotional_stocks,id',
            'item_name' => 'required|string|max:255',
            'qty' => 'required',
            'notes' => 'nullable|max:700',
        ], [
            'item_id.required' => 'Item id is required.',
            'item_id.exists' => 'Selected item does not exist.',
            'item_name.required' => 'Item name is required.',
            'item_name.string' => 'Item name must be a string.',
            'item_name.max' => 'Item name may not be greater than 255 characters.',

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

        $promotionalStock = PromotionalStock::find($request->item_id);
        $promotionalStock->qty = $request->qty;
        $promotionalStock->item_name = $request->item_name ?? null;
        $promotionalStock->notes = $request->notes ?? null;
        $promotionalStock->save();

        return response()->json([
            'success' => true,
            'message' => 'Promotional Stock updated Successfully',
            'data' => $promotionalStock
        ]);
    }

    public function deleteStock(Request $request){
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:promotional_stocks,id',
        ], [
            'item_id.required' => 'Item id is required.',
            'item_id.exists' => 'Selected item does not exist.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $promotionalStock = PromotionalStock::where('id', $request->item_id)->first();
        if(isset($promotionalStock) && $promotionalStock != null){
            $promotionalStock->delete();
            return response()->json([
                'success' => true,
                'message' => 'Promotional Stock deleted Successfully',
            ]);
        }else{
           return response()->json([
                'success' => true,
                'message' => 'Promotional Stock not Found',
            ]); 
        }
    }

    public function getStockDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:items,transactions',
        ], [
            'type.required' => 'Type is required.',
            'type.in' => 'Selected type does not exist.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if(isset($request->type) && $request->type == 'items'){
            $promotionalStock = PromotionalStock::whereNull('deleted_at')->get();
            if($promotionalStock->isEmpty()){
                return response()->json([
                    'success' => false,
                    'message' => 'No Promotional Stock items found',
                ]);
            }else{
                return response()->json([
                    'success' => true,
                    'message' => 'Promotional Stock items retrieved Successfully',
                    'data' => $promotionalStock
                ]);
            }
        }else if(isset($request->type) && $request->type == 'transactions'){
                $promotionalStock = PromotionalStockTransaction::with(['item', 'recipient'])->get();
                if($promotionalStock->isEmpty()){
                    return response()->json([
                        'success' => false,
                        'message' => 'No Promotional Stock transactions found',
                    ]);
                }else{
                    return response()->json([
                        'success' => true,
                        'message' => 'Promotional Stock transactions retrieved Successfully',
                        'data' => $promotionalStock
                    ]); 
                }
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Invalid type provided.',
            ]);
        }
    }
}
