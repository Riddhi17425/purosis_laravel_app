<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Brochure, Subcategory, Product, Video, Leaflet, Post, Reel};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarketingController extends Controller
{
    public function addUpdateBrochure(Request $request){
        //$brochureCategories = config('global_values.brochure_category');
        $validator = Validator::make($request->all(), [
            'brochure_id' => 'nullable|exists:brochures,id',
            'title' => 'required',
            //'category' => 'required|in:' . implode(',', array_keys($brochureCategories)),
            'category' => 'required|exists:products,id',
            'media_file' => 'required_without:brochure_id|file|mimes:pdf|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'brochure_id.exists' => 'The selected brochure is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'category.exists' => 'The selected category is invalid.',
            'media_file.required_without' => 'Please upload a PDF file.',
            'media_file.file' => 'The uploaded file must be a valid file.',
            'media_file.mimes' => 'Only PDF files are allowed.',
            'media_file.max' => 'The PDF file must not exceed 2MB in size.',
            'description.required' => 'The description field is required.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->brochure_id) {
            $brochure = Brochure::find($request->brochure_id);
        } else {
            $brochure = new Brochure();
        }
        $brochure->title = $request->title;
        $brochure->category = $request->category ?? null;
        $brochure->month = $request->month ?? null;
        $brochure->year = $request->year ?? null;
        $brochure->description = $request->description ?? null;
        $brochure->is_featured = $request->is_featured ?? 0;
        $brochure->save();

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $fileName = $brochure->id . '_' . time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('brochure_media');
            // Create folder if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Delete old file if exists
            if ($brochure->media_file) {
                $oldFile = $destinationPath . '/' . $brochure->media_file;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Move new file
            $file->move($destinationPath, $fileName);
            // Save new filename in DB
            $brochure->media_file = $fileName;
            $brochure->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Brochure stored Successfully',
            'data' => $brochure
        ]);
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

        $brochures = Brochure::select('id', 'title', 'category', 'media_file', 'month', 'year', 'description', 'is_featured')->whereNull('deleted_at')->with('category:id,product_name');
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

    public function addUpdateVideo(Request $request){
        //$videoCategories = config('global_values.video_category');
        $videoTypes = config('global_values.video_type');
        $validator = Validator::make($request->all(), [
            'video_id' => 'nullable|exists:videos,id',
            'title' => 'required',
            //'category' => 'required|in:' . implode(',', array_keys($videoCategories)),
            'category' => 'required|exists:products,id',
            'type' => 'required|in:' . implode(',', array_keys($videoTypes)),
            'media_file' => 'required_without:video_id|mimes:jpeg,png,jpg,webp,mp4,avi,mov,mkv|max:4096',
            'thumbnail_image' => 'required_without:video_id|mimes:jpeg,png,jpg,webp|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required|max:1000',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'video_id.exists' => 'The selected video is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'category.exists' => 'The selected category is invalid.',
            'media_file.required_without' => 'Please upload a Image file.',
            'media_file.file' => 'The uploaded file must be a valid file.',
            'media_file.mimes' => 'Only Image files are allowed.',
            'media_file.max' => 'The Image file must not exceed 4MB in size.',
            'description.required' => 'The description field is required.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->video_id) {
            $video = Video::find($request->video_id);
        } else {
            $video = new Video();
        }
        $video->title = $request->title;
        $video->category = $request->category ?? null;
        $video->type = $request->type ?? null;
        $video->month = $request->month ?? null;
        $video->year = $request->year ?? null;
        $video->description = $request->description ?? null;
        $video->is_featured = $request->is_featured ?? 0;
        $video->save();

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $fileName = $video->id . '_' . time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('images/video_media_file');
            // Create folder if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Delete old file if exists
            if ($video->media_file) {
                $oldFile = $destinationPath . '/' . $video->media_file;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Move new file
            $file->move($destinationPath, $fileName);
            // Save new filename in DB
            $video->media_file = $fileName;
            $video->save();
        }

        if ($request->hasFile('thumbnail_image')) {
            $file = $request->file('thumbnail_image');
            $fileName = $video->id . '_' . time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('images/video_thumbnail');
            // Create folder if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Delete old file if exists
            if ($video->thumbnail_image) {
                $oldFile = $destinationPath . '/' . $video->thumbnail_image;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Move new file
            $file->move($destinationPath, $fileName);
            // Save new filename in DB
            $video->thumbnail_image = $fileName;
            $video->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Video stored Successfully',
            'data' => $video
        ]);
    }

    public function getVideos(Request $request){
        $validator = Validator::make($request->all(), [
            'video_id' => 'nullable|exists:videos,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $videos = Video::select('id', 'title', 'category', 'type', 'media_file', 'thumbnail_image', 'month', 'year', 'description', 'is_featured')->with('category:id,product_name');
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
                $thumbnailPath = asset('images/video_thumbnail');
                $val->media_file = $mediaPath.'/'.$val->media_file;
                $val->thumbnail_image = $thumbnailPath.'/'.$val->thumbnail_image;
            }
            return response()->json([
                'success' => true,
                'message' => 'Videos get Successfully',
                'data' => $videos
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Videos are not Found',
            ]);
        }
    }

    public function addUpdateLeaflet(Request $request){
        //$leafletCategories = config('global_values.leaflet_category');
        $validator = Validator::make($request->all(), [
            'leaflet_id' => 'nullable|exists:leaflets,id',
            'title' => 'required',
            //'category' => 'required|in:' . implode(',', array_keys($leafletCategories)),
            'category' => 'required|exists:products,id',
            'media_file'     => 'required_without:leaflet_id|array|min:1',       
            'media_file.*'   => 'required_without:leaflet_id|image|mimes:jpeg,png,jpg,webp|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'leaflet_id.exists' => 'The selected leaflet is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'category.exists' => 'The selected category is invalid.',
            'media_file.required_without'    => 'Please upload at least one PDF file.',
            'media_file.array'       => 'Media files must be sent as an array.',
            'media_file.min'         => 'Please upload at least one PDF file.',
            'media_file.*.mimes'     => 'Only PDF files are allowed.',
            'media_file.*.max'       => 'Each PDF must not exceed 2MB.',
            'description.required' => 'The description field is required.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->leaflet_id) {
            $leaflet = Leaflet::find($request->leaflet_id);
        } else {
            $leaflet = new Leaflet();
        }
        $leaflet->title = $request->title;
        $leaflet->category = $request->category ?? null;
        $leaflet->month = $request->month ?? null;
        $leaflet->year = $request->year ?? null;
        $leaflet->description = $request->description ?? null;
        $leaflet->is_featured = $request->is_featured ?? 0;
        $leaflet->save();

        if ($request->hasFile('media_file')) {
            $destinationPath = public_path('leaflet_media');
        }
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $existingFiles = $leaflet->media_file ? json_decode($leaflet->media_file, true) : [];
        $newFiles = [];

        foreach ($request->file('media_file') as $file) {
            if (!$file->isValid()) continue;

            $fileName = $leaflet->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $file->move($destinationPath, $fileName);
            $newFiles[] = $fileName;
        }

        // Merge old + new (or replace — your choice)
        // Option A: replace completely
        $leaflet->media_file = json_encode($newFiles);

        // Option B: append new files (uncomment if preferred)
        // $allFiles = array_merge($existingFiles, $newFiles);
        // $leaflet->media_file = json_encode(array_unique($allFiles));

        $leaflet->save();

        return response()->json([
            'success' => true,
            'message' => 'Leaflet stored Successfully',
            'data' => $leaflet
        ]);
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

        $leaflets = Leaflet::select('id', 'title', 'category', 'media_file', 'month', 'year', 'description', 'is_featured')->whereNull('deleted_at')->with('category:id,product_name');
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

    public function addUpdatePost(Request $request){
        //$postCategories = config('global_values.post_category');
        $validator = Validator::make($request->all(), [
            'post_id' => 'nullable|exists:posts,id',
            'title' => 'required',
            //'category' => 'required|in:' . implode(',', array_keys($postCategories)),
            'category' => 'required|exists:products,id',
            'media_file' => 'required_without:post_id|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'post_id.exists' => 'The selected post is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'category.exists' => 'The selected category is invalid.',
            'media_file.required_without' => 'Please upload a Image file.',
            'media_file.file' => 'The uploaded file must be a valid file.',
            'media_file.mimes' => 'Only files are allowed.',
            'media_file.max' => 'The  file must not exceed 2MB in size.',
            'description.required' => 'The description field is required.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->post_id) {
            $post = Post::find($request->post_id);
        } else {
            $post = new Post();
        }
        $post->title = $request->title;
        $post->category = $request->category ?? null;
        $post->month = $request->month ?? null;
        $post->year = $request->year ?? null;
        $post->description = $request->description ?? null;
        $post->is_featured = $request->is_featured ?? 0;
        $post->save();

        if ($request->hasFile('media_file')) {
            $path = public_path('images/post_images');
            $file = $request->file('media_file');
            $fileName = $post->id . '_' . time() . '_' . $file->getClientOriginalName();
            // Create folder if not exists
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            // Delete old file if exists
            if ($post->media_file) {
                $oldFile = $path . '/' . $post->media_file;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Move new file
            $file->move($path, $fileName);
            // Save new filename in DB
            $post->media_file = $fileName;
            $post->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Post stored Successfully',
            'data' => $post
        ]);
    }

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

        $posts = Post::select('id', 'title', 'category', 'media_file', 'month', 'year', 'description', 'is_featured')->whereNull('deleted_at')->with('category:id,product_name');
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

    public function addUpdateReel(Request $request){
        //$reelCategories = config('global_values.reel_category');
        $validator = Validator::make($request->all(), [
            'reel_id' => 'nullable|exists:reels,id',
            'title' => 'required',
            //'category' => 'required|in:' . implode(',', array_keys($reelCategories)),
            'category' => 'required|exists:products,id',
            'media_file' => 'required_without:reel_id|file|max:2048',
            'thumbnail_image' => 'required_without:reel_id|image|mimes:jpeg,png,jpg,webp|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'reel_id.exists' => 'The selected reel is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'category.exists' => 'The selected category is invalid.',
            'media_file.required_without' => 'Please upload a PDF file.',
            'media_file.file' => 'The uploaded file must be a valid file.',
            'media_file.mimes' => 'Only files are allowed.',
            'media_file.max' => 'The  file must not exceed 2MB in size.',
            'thumbnail_image.required_without' => 'Please upload a thumbnail image.',
            'thumbnail_image.image' => 'Thumbnail must be an image file.',
            'thumbnail_image.mimes' => 'Thumbnail must be jpeg, png, jpg or webp.',
            'thumbnail_image.max' => 'Thumbnail must not exceed 2MB.',
            'description.required' => 'The description field is required.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->reel_id) {
            $reel = Reel::find($request->reel_id);
        } else {
            $reel = new Reel();
        }
        $reel->title = $request->title;
        $reel->category = $request->category ?? null;
        $reel->month = $request->month ?? null;
        $reel->year = $request->year ?? null;
        $reel->description = $request->description ?? null;
        $reel->is_featured = $request->is_featured ?? 0;
        $reel->save();

        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $fileName = $reel->id . '_' . time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('images/reel_media');
            // Create folder if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Delete old file if exists
            if ($reel->media_file) {
                $oldFile = $destinationPath . '/' . $reel->media_file;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Move new file
            $file->move($destinationPath, $fileName);
            // Save new filename in DB
            $reel->media_file = $fileName;
            $reel->save();
        }

        if ($request->hasFile('thumbnail_image')) {
            $file = $request->file('thumbnail_image');
            $fileName = $reel->id . '_' . time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('images/reel_thumbnail');
            // Create folder if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Delete old file if exists
            if ($reel->thumbnail_image) {
                $oldFile = $destinationPath . '/' . $reel->thumbnail_image;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Move new file
            $file->move($destinationPath, $fileName);
            // Save new filename in DB
            $reel->thumbnail_image = $fileName;
            $reel->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Reel stored Successfully',
            'data' => $reel
        ]);
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

        $reels = Reel::select('id', 'title', 'category', 'media_file', 'thumbnail_image', 'month', 'year', 'description', 'is_featured')->whereNull('deleted_at')->with('category:id,product_name');
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

    public function deleteBrochure(Request $request){
        $validator = Validator::make($request->all(), [
            'brochure_id' => 'required|exists:brochures,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $brochure = Brochure::where('id', $request->brochure_id)->whereNull('deleted_at')->first();
        if ($brochure) {
            // Delete associated media file
            if ($brochure->media_file) {
                $filePath = public_path('brochure_media/' . $brochure->media_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $brochure->delete();
            return response()->json([
                'success' => true,
                'message' => 'Brochure deleted successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Brochure not found or already deleted.',
            ]);
        }
    }

    public function deleteVideo(Request $request){
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|exists:videos,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $video = Video::where('id', $request->video_id)->whereNull('deleted_at')->first();
        if ($video) {
            // Delete associated media file
            if ($video->media_file) {
                $filePath = public_path('images/video_media_file/' . $video->media_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Delete associated thumbnail image
            if ($video->thumbnail_image) {
                $thumbPath = public_path('images/video_thumbnail/' . $video->thumbnail_image);
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }
            $video->delete();
            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Video not found or already deleted.',
            ]);
        }
    }

    public function deleteLeaflet(Request $request){
        $validator = Validator::make($request->all(), [
            'leaflet_id' => 'required|exists:leaflets,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $leaflet = Leaflet::where('id', $request->leaflet_id)->whereNull('deleted_at')->first();
        if ($leaflet) {
            // Delete associated media files
            if ($leaflet->media_file) {
                $files = json_decode($leaflet->media_file, true);
                foreach ($files as $file) {
                    $filePath = public_path('leaflet_media/' . $file);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
            $leaflet->delete();
            return response()->json([
                'success' => true,
                'message' => 'Leaflet deleted successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Leaflet not found or already deleted.',
            ]);
        }
    }

    public function deletePost(Request $request){
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $post = Post::where('id', $request->post_id)->whereNull('deleted_at')->first();
        if ($post) {
            // Delete associated media file
            if ($post->media_file) {
                $filePath = public_path('post_media/' . $post->media_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $post->delete();
            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or already deleted.',
            ]);
        }
    }

    public function deleteReel(Request $request){
        $validator = Validator::make($request->all(), [
            'reel_id' => 'required|exists:reels,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $reel = Reel::where('id', $request->reel_id)->whereNull('deleted_at')->first();
        if ($reel) {
            // Delete associated media file
            if ($reel->media_file) {
                $filePath = public_path('images/reel_media/' . $reel->media_file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            // Delete associated thumbnail image
            if ($reel->thumbnail_image) {
                $thumbPath = public_path('images/reel_thumbnail/' . $reel->thumbnail_image);
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }
            $reel->delete();
            return response()->json([
                'success' => true,
                'message' => 'Reel deleted successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Reel not found or already deleted.',
            ]);
        }
    }    



}
