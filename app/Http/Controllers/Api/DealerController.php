<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;

class DealerController extends Controller
{
    public function updateProfile(Request $request){
        $user = Auth::guard('dealer-api')->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_no' => 'required|string|max:20',
            'gst_number' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'phone_no.required' => 'Phone number is required.',
            'phone_no.digits_between' => 'Phone number must be between 10 and 15 digits.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ]);
        }
        
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone_no' => $request->phone_no,
            'gst_number' => $request->gst_number,
        ];
        $path = public_path('images/dealer_profile');
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = $user->id.'_'.time() . '_' . $file->getClientOriginalName();
            $file->move($path, $filename);
            $data['logo'] = $filename;
        }
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }
}
