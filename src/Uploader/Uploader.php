<?php

namespace MediciVN\Core\Uploader;

use Exception;
use Intervention\Image\ImageManagerStatic as Image; 
use Intervention\Image\Exception\NotReadableException;
use Illuminate\Http\UploadedFile;
class Uploader
{
    private $image;
    private string $extension;
    private string $prefix;
    private string $fileName = '';
    private array $result;
    
    public function __construct(
        private $source, 
        private $disk, 
        private string $path, 
        private array $sizes = []
    ) {
        $this->isUploadedFile()
            ->make()
            ->setExtention()
            ->setPrefix()
            ->setPath();
    }

    protected function make(): self
    {
        try {
            $this->image = Image::make($this->source);
            return $this;
        } catch (NotReadableException) {
            throw new Exception("Could not read the source");
        }
    }

    protected function setPath(): self
    {
        $this->path = rtrim($this->path, '/');

        return $this;
    }

    protected function put(string $path, string $file, string $place = 'public'): string
    {
        $this->disk->put($path, $file, $place);

        return $this->disk->url($path);
    }

    protected function setPrefix(): self
    {
        $this->prefix = $this->fileName == '' ? ltrim(implode('_', [
            auth()->id(),
            pathinfo($this->source->getClientOriginalName(), PATHINFO_FILENAME),
            uniqid(now()->timestamp),
        ]), '/') : $this->fileName;

        return $this;
    }

    protected function resize($targetWidth, $targetHeight): bool
    {
        $resizeWidth = null;
        $resizeHeight = null;

        $targetImageRatio = $targetWidth / $targetHeight;
        $imageRatio = $this->image->width() / $this->image->height();

        if ($targetImageRatio > $imageRatio) {
            if ($targetWidth > $this->image->width()) {
                return false;
            }

            $resizeWidth = $targetWidth;

            return $this->_resize($resizeWidth, $resizeHeight);
        }

        if ($targetHeight > $this->image->height()) {
            return false;
        }

        $resizeHeight = $targetHeight;

        return $this->_resize($resizeWidth, $resizeHeight);
    }

    protected function _resize($with, $height): bool
    {
        return !! $this->image->resize($with, $height, function ($constraint) {
            $constraint->aspectRatio();
        });
    }

    protected function setExtention(): self
    {
        $this->extension = match ($this->image->mime()) {
            "image/png" => "png",
            "image/gif" => "gif",
            "image/tif" => "tif",
            "image/bmp" => "bmp",
            "image/jpeg" => "jpg",
            default => "jpg"
        };

        return $this;
    }

    protected function makeSize(): self
    {
        foreach ($this->sizes as $key => $size) {
            $hasResized = $this->resize($size['width'], $size['height']);

            if (!$hasResized) {
                $this->result[$key] = null;
                continue;
            }

            $filepath = "{$this->path}/{$this->prefix}_{$size['suffix']}_{$size['width']}x{$size['height']}.jpg";

            $this->result[$key] = $this->put(
                $filepath,
                (string) $this->image->encode('jpg', 'jpg' !== $this->extension ? 95 : null),
                'public'
            );
        }

        return $this;
    }

    protected function isUploadedFile(): self
    {
        if ($this->source instanceof UploadedFile) {
            return $this;
        } else {
            throw new Exception("Invalid uploaded file");
        }
    }

    protected function raw(): string
    {
        $path = "{$this->path}/{$this->prefix}.{$this->extension}";
        $url = $this->put($path, file_get_contents($this->source), 'public');
        $this->image->orientate();
        return $url;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function upload(): self
    {
        if (! $this->image->mime()) {
            $this->image->setFileInfoFromPath($this->source);
        }

        $this->result['raw'] = $this->raw();

        if (empty($this->sizes)) {
            return $this;
        }

        $this->makeSize();

        return $this;
    }

    public function getResult(): array
    {
        return $this->result;
    }
}