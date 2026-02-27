<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Post, Subcategory, Product};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function addUpdatePost(Request $request){
        $postCategories = config('global_values.post_category');
        $validator = Validator::make($request->all(), [
            'post_id' => 'nullable|exists:posts,id',
            'title' => 'required',
            'category' => 'required|in:' . implode(',', array_keys($postCategories)),
            'media_file' => 'required|file|max:2048',
            'month' => 'nullable',
            'year' => 'nullable',
            'description' => 'required',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'post_id.exists' => 'The selected post is invalid.',
            'title.required' => 'The title field is required.',
            'category.required' => 'Please select a category.',
            'media_file.required' => 'Please upload a PDF file.',
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
            $file = $request->file('media_file');
            $fileName = $post->id . '_' . time() . '_' . $file->getClientOriginalName();
            $destinationPath = storage_path('app/public/post_media');
            // Create folder if not exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            // Delete old file if exists
            if ($post->media_file) {
                $oldFile = $destinationPath . '/' . $post->media_file;
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }
            // Move new file
            $file->move($destinationPath, $fileName);
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
// $fullPath = storage_path('app/' . $post->media_file);
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


}
