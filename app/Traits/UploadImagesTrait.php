<?php
namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait UploadImagesTrait
{
    public function uploadImage($file, $folderName)
    {
        if ($file) {
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs($folderName, $fileName, 'pictures');
            return Storage::disk('pictures')->url($path);
        }

        return null;
    }



    public function uploadVideo($file, $folderName)
    {
        if ($file) {
            $fileName = time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folderName, $fileName, 'pictures');  // تخزين الفيديو في الـ public
            return Storage::disk('pictures')->url($path);  // إرجاع رابط الفيديو
        }

        return null;
    }
}
