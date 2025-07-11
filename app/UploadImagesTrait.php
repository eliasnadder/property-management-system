<?php

namespace App;

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
}
