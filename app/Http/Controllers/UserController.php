<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Office;
use Illuminate\Http\Request;
use App\Traits\UploadImagesTrait;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use UploadImagesTrait;

    public function index()
    {
        return '2';
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
            'type' => 'required|in:user,office',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $phone = $request->phone;
        $password = $request->password;
        $type = $request->type;

        //  البحث حسب نوع الشخص
        if ($type === 'user') {
            $user = User::where('phone', $phone)->first();

            if ($user && Hash::check($password, $user->password)) {
                $token = JWTAuth::fromUser($user);
                return response()->json([
                    'message' => 'Login successful.',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'type' => 'user',
                    'data' => $user,
                ]);
            }
        } elseif ($type === 'office') {
            $office = Office::where('phone', $phone)->first();

            if ($office && Hash::check($password, $office->password)) {
                $token = JWTAuth::fromUser($office); // ← Office يجب أن يطبق JWTSubject
                return response()->json([
                    'message' => 'Login successful.',
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'type' => 'office',
                    'data' => $office,
                ]);
            }
        }

        return response()->json(['message' => 'Invalid phone or password'], 401);
    }


    public function refresh()
    {
        // تجديد التوكن
        $newToken = auth()->refresh();

        // إنشاء استجابة مع التوكن الجديد
        return $this->createNewToken($newToken);
    }
    // طريقة لإنشاء التوكن وإرجاعه كاستجابة
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => auth()->user(),
        ]);
    }
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:3,25',
            'phone' => 'required|digits:10|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'type' => 'required',
            'url' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        DB::beginTransaction();

        try {
            $user = \App\Models\User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'type' => 'user',
            ]);

            if ($request->hasFile('url')) {
                $imageUrl = $this->uploadImage($request->file('url'), 'users');
                $user->image()->create([
                    'url' => $imageUrl,
                ]);
            }

            $token = JWTAuth::fromUser($user);
            if (!$token) {
                DB::rollBack();
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            Auth::login($user);
            DB::commit();

            return response()->json([
                'token' => $this->createNewToken($token),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    //تسجيل خروج
    public function logout(Request $request)
    {
        //الحصول على التوكين ر Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token not provided'], 400);
        }

        try {
            //   محاولة التحقق من التوكين انو هو نشط
            JWTAuth::setToken($token)->invalidate();

            // إرجاع رد بعد إلغاء التوكين
            return response()->json(['message' => 'User successfully signed out'], 200);
        } catch (Exception $e) {
            // في حال حدوث خطأ في التحقق من التوكين
            return response()->json(['error' => 'Failed to log out', 'message' => $e->getMessage()], 500);
        }
    }

    public function getProfile()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'المستخدم غير مصادق عليه، يرجى إرسال التوكن بشكل صحيح.'
            ], 401);
        }

        return response()->json([
            'message' => 'تم جلب معلومات المستخدم بنجاح.',
            'user' => $user->load('image'),
        ]);
    }

    //تابع يقوم بتعديل معلومات المستخدم
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'المستخدم غير مصادق عليه، الرجاء إرسال التوكن بشكل صحيح.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|between:3,25',
            'phone' => 'sometimes|required|digits:10|unique:users,phone,' . $user->id,
            'password' => 'sometimes|required|string|min:8|confirmed',
            'url' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $isUpdated = false;

            if ($request->filled('name') && $request->name !== $user->name) {
                $user->name = $request->name;
                $isUpdated = true;
            }

            if ($request->filled('phone') && $request->phone !== $user->phone) {
                $user->phone = $request->phone;
                $isUpdated = true;
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
                $isUpdated = true;
            }

            if ($request->hasFile('url')) {
                // حذف الصورة القديمة إن وُجدت
                if ($user->image) {
                    $oldPath = public_path('pictures/' . $user->image->url);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                    $user->image()->delete();
                }

                // رفع الصورة الجديدة
                $path = $this->uploadImage($request->file('url'), 'users');
                $user->image()->create(['url' => $path]);
                $isUpdated = true;
            }

            if (!$isUpdated) {
                return response()->json([
                    'message' => 'لم تقم بتعديل أي معلومات.',
                    'user' => $user->load('image'),
                ]);
            }

            $user->save();
            DB::commit();

            return response()->json([
                'message' => 'تم تحديث الملف الشخصي بنجاح.',
                'user' => $user->load('image'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'حدث خطأ أثناء تحديث البيانات.',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }
}
