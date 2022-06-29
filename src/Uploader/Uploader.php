<?php

namespace MediciVN\Core\Uploader;

class Uploader
{
    public function __construct(protected $source, protected $disk, protected array $size = [])
    {

    }

    public function put(string $path, string $file, string $place = 'public'): string
    {
        $this->disk->put($path, $file, $place);

        return $this->disk->url($path);
    }

    public function prefix(): string
    {
        return ltrim(implode('_', [
            auth()->id(),
            pathinfo($this->source->getClientOriginalName(), PATHINFO_FILENAME),
            uniqid(now()->timestamp),
        ]), '/');
    }

    public function resize(&$image, $targetWidth, $targetHeight): bool
    {
        $targetImageRatio = $image->width() / $image->height();
        $imageRatio = $image->width() / $image->height();
        $resizeWidth = null;
        $resizeHeight = null;

        if ($targetWidth > $image->width() || $targetHeight > $image->height()) {
            return false;
        }

        if ($targetImageRatio > $imageRatio) {
            $resizeWidth = $targetWidth;
        } else {
            $resizeHeight = $targetHeight;
        }

        $image->resize($resizeWidth, $resizeHeight, function ($constraint) {
            $constraint->aspectRatio();
        });

        return true;
    }

    public function getExtention($image): string
    {
        return match ($image->mime()) {
            "image/png" => "png",
            "image/gif" => "gif",
            "image/tif" => "tif",
            "image/bmp" => "bmp",
            "image/jpeg" => "jpg",
            default => "jpg"
        };
    }

    public function upload()
    {

    }
}