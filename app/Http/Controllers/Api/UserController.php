<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\{Post, Brochure, Reel, Leaflet, Subcategory, Product, Distributor, Dealer, Video};
use DB;

class UserController extends Controller
{
    public function sendUsetOtp(Request $request){
        $userType = config('global_values.user_types');
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:' . implode(',', $userType),
            'phone_no' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $user = '';
        if(isset($request->user_type) && strtolower($request->user_type) == 'distributor'){
            $user = Distributor::where('phone_no', $request->phone_no)->where('is_active', 1)->first();
        }elseif(isset($request->user_type) && strtolower($request->user_type) == 'dealer'){
            $user = Dealer::where('phone_no', $request->phone_no)->first();
            if(!$user && isset($request->phone_no)){
                $user = new Dealer();
                $user->phone_no = $request->phone_no;
                $user->is_active = 1;
                $user->save();
            }else{
                if($user->is_active == 0){
                    $user = '';
                }
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not Found'
            ], 404);
        }

        $otp = rand(1000, 9999);
        $user->update([
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

    public function verifyUserOtp(Request $request){
        $userType = config('global_values.user_types');
        $validator = Validator::make($request->all(), [
            'user_type' => 'required|in:' . implode(',', $userType),
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

        $user = '';
        if(isset($request->user_type) && strtolower($request->user_type) == 'distributor'){
            $user = Distributor::where('phone_no', $request->phone_no)->first();
        }elseif(isset($request->user_type) && strtolower($request->user_type) == 'dealer'){
            $user = Dealer::where('phone_no', $request->phone_no)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        if ($user->otp != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);
        }
        if (now()->gt($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired'
            ]);
        }

        // OTP verified — generate token
        $token = $user->createToken('user-token')->plainTextToken;
        // Clear OTP after success
        $user->update([
            'otp' => null,
            'otp_expires_at' => null
        ],);

        $user->token = $token;
        $user->role = $request->user_type;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $user
        ]);
    }

    public function getPosts(Request $request){
        $validator = Validator::make($request->all(), [
            'post_id' => 'nullable|exists:posts,id',
            'filter' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $posts = Post::select('id', 'title', 'category', 'media_file', 'month', 'year', 'description', 'is_featured');
        if(isset($request->post_id) && $request->post_id != ''){
            $posts = $posts->where('id', $request->post_id);
        }

       if ($request->has('filter') && $request->filter != '') {
            $filterVal = json_decode($request->filter, true);
            if (!empty($filterVal)) {
                $categories = $filterVal['categories'] ?? [];
                $months = $filterVal['months'] ?? [];
                $months = array_map('strtolower', $months);
                $years = $filterVal['years'] ?? [];
                if (!empty($categories)) {
                    $posts->whereIn('category', $categories);
                }
                if (!empty($months)) {
                    $posts->whereIn(DB::raw('LOWER(month)'), $months);
                }
                if (!empty($years)) {
                    $posts->whereIn('year', $years);
                }
            }
        }

        $posts = $posts->get();
        if(isset($posts) && is_countable($posts) && count($posts) > 0){
            foreach($posts as $key => $val){
                $path = asset('images/post_images');
                $val->media_file = $path.'/'.$val->media_file;
            }
            return response()->json([
                'success' => true,
                'message' => 'Posts get Successfully',
                'data' => $posts
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Posts are not Found',
            ]);
        }
    }

    public function getBrochures(Request $request){
        $validator = Validator::make($request->all(), [
            'brochure_id' => 'nullable|exists:brochures,id',
            'filter' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $brochures = Brochure::select('id', 'title', 'category', 'media_file', 'month', 'year', 'description', 'is_featured');
        if(isset($request->brochure_id) && $request->brochure_id != ''){
            $brochures = $brochures->where('id', $request->brochure_id);
        }

        if ($request->has('filter') && $request->filter != '') {
            $filterVal = json_decode($request->filter, true);
            if (!empty($filterVal)) {
                $categories = $filterVal['categories'] ?? [];
                $months = $filterVal['months'] ?? [];
                $months = array_map('strtolower', $months);
                $years = $filterVal['years'] ?? [];
                if (!empty($categories)) {
                    $brochures->whereIn('category', $categories);
                }
                if (!empty($months)) {
                    $brochures->whereIn(DB::raw('LOWER(month)'), $months);
                }
                if (!empty($years)) {
                    $brochures->whereIn('year', $years);
                }
            }
        }

        $brochures = $brochures->get();
        if(isset($brochures) && is_countable($brochures) && count($brochures) > 0){
            foreach($brochures as $key => $val){
                $path = asset('brochure_media');
                $val->media_file = $path.'/'.$val->media_file;
            }
            return response()->json([
                'success' => true,
                'message' => 'Brochures get Successfully',
                'data' => $brochures
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Brochures are not Found',
            ]);
        }
    }

    public function getReels(Request $request){
        $validator = Validator::make($request->all(), [
            'reel_id' => 'nullable|exists:reels,id',
            'filter' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $reels = Reel::select('id', 'title', 'category', 'media_file', 'thumbnail_image', 'month', 'year', 'description', 'is_featured');
        if(isset($request->reel_id) && $request->reel_id != ''){
            $reels = $reels->where('id', $request->reel_id);
        }

        if ($request->has('filter') && $request->filter != '') {
            $filterVal = json_decode($request->filter, true);
            if (!empty($filterVal)) {
                $categories = $filterVal['categories'] ?? [];
                $months = $filterVal['months'] ?? [];
                $months = array_map('strtolower', $months);
                $years = $filterVal['years'] ?? [];
                if (!empty($categories)) {
                    $reels->whereIn('category', $categories);
                }
                if (!empty($months)) {
                    $reels->whereIn(DB::raw('LOWER(month)'), $months);
                }
                if (!empty($years)) {
                    $reels->whereIn('year', $years);
                }
            }
        }

        $reels = $reels->get();
        if(isset($reels) && is_countable($reels) && count($reels) > 0){
            foreach($reels as $key => $val){
                $mediaPath = asset('images/reel_media');
                $thumbnailPath = asset('images/reel_thumbnail');
                $val->media_file = $mediaPath.'/'.$val->media_file;
                $val->thumbnail_image = $thumbnailPath.'/'.$val->thumbnail_image;
            }
            return response()->json([
                'success' => true,
                'message' => 'Reels get Successfully',
                'data' => $reels
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Reels are not Found',
            ]);
        }
    }

    public function getLeaflets(Request $request){
        $validator = Validator::make($request->all(), [
            'leaflet_id' => 'nullable|exists:leaflet,id',
            'filter' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $leaflets = Leaflet::select('id', 'title', 'category', 'media_file', 'month', 'year', 'description', 'is_featured');
        if (isset($request->leaflet_id) && $request->leaflet_id != '') {
            $leaflets = $leaflets->where('id', $request->leaflet_id);
        }

        if ($request->has('filter') && $request->filter != '') {
            $filterVal = json_decode($request->filter, true);
            if (!empty($filterVal)) {
                $categories = $filterVal['categories'] ?? [];
                $months = $filterVal['months'] ?? [];
                $months = array_map('strtolower', $months);
                $years = $filterVal['years'] ?? [];
                if (!empty($categories)) {
                    $leaflets->whereIn('category', $categories);
                }
                if (!empty($months)) {
                    $leaflets->whereIn(DB::raw('LOWER(month)'), $months);
                }
                if (!empty($years)) {
                    $leaflets->whereIn('year', $years);
                }
            }
        }
        
        $leaflets = $leaflets->get();
        if ($leaflets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Leaflet are not Found',
            ]);
        }

        $basePath = asset('leaflet_media') . '/';
        foreach ($leaflets as $val) {
            $files = $val->media_file ? json_decode($val->media_file, true) : [];
            if (is_string($files)) {
                $files = [$files];
            }
            $val->media_file = array_map(function ($filename) use ($basePath) {
                return $basePath . $filename;
            }, $files);
        }

        return response()->json([
            'success' => true,
            'message' => 'Leaflets get Successfully',
            'data' => $leaflets
        ]);
    }

    public function getVideos(Request $request){
        $validator = Validator::make($request->all(), [
            'video_id' => 'nullable|exists:videos,id',
            'filter' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $videos = Video::select('id', 'title', 'category', 'type', 'media_file', 'thumbnail_image', 'month', 'year', 'description', 'is_featured');
        if(isset($request->video_id) && $request->video_id != ''){
            $videos = $videos->where('id', $request->video_id);
        }

        if ($request->has('filter') && $request->filter != '') {
            $filterVal = json_decode($request->filter, true);
            if (!empty($filterVal)) {
                $categories = $filterVal['categories'] ?? [];
                $type = $filterVal['type'] ?? [];
                $months = $filterVal['months'] ?? [];
                $months = array_map('strtolower', $months);
                $years = $filterVal['years'] ?? [];
                if (!empty($categories)) {
                    $videos->whereIn('category', $categories);
                }
                if (!empty($type)) {
                    $videos->whereIn('type', $type);
                }
                if (!empty($months)) {
                    $videos->whereIn(DB::raw('LOWER(month)'), $months);
                }
                if (!empty($years)) {
                    $videos->whereIn('year', $years);
                }
            }
        }

        $videos = $videos->get();
        if(isset($videos) && is_countable($videos) && count($videos) > 0){
            foreach($videos as $key => $val){
                $mediaPath = asset('images/video_media_file');
                $thumbnailPath = asset('images/video_media_file');
                $val->media_file = $mediaPath.'/'.$val->media_file;
                $val->thumbnail_image = $thumbnailPath.'/'.$val->thumbnail_image;
            }
            return response()->json([
                'success' => true,
                'message' => 'Videos are get Successfully',
                'data' => $videos
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Videos are not Found',
            ]);
        }
    }

    public function getSubCatBasedOnCat(Request $request){

    }

    public function getProducts(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'filter' => 'nullable'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $products = Product::select('id', 'title', 'category', 'type', 'media_file', 'thumbnail_image', 'month', 'year', 'description', 'is_featured');
        if(isset($request->product_id) && $request->product_id != ''){
            $products = $products->where('id', $request->product_id);
        }

        if ($request->has('filter') && $request->filter != '') {
            $filterVal = json_decode($request->filter, true);
            if (!empty($filterVal)) {
                $categories = $filterVal['categories'] ?? [];
                $type = $filterVal['type'] ?? [];
                $months = $filterVal['months'] ?? [];
                $months = array_map('strtolower', $months);
                $years = $filterVal['years'] ?? [];
                if (!empty($categories)) {
                    $videos->whereIn('category', $categories);
                }
                if (!empty($type)) {
                    $videos->whereIn('type', $type);
                }
                if (!empty($months)) {
                    $videos->whereIn(DB::raw('LOWER(month)'), $months);
                }
                if (!empty($years)) {
                    $videos->whereIn('year', $years);
                }
            }
        }

        $videos = $videos->get();
        if(isset($videos) && is_countable($videos) && count($videos) > 0){
            foreach($videos as $key => $val){
                $mediaPath = asset('images/video_media_file');
                $thumbnailPath = asset('images/video_media_file');
                $val->media_file = $mediaPath.'/'.$val->media_file;
                $val->thumbnail_image = $thumbnailPath.'/'.$val->thumbnail_image;
            }
            return response()->json([
                'success' => true,
                'message' => 'Videos are get Successfully',
                'data' => $videos
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Videos are not Found',
            ]);
        }
    }

    public function getDetails(Request $request){
        $data = [];
        $data['brochure_category'] = $this->formatKeyValue(config('global_values.brochure_category'));
        $data['post_category'] = $this->formatKeyValue(config('global_values.post_category'));
        $data['reel_category'] = $this->formatKeyValue(config('global_values.reel_category'));
        $data['video_category'] = $this->formatKeyValue(config('global_values.video_category'));
        $data['video_type'] = $this->formatKeyValue(config('global_values.video_type'));
        $data['user_types'] = config('global_values.user_types');

        return response()->json([
            'success' => true,
            'message' => 'Details get successful',
            'data' => $data
        ]);
    }

    protected function formatKeyValue($array){
        $result = [];
        foreach ($array as $key => $value) {
            $result[] = [
                'key' => $key,
                'value' => $value
            ];
        }
        return $result;
    }

}
