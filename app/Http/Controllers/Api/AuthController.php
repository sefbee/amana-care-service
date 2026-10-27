<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Admin\Models\User;
use App\Admin\Services\FileUploadService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected FileUploadService $fileUploadService) {}

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userid' => 'string|required|max:10',
            'password' => 'required|string',
            'device_data' => 'string|required',
        ]);

        if ($validator->fails()) {
            return response()->error('The given data was invalid', $validator->errors(), 422);
        }

        try {
            $user = User::query()
                ->where('userid', $request->userid)
                ->first();

            if (!$user) {
                return response()->error('Invalid User ID or Password', null, 401);
            }

            $password_matched = Hash::check($request->password, $user->password);

            if ($password_matched == false) {
                return response()->error('Invalid User ID or Password', null, 401);
            }

            if ($user->is_active == false) {
                return response()->error('User deactivated. Contact system admin', null, 401);
            }

            if (env('APP_ENV') == 'production') {
                $user->tokens()->delete();
            }

            $token = $user->createToken($request->device_data ?? 'API token', ['*']);

            $user->last_login = Carbon::now()->toDateTimeString();
            $user->save();

            $user->load([
                'user_type:id,title,slug',
            ]);

            $data = [
                'user' => $user,
                'token' => $token->plainTextToken,
            ];

            return response()->success($data, 'Successfully logged in', 200);
        } catch (\Exception $e) {
            return response()->error($e->getMessage(), null, $e->getCode());
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->success(['success' => 'success'], 'Successfully logged out', 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        $user->load([
            'user_type:id,title,slug',
        ]);

        return response()->success(['user' => $user], 'User data found', 200);
    }

    public function updateProfile(Request $request)
    {
        $id = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'gender' => 'nullable|integer|in:1,2,3',
            'dob' => 'nullable|date',
            'marital_status' => 'nullable|integer|in:1,2,3',
            'date_of_marriage' => 'nullable|date',
        ]);

        try {
            throw_if(
                $validator->fails(),
                new \Exception($validator->errors(), 422)
            );

            $data = [];
            $user = User::find($id);
            $data['gender'] = $request->gender ?? $user->gender;
            $data['dob'] = $request->dob ?? $user->dob;
            $data['marital_status'] = $request->marital_status ?? $user->marital_status;
            $data['date_of_marriage'] = $request->date_of_marriage ?? $user->date_of_marriage;

            $user->update($data);

            return response()->success(null, 'Profile updated successfully.', 200);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), null, 500);
        }
    }

    public function updateProfilePhoto(Request $request)
    {
        $id = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            throw_if(
                $validator->fails(),
                new \Exception($validator->errors(), 422)
            );

            if ($request->hasFile('profile_photo')) {
                $photo_path = $this->fileUploadService->upload('profile_photo', 'users/photo');
                $data['photo'] = $photo_path;
            }

            User::find($id)->update($data);

            return response()->success(null, 'Profile photo updated successfully.', 200);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), null, 500);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'old_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:6',
                'confirm_new_password' => 'required|string|min:6',
            ]);

            if ($request->new_password != $request->confirm_new_password) {
                return response()->error('New password and confirm password does not matched', null, 422);
            }

            $id = auth()->user()->id;

            $user = User::find($id);
            throw_unless($user, new Exception('Invalid User.'), 401);

            throw_if(
                ! Hash::check($request->old_password, $user->password),
                ValidationException::withMessages([
                    'old_password' => ['The old password is incorrect.'],
                ])
            );

            $user->update([
                'password' => Hash::make($request->new_password),
                'is_password_changed' => 1,
            ]);

            $user->tokens()->delete();

            return response()->success(null, 'Password changed successfully. Please log in again.', 200);
        } catch (\Throwable $th) {
            return response()->error($th->getMessage(), null, 500);
        }
    }
}
