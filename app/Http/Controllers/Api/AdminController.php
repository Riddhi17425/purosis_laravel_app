<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\Distributor\DistributorOrderStatusMail;
use App\Mail\Distributor\DistributorShippingStatusMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\{Admin, Distributor, Order, Product, Brochure, Video, Leaflet, Post, Reel, Dealer, Setting, Banner, SupportMessageInquiry, UserActivityLocation, OrderProduct, DistributorNotification};
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Services\LocationTrackerService;
use App\Services\OtpTransactionService;

class AdminController extends Controller
{
    protected $locationTrackerService;
    protected $otpTransactionService;

    public function __construct(LocationTrackerService $locationTrackerService ,OtpTransactionService $otpTransactionService)
    {
        $this->locationTrackerService = $locationTrackerService;
        $this->otpTransactionService = $otpTransactionService;
    }

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

        // Generate OTP using comman service 
        $otp = $this->otpTransactionService->generateOtp();

        // Send OTP SMS Service when upload on productions then uncomment this s
        // $this->otpTransactionService->sendOtp($admin->phone_no, $otp);

        $admin->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(5)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            //'data' => $otp // remove this in production
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

        $admin->profile_photo = isset($admin->profile_photo) ? url('images/admin_profile_photos/' . $admin->profile_photo) : null;

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

        $profile->profile_photo = isset($profile->profile_photo) ? url('images/admin_profile_photos/' . $profile->profile_photo) : null;

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
            'company_name' => 'nullable|string|max:255',
            'email' => 'required|email',
            //'phone_no' => 'required|digits_between:10,15',
            'whatsapp_no' => 'nullable|digits_between:10,15',
            'gst_number' => 'nullable',
            'area' => 'nullable|string',
            'billing_address' => 'nullable',
            'shipping_address_line' => 'nullable',
            'shipping_address_pincode' => 'nullable',
            'phone_no' => [
                'nullable',
                'digits_between:10,15',
                Rule::unique('distributors', 'phone_no')->when(
                    !$request->filled('distributor_id'),
                    fn ($query) => $query
                ),
            ],
            'alternate_mobile_no' => 'nullable|digits_between:10,15',
            'landline_no' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required' => 'The name field is required.',
            'company_name.required' => 'Company name is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone_no.required' => 'The mobile number field is required.',
            'phone_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
            //'whatsapp_no.required' => 'The WhatsApp number field is required.',
            'whatsapp_no.digits_between' => 'WhatsApp number must be between 10 and 15 digits.',
            //'billing_address.required' => 'The billing address field is required.',
            //'shipping_address_line.required' => 'The shipping address field is required.',
            //'shipping_address_pincode.required' => 'The shipping pincode field is required.',
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
            $distributor->company_name = $request->company_name ?? null;
            $distributor->email = $request->email;
            $distributor->phone_no = $request->phone_no;
            $distributor->alternate_mobile_no = $request->alternate_mobile_no ?? null;
            $distributor->landline_no = $request->landline_no ?? null;
            $distributor->whatsapp_no = $request->whatsapp_no;
            $distributor->gst_number = $request->gst_number;
            $distributor->area = $request->area;
            $distributor->billing_address = $request->billing_address;
            $distributor->shipping_address_line = $request->shipping_address_line;
            $distributor->shipping_address_pincode = $request->shipping_address_pincode;
            $distributor->is_active = 1;
            $distributor->save();

            $path = public_path('images/profile');
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $filename = $request->distributor_id.'_'.time() . '_' . $file->getClientOriginalName();
                // Delete old logo if it exists
                if ($distributor->logo && file_exists($path . '/' . $distributor->logo)) {
                    unlink($path . '/' . $distributor->logo);
                }
                $file->move($path, $filename);
                $distributor->logo = $filename;
                $distributor->save();
            }

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

        $distributor = Distributor::select('id', 'name', 'company_name', 'email', 'phone_no', 'whatsapp_no', 'gst_number', 'area', 'billing_address', 'shipping_address_line', 'shipping_address_pincode', 'is_active', 'alternate_mobile_no', 'landline_no', 'logo', 'assets_downloaded_count');
        if(isset($request->distributor_id) && $request->distributor_id != ''){
            $distributor = $distributor->where('id', $request->distributor_id);
        }
        $distributor = $distributor->orderBy('id', 'DESC')->get();
        if(isset($distributor) && is_countable($distributor) && count($distributor) > 0){
            foreach($distributor as $key => $val){
                $val->total_orders = Order::where('distributor_id', $val->id)->count();

                $lastActivity = UserActivityLocation::where('actor_id', $val->id)
                    ->where('actor_type', 'distributor')
                    ->orderBy('event_at', 'desc')
                    ->value('event_at');

                $val->last_active = $lastActivity ? Carbon::parse($lastActivity)->diffForHumans() : null;
                if($val->logo){
                    $val->logo = asset('images/profile/'.$val->logo);
                }
            }
        }
        
        if(isset($distributor) && is_countable($distributor) && count($distributor) > 0){
            return response()->json(['success' => true, 'message' => 'Distributor get Successfully', 'data' => $distributor]);
        }else{
            return response()->json(['success' => false, 'message' => 'Distributor not Found']);
        }               
    }

    public function getDashboardData(Request $request){
        $pendingOrders = Order::where('shipping_status', 'pending')->count();
        $confirmedOrders = Order::where('shipping_status', 'approved')->count();
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
                'pending_orders' => $pendingOrders,
                'total_orders' => $totalOrders,
                'confirmed_orders' => $confirmedOrders,                
                'total_distributors' => $totalDistributors,
                // 'total_dealers' => $totalDealers,
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
        $orders = Order::select('id', 'order_number', 'total_weight', 'status', 'shipping_status', 'created_at', 'distributor_id')
            ->withCount('orderProducts')
            ->with(['orderProducts' => function     ($query) {
                $query->select('id', 'order_id', 'product_id')
                    ->with('product:id,product_name');
            }])->orderBy('id', $sortOrder);

        if ($request->shipping_status && $request->shipping_status != 'all') {
            $orders = $orders->where('shipping_status', $request->shipping_status);
        }
        $orders = $orders->with('distributor:id,name,company_name,email,phone_no')->get();

        if ($orders->isNotEmpty()) {
            $orders->transform(function ($order) use ($shippingStatuses) {
                $order->order_date = $order->created_at->format('M d, Y • h:i A');
                $order->shipping_status_label = $shippingStatuses[$order->shipping_status] ?? $order->shipping_status;
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
            'data' =>  $orders,
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
            ->with([
                'orderProducts' => fn($q) => $q->select('id', 'order_id', 'product_id', 'qty', 'color_code', 'color_id', 'price', 'total_weight', 'total_cbm')
                    ->with([
                        'product' => fn($pq) => $pq->select('id', 'product_name', 'units_per_box', 'weight_per_box')
                            ->with('productColors.productColorImages'),
                    ]),
            ])
            ->first();
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ]);
        }

        foreach ($order->orderProducts as $orderProduct) {
            if ($orderProduct->product) {
                $product = clone $orderProduct->product;
                $orderProduct->setRelation('product', $product);
                $matchedColor = $product->productColors->firstWhere('id', $orderProduct->color_id)
                    ?? $product->productColors->firstWhere('color_code', $orderProduct->color_code);

                if ($matchedColor) {
                    $product->product_colors_images = [[
                        'color_id'   => $matchedColor->id,
                        'color_name' => $matchedColor->color_name,
                        'color_code' => $matchedColor->color_code,
                        'images'     => $matchedColor->productColorImages->map(fn($img) => url('images/product_images/' . $img->image))->values()->toArray(),
                    ]];
                } else {
                    $product->product_colors_images = [['color_id' => null, 'color_name' => null, 'color_code' => null, 'images' => []]];
                }

                unset($product->productColors);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order details get Successfully',
            'data'    => $order,
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

    public function addUpdateBanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_id' => 'nullable|exists:banners,id',
            'type'      => 'required|in:distributor,dealer',
            'image'     => 'required_without:banner_id|image|mimes:jpeg,jpg,png,webp|max:2048',
        ], [
            'banner_id.exists'         => 'The selected banner is invalid.',
            'type.required'            => 'The type field is required.',
            'type.in'                  => 'Type must be either distributor or dealer.',
            'image.required_without'   => 'Please upload a banner image.',
            'image.image'              => 'The uploaded file must be an image.',
            'image.mimes'             => 'Only jpeg, jpg, png, and webp images are allowed.',
            'image.max'                => 'The image must not exceed 2MB in size.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        if ($request->banner_id) {
            $banner = Banner::find($request->banner_id);
            if (!$banner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Banner not found.',
                ]);
            }
        } else {
            $banner = new Banner();
        }

        $banner->type = $request->type;
        $banner->save();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = $banner->id . '_' . time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('images/banner_images');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Delete old image if updating
            if ($banner->image) {
                $oldFile = $destinationPath . '/' . $banner->image;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            $file->move($destinationPath, $fileName);
            $banner->image = $fileName;
        }
        $banner->save();

        return response()->json([
            'success' => true,
            'message' => $request->banner_id ? 'Banner updated successfully.' : 'Banner added successfully.',
            'data'    => $banner,
        ]);
    }

    public function getBanners(Request $request)
    {
        $query = Banner::whereNull('deleted_at');

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $banners = $query->latest()->get();

        $banners->transform(function ($banner) {
            $banner->image = $banner->image ? url('images/banner_images/' . $banner->image) : null;
            return $banner;
        });

        return response()->json([
            'success' => true,
            'message' => 'Banners retrieved successfully.',
            'data'    => $banners,
        ]);
    }

    public function deleteBanner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner_id' => 'required|exists:banners,id',
        ], [
            'banner_id.required' => 'The banner_id field is required.',
            'banner_id.exists'   => 'The selected banner is invalid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $banner = Banner::find($request->banner_id);

        // Delete image from disk
        if (isset($banner)) {
            if(isset($banner->image) && $banner->image != ''){
                $filePath = public_path('images/banner_images/' . $banner->image);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $banner->delete();
           
            return response()->json([
                'success' => true,
                'message' => 'Banner deleted successfully.',
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Banner not Found.',
            ]);
        }
    }

    public function approveDeclineOrder(Request $request){
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'action' => 'required|in:approved,declined'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $order = Order::find($request->order_id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ]);
        }

        if ($request->action === 'approved') {
            $order->status = 'confirmed';
            $order->shipping_status = 'approved';
        } else if ($request->action === 'declined') {
            $order->status = 'declined';
            $order->shipping_status = 'declined';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid action.',
            ]);
        }
        $order->save();

        $order->load([
            'distributor',
            'billingAddress',
            'shippingAddress',
            'orderProducts.product',
        ]);

        try {
            if (!empty($order->distributor?->email)) {
                Mail::to($order->distributor->email)->send(new DistributorOrderStatusMail($order));
            }
        } catch (\Exception $e) {
            Log::error('Distributor order status email sending failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true, 
            'message' => "Order has been {$order->status} successfully.",
            'data'    => $order,
        ]);
    }

    public function updateShippingStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id'        => 'required|exists:orders,id',
            'shipping_status' => 'required|in:in-process,shipped,delivered',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $order = Order::find($request->order_id);
        $blockedStatuses = ['delivered', 'declined', 'pending'];
        if (in_array($order->shipping_status, $blockedStatuses)) {
            return response()->json([
                'success' => false,
                'message' => "You cannot update shipping status as it is already {$order->shipping_status}.",
            ]);
        }

        $order->shipping_status = $request->shipping_status;
        if ($request->shipping_status === 'delivered') {
            $order->status = 'completed';
        }
        $order->save();

        $order->load([
            'distributor',
            'billingAddress',
            'shippingAddress',
            'orderProducts.product',
        ]);

        try {
            if (!empty($order->distributor?->email)) {
                Mail::to($order->distributor->email)->send(new DistributorShippingStatusMail($order));
            }
        } catch (\Exception $e) {
            Log::error('Distributor shipping status email sending failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Shipping status updated to {$order->shipping_status} successfully.",
            'data'    => $order,
        ]);
    }

    // public function updateDistributorDetails(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'distributor_id' => 'required|exists:distributors,id',
    //         'company_name' => 'required|string|max:255',
    //         'contact_person_name' => 'required|string|max:255',
    //         'gst_number' => 'required|string|max:50',
    //         'area' => 'nullable|string|max:255',
    //         'mobile_no' => 'required|digits_between:10,15',
    //         'alternate_mobile_no' => 'nullable|digits_between:10,15',
    //         'landline_no' => 'nullable|string|max:20',
    //         'email' => 'required|email|max:255',
    //         'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ], [
    //         'company_name.required' => 'Company name is required.',
    //         'contact_person_name.required' => 'Contact person name is required.',
    //         'gst_number.required' => 'GST number is required.',
    //         'mobile_no.required' => 'Mobile number is required.',
    //         'mobile_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
    //         'email.required' => 'Email is required.',
    //         'email.email' => 'Please enter a valid email address.',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation failed.',
    //             'errors'  => $validator->errors(),
    //         ]);
    //     }

    //     $distributor = Distributor::find($request->distributor_id);
    //     if (!$distributor) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Distributor not found.',
    //         ]);
    //     }

    //     $data = [
    //         'company_name' => $request->company_name,
    //         'name' => $request->contact_person_name,
    //         'gst_number' => $request->gst_number,
    //         'area' => $request->area,
    //         'phone_no' => $request->mobile_no,
    //         'alternate_mobile_no' => $request->alternate_mobile_no,
    //         'landline_no' => $request->landline_no,
    //         'email' => $request->email,
    //     ];

    //     $path = public_path('images/profile');
    //     if ($request->hasFile('logo')) {
    //         $file = $request->file('logo');
    //         $filename = $request->distributor_id.'_'.time() . '_' . $file->getClientOriginalName();
    //         $file->move($path, $filename);
    //         $data['logo'] = $filename;
    //     }
    //     $distributor->update($data);

    //     return response()->json([
    //         'success' => true,
    //         'message' => "Distributor has been " . ($distributor->is_active ? "activated" : "deactivated") . " successfully.",
    //         'data'    => $distributor,
    //     ]);
    // }

    public function updateDistributorStatus(Request $request) {
        $validator = Validator::make($request->all(), [
            'distributor_id' => 'required|exists:distributors,id',
            'status' => 'required|in:0,1',
        ], [
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either 0 (deactivated) or 1 (activated).',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $distributor = Distributor::find($request->distributor_id);
        if (!$distributor) {
            return response()->json([
                'success' => false,
                'message' => 'Distributor not found.',
            ]);
        }

        $distributor->is_active = $request->status;
        $distributor->save();

        return response()->json([
            'success' => true,
            'message' => "Distributor has been " . ($distributor->is_active ? "activated" : "deactivated") . " successfully.",
            'data'    => $distributor,
        ]);
    }

    public function getSupportMessageInquiries(Request $request){
        $supportMessages = SupportMessageInquiry::select('id', 'distributor_id', 'subject', 'product_id', 'message', 'created_at')->with('distributor:id,name,company_name,email')->with('product:id,product_name')->latest()->get();
        if(isset($supportMessages) && is_countable($supportMessages) && count($supportMessages) > 0){
            return response()->json([
                'success' => true,
                'message' => 'Support messages retrieved successfully.',
                'data' => $supportMessages
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'No support messages found.',
            ]);
        }
    }

   public function userActivityLogs(Request $request)
    {
        $userType = $request->user_type;
        $actorID = $request->actor_id;
    
        if (!in_array($userType, ['distributor', 'dealer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user type. Allowed values: distributor, dealer.'
            ], 422);
        }

        $table = $userType === 'distributor' ? 'distributors' : 'dealers';

        $validator = Validator::make(
            [
                'actor_id' => $actorID,
            ],
            [
                'actor_id' => 'required|exists:' . $table . ',id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $logs = UserActivityLocation::where('actor_id', $actorID)
            ->where('actor_type', $userType)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'User activity logs retrieved successfully.',
            'data' => $logs
        ]);
    }

     public function getNotifications(Request $request){
        $notifications = DistributorNotification::with('distributor:id,name,company_name,email')->orderBy('id', 'desc')
            ->get()
            ->map(function ($notification) {
                return [
                    'id'         => $notification->id,
                    'distributor'=> $notification->distributor,
                    'order_id'   => $notification->order_id,
                    'title'      => $notification->title,
                    'message'    => $notification->message,
                    'is_read'    => (bool) $notification->is_read,
                    'time'       => $notification->created_at,
                ];
            });

        if(isset($notifications) && is_countable($notifications) && count($notifications) > 0){
            return response()->json([
                'success'      => true,
                'message'      => 'Notifications fetched successfully.',
                //'unread_count' => $unreadCount,
                'data'         => $notifications,
            ]);
        }else{  
            return response()->json([
                'success'      => false,
                'message'      => 'Notifications are not available.',
            ]);
        }
    }
}
 