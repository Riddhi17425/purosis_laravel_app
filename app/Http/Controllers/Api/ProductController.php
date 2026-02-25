<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Category, Subcategory, Product};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function addUpdateCategory(Request $request){
        $categorySlug = Str::slug($request->category_name);
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'category_name' => ['required', function ($attribute, $value, $fail) use ($categorySlug, $request) {
                    $query = DB::table('categories')->where('category_url', $categorySlug);
                    
                    // If updating, exclude current product
                    if ($request->category_id) {
                        $query->where('id', '<>', $request->category_id);
                    }
                    if ($query->exists()) {
                        $fail('The category name is already used. Please choose a different name.');
                    }
                }
            ],
            'short_description' => 'nullable',
        ], [
            'category_name.required' => 'Please enter the category name.',
            'category_id.required' => 'Please enter the category ID.',
            'short_description.required' => 'Please enter the Description.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->category_id) {
            $category = Category::find($request->category_id);
        } else {
            $category = new Category();
        }
        $category->category_name = $request->category_name;
        $category->category_url = $categorySlug;
        $category->short_description = $request->short_description ?? null;
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Category stored Successfully',
            'data' => $category
        ]);
    }

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

    public function addUpdateSubCategory(Request $request){
        $subCategorySlug = Str::slug($request->category_name);
        $validator = Validator::make($request->all(), [
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'category_id' => 'required|exists:categories,id',            
            'sub_category_name' => ['required', function ($attribute, $value, $fail) use ($subCategorySlug, $request) {
                    $query = DB::table('sub_categories')->where('sub_category_url', $subCategorySlug);
                    // If updating, exclude current product
                    if ($request->sub_category_id) {
                        $query->where('id', '<>', $request->sub_category_id);
                    }
                    if ($query->exists()) {
                        $fail('The Sub Category name is already used. Please choose a different name.');
                    }
                }
            ],
            'short_description' => 'nullable',
        ], [
            'sub_category_name.required' => 'Please enter the category name.',
            'sub_category_id.required' => 'Please enter the category ID.',
            'short_description.required' => 'Please enter the Description.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->sub_category_id) {
            $subCategory = SubCategory::find($request->sub_category_id);
        } else {
            $subCategory = new SubCategory();
        }
        $subCategory->category_id = $request->category_id;
        $subCategory->sub_category_name = $request->sub_category_name;
        $subCategory->sub_category_url = $subCategorySlug;
        $subCategory->short_description = $request->short_description ?? null;
        $subCategory->save();

        return response()->json([
            'success' => true,
            'message' => 'Sub Category stored Successfully',
            'data' => $subCategory
        ]);
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

        $subcategories = Subcategory::select('id', 'category_id', 'sub_category_name')->where('category_id', $request->category_id)->get();
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

    public function addUpdateProduct(Request $request){
        $productSlug = Str::slug($request->product_name);
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:products,id',
            'product_name' => ['required', function ($attribute, $value, $fail) use ($productSlug, $request) {
                    $query = DB::table('products')->where('product_url', $productSlug);
                    
                    // If updating, exclude current product
                    if ($request->product_id) {
                        $query->where('id', '<>', $request->product_id);
                    }

                    if ($query->exists()) {
                        $fail('The product name is already used. Please choose a different name.');
                    }
                }
            ],
            'category_id' => 'required',
            'description' => 'required',
            'units_per_box' => 'required',
            'weight_per_box' => 'required', 
            'product_img' => 'required|array',
            'product_img.*.color_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'product_name.required' => 'Please enter the product name.',
            'category_id.required' => 'Please enter the category ID.',
            'description.required' => 'Please enter the Description.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        if ($request->product_id) {
            $product = Product::find($request->product_id);
        } else {
            $product = new Product();
        }
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
        $path = public_path('images/product_images');
        $existingImages = $product->product_colors_images ? json_decode($product->product_colors_images, true) : [];
        $productImgArr = [];
        if(isset($request->product_img) && is_countable($request->product_img) && count($request->product_img) > 0){
            // If updating, delete existing images from disk
            if ($request->product_id && count($existingImages) > 0) {
                foreach ($existingImages as $img) {
                    if (!empty($img['image']) && file_exists($path . '/' . $img['image'])) {
                        @unlink($path . '/' . $img['image']);
                    }
                }
            }
            foreach($request->product_img as $index => $imgData){
                $colorName = $imgData['color_name'] ?? null;
                $colorCode = $imgData['color_code'] ?? null;
                if (isset($imgData['color_image'])) {
                    $imageFile = $request->file("product_img.$index.color_image");
                    $originalFilename = $product->id.'_'.$index. '_'. time() .'_' . $imageFile->getClientOriginalName();
                    $imageFile->move($path, $originalFilename);

                    $productImgArr[] = [
                        'color_name' => $colorName,
                        'color_code' => $colorCode,
                        'image' => $originalFilename,
                    ];
                }

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
            'serach' => 'nullable',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }
        $products = Product::query();
        if(isset($request->product_id) && $request->product_id != ''){
            $products = $products->where('id', $request->product_id)->select('id', 'category_id', 'sub_category_id', 'product_name', 'product_description', 'product_colors_images', 'units_per_box', 'weight_per_box', 'length', 'width', 'height', 'technical_video_url')->with(['category:id,category_name', 'subCategory:id,category_id,sub_category_name'])->get();
            $products = $products->map(function ($product) {
                $productColors = collect($product->product_colors_images ?? [])->map(function ($img) {
                    return [
                        'color_name' => $img['color_name'] ?? null,
                        'color_code' => $img['color_code'] ?? null,
                        'image' => isset($img['image']) ? url('images/product_images/' . $img['image']) : null
                    ];
                });

                $product->product_colors_images = $productColors;
                return $product;
            });
        }else{
            $products = $products->select('id', 'product_name', 'product_description');
            if (isset($request->search)) {
                $search = $request->search;
                $products = $products->where(function ($query) use ($search) {
                    $query->where('product_name', 'like', "%{$search}%")->orWhere('product_description', 'like', "%{$search}%");
                });
            }
            if (isset($request->category_id)) {
                $products = $products->where('category_id', $request->category_id);
            }
            if (isset($request->sub_category_id)) {
                $products = $products->where('sub_category_id', $request->sub_category_id);
            }
            $products = $products->get();
        }
        
        if(isset($products) && is_countable($products) && count($products) > 0){
            foreach($products as $key => $val){
                
            }
            return response()->json([
                'success' => true,
                'message' => "Products are get Successfully",
                'data' => $products
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Products are not Found',
            ]);
        }
    }
}

