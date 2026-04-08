<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminPurchaseOrderMail;
use App\Mail\DistributorPurchaseOrderMail;
use Illuminate\Http\Request;
use App\Models\{Cart, Product, Address, Order, OrderProduct, SupportMessageInquiry};
use App\Services\LocationTrackerService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DistributorController extends Controller
{
    protected $locationTrackerService;
    public function __construct(LocationTrackerService $locationTrackerService)
    {
        $this->locationTrackerService = $locationTrackerService;
    }

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

        $checkCart = Cart::where('product_id', $request->product_id)->where('distributor_id', Auth::guard('distributor-api')->id())->where('color_code', $request->color_code)->first();
        $product = Product::where('id', $request->product_id)->first();
        if(!$checkCart){
            $checkCart = new Cart();
        }
            
        $checkCart->qty += $request->qty;
        $checkCart->distributor_id = Auth::guard('distributor-api')->id();
        $checkCart->product_id = $request->product_id ?? null;
        $checkCart->qty = $request->qty ?? null;
        $checkCart->color_code = $request->color_code ?? null;
        //$checkCart->price = $request->product_id ?? null;
        $checkCart->units_per_box = $product->units_per_box ?? null;
        $checkCart->weight_per_box = $product->weight_per_box ?? null;

        $checkCart->total_weight = ($product->weight_per_box ?? 0) * ($checkCart->qty ?? 0);
        $cbm = ($product->length ?? 0) * ($product->width ?? 0) * ($product->height ?? 0);
        $checkCart->total_cbm = $cbm * ($checkCart->qty ?? 0);
        $checkCart->save();

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart Successfully',
            'data' => $checkCart
        ]);
    }

    public function viewCart(Request $request){
        $cart = Cart::with('product:id,product_name,product_colors_images')->where('distributor_id', Auth::guard('distributor-api')->id())->get();

        if(isset($cart) && is_countable($cart) && count($cart) > 0){

            $cart = $cart->map(function ($cartItem) {
                if ($cartItem->product) {
                    $product = $cartItem->product;
                    $productColors = collect($product->product_colors_images ?? [])->map(function ($img) {
                        $images = [];
                        if (!empty($img['images'])) {
                            if (is_array($img['images'])) {
                                $images = collect($img['images'])->map(function ($image) {
                                    return url('images/product_images/' . $image);
                                })->toArray();
                            } else {
                                $images[] = url('images/product_images/' . $img['images']);
                            }
                        }
                        return [
                            'color_name' => $img['color_name'] ?? null,
                            'color_code' => $img['color_code'] ?? null,
                            'images'     => $images,
                        ];
                    });
                    $product->product_colors_images = $productColors;
                }
                return $cartItem;
            });

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
            'pincode' => 'required|max:10',
            'mobile_no' => 'required|digits_between:10,15',
            'email_address' => 'required|email|max:255',
            'is_shipping' => 'required|in:0,1',
            'shipping_address' => 'required_if:is_shipping,1',
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
            'pincode.required' => 'The pincode field is required.',
            'pincode.max' => 'The pincode must not exceed 10 characters.',
            'mobile_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
            'email_address.email' => 'Please enter a valid email address.',
            'email_address.max' => 'Email address cannot exceed 255 characters.',
            'is_shipping.required' => 'Please select address type.',
            'is_shipping.in' => 'Address type must be Billing or Shipping.',
            'shipping_address.required_if' => 'Shipping address is required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $distributorId = Auth::guard('distributor-api')->id();
        // Common data
        $commonData = [
            'dealer_name' => $request->dealer_name,
            'contact_person_name' => $request->contact_person_name,
            'gst_number' => $request->gst_number,
            'state' => $request->state,
            'city' => $request->city,
            'pincode' => $request->pincode,
            'mobile_no' => $request->mobile_no,
            'email_address' => $request->email_address,
            'distributor_id' => $distributorId,
        ];

        // If is_shipping = 0 → create both billing and shipping entries with the same address
        // If is_shipping = 1 → create separate billing and shipping entries (shipping_address will be used for shipping)

        // // Billing Address
        // $billing = Address::create(array_merge($commonData, [
        //     'address' => $request->address,
        //     'is_shipping' => 0,
        // ]));

        // // Shipping Address (same or different)
        $shippingAddress = $request->is_shipping == 1 
            ? $request->shipping_address 
            : $request->address;

        // $shipping = Address::create(array_merge($commonData, [
        //     'address' => $shippingAddress,
        //     'is_shipping' => 1,
        // ]));

        // ================= UPDATE =================
        if ($request->address_id) {
            $address = Address::where('id', $request->address_id)
                ->where('distributor_id', $distributorId)
                ->first();

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Address not found'
                ]);
            }
        
            $address->update(array_merge($commonData, [
                'address' => $shippingAddress,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Address stored Successfully',
                'data' => [
                    'address' => $address,
                ]
            ]);
            
        } 
        // ================= CREATE =================
        else {
            $billing = Address::create(array_merge($commonData, [
                'address' => $request->address,
                'is_shipping' => 0,
            ]));

            $shipping = Address::create(array_merge($commonData, [
                'address' => $shippingAddress,
                'is_shipping' => 1,
            ]));
            return response()->json([
                'success' => true,
                'message' => 'Address stored Successfully',
                'data' => [
                    'billing' => $billing,
                    'shipping' => $shipping
                ]
            ]);
        }
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

    public function updateProfile(Request $request){
        $user = Auth::guard('distributor-api')->user();
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'gst_number' => 'required|string|max:50',
            'area' => 'nullable|string|max:255',
            'mobile_no' => 'required|digits_between:10,15',
            'alternate_mobile_no' => 'nullable|digits_between:10,15',
            'landline_no' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'company_name.required' => 'Company name is required.',
            'contact_person_name.required' => 'Contact person name is required.',
            'gst_number.required' => 'GST number is required.',
            'mobile_no.required' => 'Mobile number is required.',
            'mobile_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }
        
        $data = [
            'company_name' => $request->company_name,
            'name' => $request->contact_person_name,
            'gst_number' => $request->gst_number,
            'area' => $request->area,
            'phone_no' => $request->mobile_no,
            'alternate_mobile_no' => $request->alternate_mobile_no,
            'landline_no' => $request->landline_no,
            'email' => $request->email,
        ];

        $path = public_path('images/profile');
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = $user->id.'_'.time() . '_' . $file->getClientOriginalName();
            $file->move($path, $filename);
            $data['logo'] = $filename;
        }
        $user->update($data);

        if($user->logo){
            $user->logo = asset('images/profile/'.$user->logo);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    public function proceedToCheckout(Request $request){
        $distributorId = Auth::guard('distributor-api')->id();
        $distributor = Auth::guard('distributor-api')->user();
        $adminEmail = config('global_values.admin_email');


        $transportationTypes = config('global_values.transportation_types');

        // Determine if it's a Buy Now (direct) or Cart-based checkout
        $isBuyNow = $request->filled('product_id');
        $rules = [
            'billing_address_id'  => 'required|exists:addresses,id',
            'shipping_address_id' => 'required|exists:addresses,id',
            'type'                => 'required|in:' . implode(',', $transportationTypes),
            'remarks'             => 'nullable|max:255',
        ];

        // Extra validation for Buy Now
        if ($isBuyNow) {
            $rules['product_id']  = 'required|exists:products,id';
            $rules['qty']         = 'required|integer|min:1';
            $rules['color_code']  = 'required|string';
        } else {
            // Allow selecting specific cart items; if omitted, all cart items are used
            $rules['cart_ids']   = 'nullable|array|min:1';
            $rules['cart_ids.*'] = 'integer|exists:carts,id';
        }
        $validator = Validator::make($request->all(), $rules, [
            'billing_address_id.required'  => 'Billing address is required.',
            'shipping_address_id.required' => 'Shipping address is required.',
            'type.required'                => 'Transportation type is required.',
            'type.in'                      => 'Invalid transportation type.',
            'remarks.max'                  => 'Remarks cannot exceed 255 characters.',
            'product_id.required'          => 'Product is required for Buy Now.',
            'qty.required'                 => 'Quantity is required for Buy Now.',
            'color_code.required'          => 'Color code is required for Buy Now.',
            'cart_ids.array'               => 'cart_ids must be an array.',
            'cart_ids.min'                 => 'Please select at least one cart item.',
            'cart_ids.*.exists'            => 'One or more selected cart items are invalid.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ]);
        }

        // Build the items to insert into order_products
        if ($isBuyNow) {
            $product = Product::find($request->product_id);
            $qty     = (int) $request->qty;
            $cbm     = ($product->length ?? 0) * ($product->width ?? 0) * ($product->height ?? 0);

            $items = [[
                'product_id'     => $product->id,
                'qty'            => $qty,
                'color_code'     => $request->color_code,
                'price'          => $product->price ?? null,
                'units_per_box'  => $product->units_per_box ?? null,
                'weight_per_box' => $product->weight_per_box ?? null,
                'total_weight'   => ($product->weight_per_box ?? 0) * $qty,
                'total_cbm'      => $cbm * $qty,
            ]];
        } else {
            $cartQuery = Cart::where('distributor_id', $distributorId);

            // If specific cart_ids are provided, filter to only those items
            if ($request->filled('cart_ids')) {
                $cartQuery->whereIn('id', $request->cart_ids);
            }

            $cartItems = $cartQuery->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No selected cart items found. Please select at least one product.',
                ]);
            }

            $items = $cartItems->map(fn($c) => [
                'product_id'     => $c->product_id,
                'qty'            => $c->qty,
                'color_code'     => $c->color_code,
                'price'          => $c->price,
                'units_per_box'  => $c->units_per_box,
                'weight_per_box' => $c->weight_per_box,
                'total_weight'   => $c->total_weight,
                'total_cbm'      => $c->total_cbm,
            ])->toArray();
        }

        // Calculate order totals
        $totalWeight = array_sum(array_column($items, 'total_weight'));
        $totalCbm    = array_sum(array_column($items, 'total_cbm'));

        // Create the order
        $order = Order::create([
            'distributor_id'      => $distributorId,
            'billing_address_id'  => $request->billing_address_id,
            'shipping_address_id' => $request->shipping_address_id,
            'type'                => $request->type,
            'remarks'             => $request->remarks ?? null,
            'total_weight'        => $totalWeight,
            'total_cbm'           => $totalCbm,
            'status'              => 'confirmed',
        ]);

        // Insert order products
        $now = now();
        $orderProducts = array_map(fn($item) => array_merge($item, [
            'order_id'   => $order->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $items);

        OrderProduct::insert($orderProducts);

        // Clear cart (only for cart-based checkout)
        if (!$isBuyNow) {
            $deleteQuery = Cart::where('distributor_id', $distributorId);
            if ($request->filled('cart_ids')) {
                $deleteQuery->whereIn('id', $request->cart_ids);
            }
            $deleteQuery->delete();
        }
        // this for location tracking where to buying orders
        $this->locationTrackerService->track('order', 'distributor', $distributorId, $request , $order->id);

        // Send order confirmation email to distributor
        try {
            Mail::to($distributor->email)->send(new DistributorPurchaseOrderMail($order));
            Mail::to($adminEmail)->send(new AdminPurchaseOrderMail($order));
        } catch (Exception $e) {
            Log::error('Distributor Purchase Order email sending failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully.',
            'data'    => $order->load('orderProducts'),
        ]);
    }

    public function orderHistory(Request $request){
        $validator = Validator::make($request->all(), [
            'sort_by' => 'nullable|in:latest,oldest',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $distributorId = Auth::guard('distributor-api')->id();

        // Summary counts
        $allOrders = Order::where('distributor_id', $distributorId);
        $summary = [
            'total_orders' => (clone $allOrders)->count(),
            'pending'      => (clone $allOrders)->where('shipping_status', 'pending')->count(),
            'confirm'      => (clone $allOrders)->where('shipping_status', 'confirmed')->count(),
            'failed'       => (clone $allOrders)->where('shipping_status', 'failed')->count(),
        ];

        // Build query
        $sortOrder = $request->sort_by === 'oldest' ? 'asc' : 'desc';

        $orders = Order::select('id', 'order_number', 'total_weight', 'shipping_status', 'created_at')
            ->where('distributor_id', $distributorId)
            ->withCount('orderProducts')
            ->orderBy('id', $sortOrder)
            ->get();
    
        if ($orders->isNotEmpty()) {
            $orders->transform(function ($order) {
                $order->order_date = $order->created_at->format('M d, Y • h:i A');
                return $order;
            });
            $orders->makeHidden(['created_at']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order history get Successfully',
            'data' => [
                'summary' => $summary,
                'orders'  => $orders,
            ],
        ]);
    }

    public function orderDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $distributorId = Auth::guard('distributor-api')->id();
        $order = Order::select('id', 'order_number', 'shipping_status', 'created_at')
            ->where('id', $request->order_id)
            ->where('distributor_id', $distributorId)
            ->with(['orderProducts:id,order_id,product_id,qty,color_code,price,total_weight,total_cbm', 'orderProducts.product:id,product_name,units_per_box,weight_per_box,product_colors_images'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ]);
        }
        $data = $order->toArray();
        foreach ($data['order_products'] as $opIndex => $op) {
            foreach ($op['product']['product_colors_images'] ?? [] as $imgIndex => $img) {
                $data['order_products'][$opIndex]['product']['product_colors_images'][$imgIndex]['images'] = array_map(
                    fn($i) => url('images/product_images/' . $i),
                    $img['images'] ?? []
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order details get Successfully',
            'data' => $order,
        ]);
    }

    public function deleteCart(Request $request){
        $validator = Validator::make($request->all(), [
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'exists:carts,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $distributorId = Auth::guard('distributor-api')->id();
        $checkExists = Cart::where('distributor_id', $distributorId)->whereIn('id', $request->cart_ids)->get();
        if ($checkExists->isNotEmpty()) {
            $checkExists->each->delete();
           
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully.',
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Product not found in cart.',
            ]);
        }
    }
    
    public function supportMessageInquiry(Request $request){
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:500',
            'product_id' => 'required|exists:products,id',
            'message' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }
        
        $supportMessage = SupportMessageInquiry::create([
            'distributor_id' => Auth::guard('distributor-api')->id(),
            'subject' => $request->subject,
            'product_id' => $request->product_id,
            'message' => $request->message,
        ]);

        //SEND MAIL TO ADMIN
        $adminEmail = config('global_values.admin_email');
        $data = [
            'distributor'       => $supportMessage->distributor->name ?? null,
            'subject'       => $supportMessage->subject ?? null,
            'message_data'       => $supportMessage->message ?? null,
        ];
        try {
            Mail::send('email.admin.support_inquiry', $data, function ($message) use ($adminEmail) {
                $message->to($adminEmail)->subject('New Support Message Inquiry');
            });
        } catch (Exception $e) {
            \Log::error('Support Message Inquiry sending failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully. We will get back to you soon.',
            'data' => $supportMessage
        ]);
    }
   
}
