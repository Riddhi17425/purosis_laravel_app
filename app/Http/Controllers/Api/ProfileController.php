<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Profile, Subcategory, Product};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function addUpdateProfile(Request $request){
        $profileCategories = config('global_values.profile_category');
        $validator = Validator::make($request->all(), [
            'profile_id' => 'nullable|exists:profiles,id',
            'name' => 'required',
            'mobile' => 'required',
            'email' => 'required',
        ], [
            'profile_id.exists' => 'The selected profile is invalid.',
            'name.required' => 'The name field is required.',
            'mobile.required' => 'The mobile field is required.',
            'mobile.digits_between' => 'Mobile number must be between 10 and 15 digits.',
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

        if ($request->profile_id) {
            $profile = Profile::find($request->profile_id);
        } else {
            $profile = new Profile();
        }
        $profile->name = $request->name ?? null;
        $profile->mobile = $request->mobile ?? null;
        $profile->email = $request->email ?? null;
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile stored Successfully',
            'data' => $profile
        ]);
    }
// $fullPath = storage_path('app/' . $reel->media_file);
    public function getProfiles(Request $request){
        $validator = Validator::make($request->all(), [
            'profile_id' => 'nullable|exists:profiles,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $profiles = Profile::select('id', 'name', 'mobile', 'email');
        if(isset($request->profile_id) && $request->profile_id != ''){
            $profiles = $profiles->where('id', $request->profile_id);
        }
        $profiles = $profiles->get();
        if(isset($profiles) && is_countable($profiles) && count($profiles) > 0){
            
            return response()->json([
                'success' => true,
                'message' => 'Profiles get Successfully',
                'data' => $profiles
            ]);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Profiles are not Found',
            ]);
        }
    }
}
