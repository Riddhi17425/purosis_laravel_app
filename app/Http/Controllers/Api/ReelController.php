<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Reel, Subcategory, Product};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReelController extends Controller
{
    public function addUpdateReel(Request $request){
        $reelCategories = config('global_values.reel_category');
        $validator = Validator::make($request->all(), [
            'reel_id' => 'nullable|exists:reels,id',
            'title' => 'required',
            'category' => 'required|in:' . implode(',', array_keys($reelCategories)),
            'media_file' => 'required|file|max:2048',
            'thumbnail_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'reel_id.exists' => 'The selected reel is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'media_file.required' => 'Please upload a PDF file.',
            'media_file.file' => 'The uploaded file must be a valid file.',
            'media_file.mimes' => 'Only files are allowed.',
            'media_file.max' => 'The  file must not exceed 2MB in size.',
            'thumbnail_image.required' => 'Please upload a thumbnail image.',
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
            $destinationPath = storage_path('app/public/reel_media');
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
            $destinationPath = storage_path('app/public/reel_thumbnail');
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
// $fullPath = storage_path('app/' . $reel->media_file);
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

}
