<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Post,Brochure,Reel,Leaflet, Subcategory, Product};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DistributorController extends Controller
{
    public function getPosts(Request $request){
        $validator = Validator::make($request->all(), [
            'post_id' => 'nullable|exists:posts,id',
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
    
    $leaflets = $leaflets->get();

    if ($leaflets->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Leaflet are not Found',
        ]);
    }

    $basePath = asset('leaflet_media') . '/';

    foreach ($leaflets as $val) {
        // media_file JSON string hai → array mein convert
        $files = $val->media_file ? json_decode($val->media_file, true) : [];

        // agar purana single string hai to array bana do (compatibility)
        if (is_string($files)) {
            $files = [$files];
        }

        // ab $val->media_file mein array of full URLs daal do
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
}
