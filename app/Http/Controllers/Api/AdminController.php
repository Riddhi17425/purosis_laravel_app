<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\{Admin};

class AdminController extends Controller
{
    public function sendAdminOtp(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_no' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $admin = Admin::where('phone_no', $request->phone_no)->first();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Mobile number not registered'
            ], 404);
        }

        $otp = rand(1000, 9999);
        $admin->update([
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

    public function verifyAdminOtp(Request $request){
        $validator = Validator::make($request->all(), [
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

        $admin = Admin::where('phone_no', $request->phone_no)->first();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'admin not found'
            ], 404);
        }
        if ($admin->otp != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ]);
        }
        if (now()->gt($admin->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired'
            ]);
        }

        // OTP verified — generate token
        $token = $admin->createToken('admin-token')->plainTextToken;
        // Clear OTP after success
        $admin->update([
            'otp' => null,
            'otp_expires_at' => null
        ],);

        $admin->token = $token;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $admin
        ]);
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
    
    public function addUpdateProfile(Request $request){
        $profileCategories = config('global_values.profile_category');
        $validator = Validator::make($request->all(), [
            'admin_id' => 'nullable|exists:admins,id',
            'name' => 'required',
            'phone_no' => 'required',
            'email' => 'required',
        ], [
            'admin_id.exists' => 'The selected admin is invalid.',
            'name.required' => 'The name field is required.',
            'phone_no.required' => 'The mobile field is required.',
            'phone_no.digits_between' => 'Mobile number must be between 10 and 15 digits.',
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

        if ($request->admin_id) {
            $profile = Admin::find($request->admin_id);
        } else {
            $profile = new Admin();
        }
        $profile->name = $request->name ?? null;
        $profile->phone_no = $request->phone_no ?? null;
        $profile->email = $request->email ?? null;
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile stored Successfully',
            'data' => $profile
        ]);
    }

    public function getProfiles(Request $request){
        $validator = Validator::make($request->all(), [
            'admin_id' => 'nullable|exists:admins,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ]);
        }

        $profiles = Admin::select('id', 'name', 'phone_no', 'email');
        if(isset($request->admin_id) && $request->admin_id != ''){
            $profiles = $profiles->where('id', $request->admin_id);
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
 