<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Property;
use App\Models\Office;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{

    public function addToFavorites(Request $request)
    {
        $user = Auth::user();

        // دعم نوعين: property أو office
        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|in:property,office',
        ]);

        // تعيين النوع الصحيح للكلاس
        $favoriteableType = match ($request->type) {
            'property' => Property::class,
            'office' => Office::class,
        };

        // التحقق من وجود العنصر
        $model = $favoriteableType::find($request->id);
        if (!$model) {
            return response()->json(['message' => 'العنصر غير موجود'], 404);
        }

        // التحقق إذا كان مضاف مسبقًا
        $exists = Favorite::where('user_id', $user->id)
            ->where('favoriteable_id', $request->id)
            ->where('favoriteable_type', $favoriteableType)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'العنصر موجود مسبقًا في المفضلة',
                'is_favorited' => true,
            ], 409);
        }

        // الإضافة
        Favorite::create([
            'user_id' => $user->id,
            'favoriteable_id' => $request->id,
            'favoriteable_type' => $favoriteableType,
        ]);

        return response()->json(['message' => 'تمت إضافة العنصر إلى المفضلة بنجاح']);
    }

    public function removeFromFavorites(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|in:property,office',
        ]);

        // تحديد الكلاس المناسب
        $favoriteableType = match ($request->type) {
            'property' => \App\Models\Property::class,
            'office' => \App\Models\Office::class,
        };

        // البحث عن السجل
        $favorite = Favorite::where('user_id', $user->id)
            ->where('favoriteable_id', $request->id)
            ->where('favoriteable_type', $favoriteableType)
            ->first();

        if (!$favorite) {
            return response()->json(['message' => 'العنصر غير موجود في المفضلة'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'تم حذف العنصر من المفضلة بنجاح']);
    }

    public function getFavorites()
    {
        $user = Auth::user();

        $favorites = Favorite::with([
            'favoriteable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\Property::class => ['images', 'video'],
                    \App\Models\Office::class => [], // إذا عندك علاقات إضافية للمكتب، ضيفها هون
                ]);
            }
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'message' => 'قائمة العناصر المفضلة',
            'data' => $favorites
        ]);
    }


    public function isFavorited(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
        ]);

        $propertyId = $request->query('property_id');

        $exists = Favorite::where('user_id', $user->id)
            ->where('favoriteable_id', $propertyId)
            ->where('favoriteable_type', Property::class)
            ->exists();

        return response()->json(['is_favorited' => $exists], 200);
    }
}
