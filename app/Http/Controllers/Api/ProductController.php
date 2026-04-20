<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Category, SubCategory, Product, ProductColor, ProductColorImage, Cart};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $categories = Category::select('id', 'category_name')->whereNull('deleted_at')->get();
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

        $subcategories = SubCategory::select('id', 'category_id', 'sub_category_name')->where('category_id', $request->category_id)->whereNull('deleted_at')->get();
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
            'product_img' => $request->product_id ? 'nullable|array' : 'required|array',
            'product_img.*.color_image' => $request->product_id ? 'nullable' : 'required',
            'product_img.*.color_image.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'specifications' => 'nullable'
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
        $product->specifications = $request->specifications ? implode(', ',$request->specifications) : null;
        $product->save();

        //PRODUCT IMAGES
        if ($request->has('product_img') && is_array($request->product_img) && count($request->product_img) > 0) {
            $path = public_path('images/product_images');

            // On update, remove old colors and their images
            if ($request->product_id) {
                $colorIds = ProductColor::where('product_id', $product->id)->pluck('id');
                ProductColorImage::whereIn('color_id', $colorIds)->each(fn($img) => @unlink($path . '/' . $img->image));
                ProductColorImage::whereIn('color_id', $colorIds)->delete();
                ProductColor::whereIn('id', $colorIds)->delete();
            }

            $colorMap = [];
            foreach ($request->product_img as $index => $imgData) {
                $colorKey = ($imgData['color_name'] ?? '') . '|' . ($imgData['color_code'] ?? '');

                if (!isset($colorMap[$colorKey])) {
                    $colorMap[$colorKey] = ProductColor::create([
                        'product_id' => $product->id,
                        'color_name' => $imgData['color_name'] ?? null,
                        'color_code' => $imgData['color_code'] ?? null,
                    ]);
                }

                if ($request->hasFile("product_img.$index.color_image")) {
                    $imageFiles = $request->file("product_img.$index.color_image");
                    if (!is_array($imageFiles)) {
                        $imageFiles = [$imageFiles];
                    }
                    foreach ($imageFiles as $imgIndex => $file) {
                        $filename = $product->id . '_' . $index . '_' . $imgIndex . '_' . time() . '_' . $file->getClientOriginalName();
                        $file->move($path, $filename);
                        ProductColorImage::create([
                            'color_id' => $colorMap[$colorKey]->id,
                            'image'    => $filename,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product stored Successfully',
            'data' => $product
        ]);
    }

    public function updateProductColorImage(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id'         => 'required|exists:products,id',
            'color_id'           => [
                'nullable',
                Rule::exists('product_colors', 'id')->where('product_id', $request->product_id),
            ],
            'color_name'         => 'required|string',
            'color_code'         => 'required|string',
            'existing_img_names' => 'nullable|array',
            'existing_img_names.*' => 'nullable|string',
            'color_img'          => 'nullable|array',
            'color_img.*'        => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ]);
        }

        $path = public_path('images/product_images');
        $keepImgNames = array_values(array_filter($request->existing_img_names ?? [], fn($n) => !empty($n)));

        if ($request->filled('color_id')) {
            // ===== UPDATE existing color =====
            $color = ProductColor::where('id', $request->color_id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$color) {
                return response()->json([
                    'success' => false,
                    'message' => 'Color not found for this product.',
                ]);
            }

            $color->color_name = $request->color_name;
            $color->color_code = $request->color_code;
            $color->save();

            // Delete images not in existing_img_names
            $imagesToDelete = ProductColorImage::where('color_id', $color->id)
                ->whereNotIn('image', $keepImgNames)
                ->get();

            foreach ($imagesToDelete as $img) {
                @unlink($path . '/' . $img->image);
                $img->delete();
            }
        } else {
            // ===== CREATE new color =====
            $color = ProductColor::create([
                'product_id' => $request->product_id,
                'color_name' => $request->color_name,
                'color_code' => $request->color_code,
            ]);
        }

        // Upload and save new images
        if ($request->hasFile('color_img')) {
            foreach ($request->file('color_img') as $imgIndex => $file) {
                $filename = $request->product_id . '_' . $color->id . '_' . $imgIndex . '_' . time() . '_' . $file->getClientOriginalName();
                $file->move($path, $filename);
                ProductColorImage::create([
                    'color_id' => $color->id,
                    'image'    => $filename,
                ]);
            }
        }

        $color->load('productColorImages');
        $response = [
            'color_id'   => $color->id,
            'color_name' => $color->color_name,
            'color_code' => $color->color_code,
            'images'     => $color->productColorImages->map(fn($img) => url('images/product_images/' . $img->image))->values()->toArray(),
        ];

        return response()->json([
            'success' => true,
            'message' => $request->filled('color_id') ? 'Product color image updated Successfully' : 'Product color image added Successfully',
            'data'    => $response,
        ]);
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
        $products = Product::whereNull('deleted_at')->with(['productColors.productColorImages']);
        if(isset($request->product_id) && $request->product_id != ''){
            $products = $products->where('id', $request->product_id)->select('id', 'category_id', 'sub_category_id', 'product_name', 'product_description', 'units_per_box', 'weight_per_box', 'length', 'width', 'height', 'technical_video_url', 'specifications')->with(['category:id,category_name', 'subCategory:id,category_id,sub_category_name']);
        }else if ($request->has('filter') && $request->filter != '') {
            $filterVal = json_decode($request->filter, true);
            if (!empty($filterVal)) {
                $categories = $filterVal['categories'] ?? [];
                $subCategories = $filterVal['sub_categories'] ?? [];
                //$price = $filterVal['price'] ?? [];
                if (!empty($categories)) {
                    $products->whereIn('category_id', $categories);
                }
                if (!empty($subCategories)) {
                    $products->whereIn('sub_category_id', $subCategories);
                }
            }
        }
        $products = $products->get();
        $products = $products->map(function ($product) {
            $colors = $product->productColors->map(function ($color) {
                return [
                    'color_id' => $color->id,
                    'color_name' => $color->color_name,
                    'color_code' => $color->color_code,
                    'images'     => $color->productColorImages->map(fn($img) => url('images/product_images/' . $img->image))->values()->toArray(),
                ];
            })->values();

            $product->product_colors_images = $colors->isEmpty()
                ? [['color_id' => null, 'color_name' => null, 'color_code' => null, 'images' => []]]
                : $colors;

            unset($product->productColors);
            return $product;
        });
        
        if(isset($products) && is_countable($products) && count($products) > 0){
            foreach($products as $key => $val){
                $val->specifications = explode(', ', $val->specifications);
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

    public function deleteProductColor(Request $request){
        $validator = Validator::make($request->all(), [
            'color_id' => 'required|exists:product_colors,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $images = ProductColorImage::where('color_id', $request->color_id)->get();
        foreach ($images as $image) {
            $path = public_path('images/product_images/' . $image->image);
            if (file_exists($path)) {
                @unlink($path);
            }
            $image->delete();
        }

        ProductColor::where('id', $request->color_id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product color and its images deleted successfully.',
        ]);
    }

    public function deleteCategory(Request $request){
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

        $checkSubCategory = SubCategory::where('category_id', $request->category_id)->whereNull('deleted_at')->exists();
        $checkProduct = Product::where('category_id', $request->category_id)->whereNull('deleted_at')->exists();
        if ($checkSubCategory || $checkProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Category cannot be deleted as it has associated subcategories or products.',
            ]);
        }

        $category = Category::where('id', $request->category_id)->whereNull('deleted_at')->first();
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found or already deleted.',
            ]);   
        }
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function deleteSubCategory(Request $request){
        $validator = Validator::make($request->all(), [
            'sub_category_id' => 'required|exists:sub_categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $checkProduct = Product::where('sub_category_id', $request->sub_category_id)->whereNull('deleted_at')->exists();
        if ($checkProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Sub Category cannot be deleted as it has associated products.',
            ]);
        }

        $subCategory = SubCategory::where('id', $request->sub_category_id)->whereNull('deleted_at')->first();
        if (!$subCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Sub Category not found or already deleted.',
            ]);
        }
        $subCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub Category deleted successfully.',
        ]);
    }

    public function deleteProduct(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $checkCart = Cart::where('product_id', $request->product_id)->first();
        if ($checkCart) {
            return response()->json([
                'success' => false,
                'message' => 'Product cannot be deleted as it is in the cart.',
            ]);
        }
        $product = Product::where('id', $request->product_id)->whereNull('deleted_at')->first();
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or already deleted.',
            ]);
        }
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}

