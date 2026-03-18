<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Cart, Product, Address};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Auth;

class DistributorController extends Controller
{
    public function addToCart(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'qty' => 'required',
            'color_code' => 'required'
        ], [
            'product_id.required' => 'Please select the product name.',
            'qty.required' => 'Please enter the Quantity.',
            'color_code.required' => 'Please enter the Color Code.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $checkCart = Cart::where('product_id', $request->product_id)->where('distributor_id', Auth::guard('distributor-api')->id())->first();
        $product = Product::where('id', $request->product_id)->first();
        if($checkCart){
            $checkCart->qty += $request->qty;
        }else{
            $checkCart = new Cart();
            $checkCart->distributor_id = Auth::guard('distributor-api')->id();
            $checkCart->product_id = $request->product_id ?? null;
            $checkCart->qty = $request->qty ?? null;
            $checkCart->color_code = $request->color_code ?? null;
            //$checkCart->price = $request->product_id ?? null;
            $checkCart->units_per_box = $product->units_per_box ?? null;
            $checkCart->weight_per_box = $product->weight_per_box ?? null;
        }
        $checkCart->save();

        $checkCart->total_weight = $product->weight_per_box * $checkCart->qty ?? null;
        $cbm = $product->length * $product->width *	$product->height;
        $checkCart->total_cbm = $cbm * $checkCart->qty;
        $checkCart->save();

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart Successfully',
            'data' => $checkCart
        ]);
    }

    public function viewCart(Request $request){
        $cart = Cart::where('distributor_id', Auth::guard('distributor-api')->id())->get();

        if(isset($cart) && is_countable($cart) && count($cart) > 0){
            return response()->json([
                'success' => true,
                'message' => 'Cart details are get Successfully',
                'data' => $cart
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'No any products are Found',
            ]);
        }
    }

    public function addUpdateAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'address_id' =>  'nullable|exists:addresses,id',
            'dealer_name' => 'required|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'gst_number' => 'required',
            'address' => 'required|string|max:500',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'mobile_no' => 'required|digits_between:10,15',
            'email_address' => 'required|email|max:255',
            'is_shipping' => 'required|in:0,1',
        ],[
            'dealer_name.string' => 'Dealer name must be a valid string.',
            'dealer_name.max' => 'Dealer name cannot exceed 255 characters.',
            'contact_person_name.string' => 'Contact person name must be a valid string.',
            'contact_person_name.max' => 'Contact person name cannot exceed 255 characters.',
            'address.string' => 'Address must be a valid string.',
            'address.max' => 'Address cannot exceed 500 characters.',
            'state.string' => 'State must be a valid string.',
            'state.max' => 'State cannot exceed 255 characters.',
            'city.string' => 'City must be a valid string.',
            'city.max' => 'City cannot exceed 255 characters.',
            'mobile_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
            'email_address.email' => 'Please enter a valid email address.',
            'email_address.max' => 'Email address cannot exceed 255 characters.',
            'is_shipping.required' => 'Please select address type.',
            'is_shipping.in' => 'Address type must be Billing or Shipping.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $address = Address::updateOrCreate(
            [
                'id' => $request->address_id
            ],
            [
                'dealer_name' => $request->dealer_name,
                'contact_person_name' => $request->contact_person_name,
                'gst_number' => $request->gst_number,
                'address' => $request->address,
                'state' => $request->state,
                'city' => $request->city,
                'mobile_no' => $request->mobile_no,
                'email_address' => $request->email_address,
                'is_shipping' => $request->is_shipping,
                'distributor_id' => Auth::guard('distributor-api')->id()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Address stored Successfully',
            'data' => $address
        ]);
    }

    public function deleteAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'address_id' =>  'nullable|exists:addresses,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $address = Address::find($request->address_id);
        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found.',
            ]);
        }
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
        ]);
    }

    public function getAddresses(Request $request){
        $addresses = Address::where('distributor_id', Auth::guard('distributor-api')->id())->get();
        if(isset($addresses) && is_countable($addresses) && count($addresses) > 0){
            return response()->json([
                'success' => true,
                'message' => 'Addresses are get Successfully',
                'data' => $addresses
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'No any Addresses are Found',
            ]);
        }
    }
   
}
