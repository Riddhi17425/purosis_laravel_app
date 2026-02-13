<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Category, Subcategory, Product};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function getCategories(Request $reequest){
        $categories = Category::select('id', 'category_name')->get();
        if(isset($categories) && is_countable($categories) && count($categories) > 0){
            return response()->json([
                'success' => true,
                'message' => 'Categories get Successfully',
                'data' => $categories
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Categories are not Found',
            ]);
        }
    }

    public function getSubcategories(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $subcategories = Subcategory::select('id', 'category_id', 'subcate_name')->where('category_id', $request->category_id)->get();
        if(isset($subcategories) && is_countable($subcategories) && count($subcategories) > 0){
            return response()->json([
                'success' => true,
                'message' => 'Sub Categories get Successfully',
                'data' => $subcategories
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Sub Categories are not Found',
            ]);
        }
    }

    public function addProduct(Request $request){
        $validator = Validator::make($request->all(), [
            'product_name' => 'required',
            'category_id' => 'required',
            'description' => 'required',
            'units_per_box' => 'required',
            'weight_per_box' => 'required', 
            'product_img' => 'required|array',
            'product_img.*.color_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'product_name.required' => 'Please enter the product name.',
            'category_id.required' => 'Please enter the category ID.',
            'description.required' => 'Please enter the product Url.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $product = new Product();
        $product->category_id = $request->category_id;
        $product->sub_category_id  = $request->sub_category_id ?? null;
        $product->product_name = $request->product_name;
        $product->product_url = Str::slug($request->product_name);
        $product->product_description = $request->description ?? null;
        $product->units_per_box = $request->units_per_box ?? null;
        $product->weight_per_box = $request->weight_per_box ?? null;
        $product->length = $request->length ?? null;
        $product->width = $request->width ?? null;
        $product->height = $request->height ?? null;
        $product->technical_video_url = $request->technical_video_url ?? null;
        $product->save();
        //PRODUCT IMAGES
        $path = public_path('images/app_product_images');
        $productImgArr = [];
        if(isset($request->product_img) && is_countable($request->product_img) && count($request->product_img) > 0){
            foreach($request->product_img as $index => $imgData){
                $colorName = $imgData['color_name'] ?? null;
                $colorCode = $imgData['color_code'] ?? null;
                $imageFile = $request->file("product_img.$index.color_image");
                $originalFilename = $index.'_'.time() . '_' . $imageFile->getClientOriginalName();
                $imageFile->move($path, $originalFilename);
                $productImgArr[] = [
                    'color_code' => $colorName,
                    'color_name' => $colorCode,
                    'images' => $originalFilename,
                ];
            }
        }
        $product->product_colors_images = $productImgArr;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Product stored Successfully',
            'data' => $product
        ]);
    }

    public function getProducts(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }
        $products = Product::get();
        if(isset($products) && is_countable($products) && count($products) > 0){
            foreach($products as $key => $val){
                
            }
            return response()->json([
                'success' => true,
                'message' => $products,
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Products are not Found',
            ]);
        }
    }
}
