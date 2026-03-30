<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\{Admin, Distributor, Order, Product, Brochure, Video, Leaflet, Post, Reel, Dealer, Setting};
use Auth;

class AdminController extends Controller
{
    public function sendAdminOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_no' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $admin = Admin::where('phone_no', $request->phone_no)->first();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number not registered'
            ], 404);
        }

        $otp = rand(1000, 9999);
        $admin->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(5)
        ]);

        // 🔹 Here integrate SMS API
        // Example: sendSMS($admin->mobile, "Your OTP is $otp");

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => $otp // remove this in production
        ]);
    }

    public function verifyAdminOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_no' => 'required',
            'otp' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $admin = Admin::where('phone_no', $request->phone_no)->first();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'admin not found'
            ], 404);
        }
        if ($admin->otp != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);
        }
        if (now()->gt($admin->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired'
            ]);
        }

        // OTP verified — generate token
        $token = $admin->createToken('admin-token')->plainTextToken;
        // Clear OTP after success
        $admin->update([
            'otp' => null,
            'otp_expires_at' => null
        ],);

        $admin->token = $token;
        $admin->role = 'admin';

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $admin
        ]);
    }
    
    public function updateProfile(Request $request){
        $profileCategories = config('global_values.profile_category');
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'phone_no' => 'required',
            'email' => 'required',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'The name field is required.',
            // 'phone_no.required' => 'The mobile field is required.',
            // 'phone_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $adminId = Auth::guard('admin-api')->id();
        $profile = Admin::find($adminId);
        $profile->name = $request->name ?? null;
        // $profile->phone_no = $request->phone_no ?? null;
        $profile->email = $request->email ?? null;
        if($request->hasFile('profile_photo')){
            $file = $request->file('profile_photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('images/admin_profile_photos'), $filename);
            $profile->profile_photo = $filename;
        }
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile stored Successfully',
            'data' => $profile
        ]);
    }

    public function getProfile(Request $request){
        $adminId = Auth::guard('admin-api')->id();
        $profile = Admin::select('id', 'name', 'phone_no', 'email', 'profile_photo')->where('id', $adminId)->first();

        if(isset($profile) && $profile != ''){
            $profile->profile_photo = isset($profile->profile_photo) ? url('images/admin_profile_photos/' . $profile->profile_photo) : null;
            return response()->json([
                'success' => true,
                'message' => 'Profile data retrieved Successfully',
                'data' => $profile
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Profile data are not Found',
            ]);
        }
    }

    public function addUpdateDistributor(Request $request){
        $validator = Validator::make($request->all(), [
            'distributor_id' => 'nullable|exists:distributors,id',
            'name' => 'required',
            'email' => 'required|email',
            //'phone_no' => 'required|digits_between:10,15',
            'whatsapp_no' => 'required|digits_between:10,15',
            'gst_number' => 'nullable',
            'area' => 'nullable|string',
            'billing_address' => 'required',
            'shipping_address_line' => 'required',
            'shipping_address_pincode' => 'required',
            'phone_no' => [
                'required',
                'digits_between:10,15',
                Rule::unique('distributors', 'phone_no')->when(
                    !$request->filled('distributor_id'),
                    fn ($query) => $query
                ),
            ],
        ], [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone_no.required' => 'The mobile number field is required.',
            'phone_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
            'whatsapp_no.required' => 'The WhatsApp number field is required.',
            'whatsapp_no.digits_between' => 'WhatsApp number must be between 10 and 15 digits.',
            'billing_address.required' => 'The billing address field is required.',
            'shipping_address_line.required' => 'The shipping address field is required.',
            'shipping_address_pincode.required' => 'The shipping pincode field is required.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }
        
        try {
            if ($request->distributor_id) {
                $distributor = Distributor::find($request->distributor_id);
            } else {
                $distributor = new Distributor();
            }

            $distributor->name = $request->name;
            $distributor->email = $request->email;
            $distributor->phone_no = $request->phone_no;
            $distributor->whatsapp_no = $request->whatsapp_no;
            $distributor->gst_number = $request->gst_number;
            $distributor->area = $request->area;
            $distributor->billing_address = $request->billing_address;
            $distributor->shipping_address_line = $request->shipping_address_line;
            $distributor->shipping_address_pincode = $request->shipping_address_pincode;
            $distributor->is_active = 1;
            $distributor->save();

            return response()->json([
                'success' => true,
                'message' => 'Distributor details are set successfully.',
                'data' => $distributor
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDistributor(Request $request){
        $validator = Validator::make($request->all(), [
            'distributor_id' => 'nullable|exists:distributors,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $distributor = Distributor::select('id', 'name', 'email', 'phone_no', 'whatsapp_no', 'gst_number', 'area', 'billing_address', 'shipping_address_line', 'shipping_address_pincode', 'is_active');
        if(isset($request->distributor_id) && $request->distributor_id != ''){
            $distributor = $distributor->where('id', $request->distributor_id);
        }
        $distributor = $distributor->get();
        if(isset($distributor) && is_countable($distributor) && count($distributor) > 0){
            foreach($distributor as $key => $val){
                $val->total_orders = 0; //NEED TO MAKE DYNAMIC
                $val->assets_downloaded = 0; //NEED TO MAKE DYNAMIC
                $val->last_active = 0; //NEED TO MAKE DYNAMIC LIKE 2 DAYS ago 30 minutes ago
            }
        }
        
        if(isset($distributor) && is_countable($distributor) && count($distributor) > 0){
            return response()->json(['success' => true, 'message' => 'Distributor get Successfully', 'data' => $distributor]);
        }else{
            return response()->json(['success' => false, 'message' => 'Distributor not Found']);
        }               
    }

    public function getDashboardData(Request $request){
        $confirmedOrders = Order::where('status', 'confirmed')->count();
        $totalOrders = Order::count();
        $totalDistributors = Distributor::count();
        $totalDealers = Dealer::count();
        $totalProducts = Product::count();

        $totalBrochures = Brochure::count();
        $totalVideos = Video::count();
        $totalLeaflets = Leaflet::count();
        $totalPosts = Post::count();
        $totalReels = Reel::count();
        $marketingAssets = $totalBrochures + $totalVideos + $totalLeaflets + $totalPosts + $totalReels;

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'confirmed_orders' => $confirmedOrders,
                'total_orders' => $totalOrders,
                'total_distributors' => $totalDistributors,
                'total_dealers' => $totalDealers,
                'total_products' => $totalProducts,
                'marketing_assets' => $marketingAssets,
                
            ]
        ]);
    }

    public function orderHistory(Request $request){
        $shippingStatuses = config('global_values.shipping_status');
        $validator = Validator::make($request->all(), [
            'sort_by' => 'nullable|in:latest,oldest',
            'shipping_status' => 'nullable|in:all,' . implode(',', array_keys($shippingStatuses))
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $sortOrder = $request->sort_by === 'oldest' ? 'asc' : 'desc';
        $orders = Order::select('id', 'order_number', 'total_weight', 'shipping_status', 'created_at')
            ->withCount('orderProducts')
            ->with(['orderProducts' => function     ($query) {
                $query->select('id', 'order_id', 'product_id')
                    ->with('product:id,product_name');
            }])->orderBy('id', $sortOrder);

        if ($request->shipping_status && $request->shipping_status != 'all') {
            $orders = $orders->where('shipping_status', $request->shipping_status);
        }
        $orders = $orders->get();

        if ($orders->isNotEmpty()) {
            $orders->transform(function ($order) {
                $order->order_date = $order->created_at->format('M d, Y • h:i A');
                return $order;
            });
            $orders->makeHidden(['created_at']);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'No orders found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order history get Successfully',
            'data' => $orders,
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
        
        $order = Order::select('id', 'order_number', 'shipping_status', 'created_at')
            ->where('id', $request->order_id)
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
            'data'    => $data,
        ]);
    }

    public function updateSupportDetails(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'phone_no' => 'required|digits_between:10,15',
            'office_timings' => 'required|string',
            'note' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $setting = Setting::first(); // Get the first record (you can modify this as needed)
        if (!$setting) {
            $setting = new Setting();
        }
        $setting->email = $request->email ?? null;
        $setting->phone_no = $request->phone_no ?? null;
        $setting->office_timings = $request->office_timings ?? null;
        $setting->note = $request->note ?? null;
        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'Support details updated successfully',
            'data' => $setting
        ]);
    }

    public function getSupportDetails(Request $request){
        $setting = Setting::first(); // Get the first record (you can modify this as needed)
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Support details not found.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Support details retrieved successfully',
            'data' => $setting
        ]);
    }


}
 