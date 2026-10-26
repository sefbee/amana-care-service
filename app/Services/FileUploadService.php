<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /* public function getFileUrl(?string $path): string
    {
        if (! $path) {
            // $path = 'public/customer_types/icons/LtT6IEtopawitmD2EXa8qyuUZVBngKy56t44IsVl.png'; // Default Image
            $path = 'no_image.png'; // Default Image
        }
        $cdn = env('CLOUDFRONT_CDN');

        return $cdn ? "{$cdn}/$path" : Storage::url($path);
    } */

    public static function getFileUrl(?string $path = null): string
    {
        if (! $path) {
            return asset('theme/media/no-pictures.png');
        }
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        return $path ? Storage::url($path) : asset('theme/media/no-pictures.png');
    }

    public function upload($fileName, $path, $uploadFileName = null)
    {
        try {
            if ($uploadFileName) {
                return request()
                    ->file($fileName)
                    ->storeAs($path, $uploadFileName, 'public');
            }

            return request()
                ->file($fileName)
                ->store($path, 'public');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function upload_multiple($fileName, $path)
    {
        ini_set('upload_max_filesize', '100M');
        ini_set('post_max_size', '120M');
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        ini_set('memory_limit', '256M');

        $files = request()->file($fileName);

        if (! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $uploadedFiles[] = $file->store($path, 'public');
            }
        }

        return $uploadedFiles;
    }

    public function upload_file($file, $path)
    {
        try {
            return $file->store($path, 'public');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function delete(string $path)
    {
        try {
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
