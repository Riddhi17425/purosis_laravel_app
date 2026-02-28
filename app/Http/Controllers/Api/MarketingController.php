<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Brochure, Subcategory, Product, Video, Leaflet};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MarketingController extends Controller
{
    public function addUpdateBrochure(Request $request){
        $brochureCategories = config('global_values.brochure_category');
        $validator = Validator::make($request->all(), [
            'brochure_id' => 'nullable|exists:brochures,id',
            'title' => 'required',
            'category' => 'required|in:' . implode(',', array_keys($brochureCategories)),
            'media_file' => 'required|file|mimes:pdf|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'brochure_id.exists' => 'The selected brochure is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'media_file.required' => 'Please upload a PDF file.',
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

    public function addUpdateVideo(Request $request){
        $videoCategories = config('global_values.video_category');
        $videoTypes = config('global_values.video_type');
        $validator = Validator::make($request->all(), [
            'video_id' => 'nullable|exists:videos,id',
            'title' => 'required',
            'category' => 'required|in:' . implode(',', array_keys($videoCategories)),
            'type' => 'required|in:' . implode(',', array_keys($videoTypes)),
            'media_file' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'thumbnail_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required|max:1000',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'video_id.exists' => 'The selected brochure is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'media_file.required' => 'Please upload a PDF file.',
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

        $videos = Video::select('id', 'title', 'category', 'type', 'media_file', 'thumbnail_image', 'month', 'year', 'description', 'is_featured');
        if(isset($request->video_id) && $request->video_id != ''){
            $videos = $videos->where('id', $request->video_id);
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

    }

    public function getLeaflets(Request $request){
        
    }


}
