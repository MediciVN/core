<?php

use Carbon\Carbon;
use MediciVN\Core\Logger\Logger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\Exception\NotReadableException;

if (!function_exists('upload_images')) {
    /**
     * The function upload image and response all image sizes.
     *
     * @param $source
     * @param $targetPath
     * @param array $sizes
     *
     * @return bool|array
     */
    function upload_images($source, $targetPath, array $sizes = [], $filename = ''): bool|array
    {
        if (!($source instanceof UploadedFile) && !filter_var($source, FILTER_VALIDATE_URL)) {
            return false;
        }

        $targetPath = rtrim($targetPath, '/');

        if ($filename != '') {
            $filenamePrefix = $filename;
        } else {
            $filenamePrefix = implode('_', [
                auth()->id(),
                pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME),
                uniqid(Carbon::now()->timestamp),
            ]);
        }
        $filenamePrefix = ltrim($filenamePrefix, '/');
        $extension = '';
        $disk = Storage::disk(env('FILESYSTEM_CLOUD', 's3'));
        try {
            $image = Image::make($source);
        } catch (NotReadableException $ex) {
            throw $ex;
        }
        $result = [];

        if (!$image->mime()) {
            $image->setFileInfoFromPath($source);
        }

        $extension = get_image_extension($image);

        // Store raw image
        $filepath = "{$targetPath}/{$filenamePrefix}.{$extension}";
        $disk->put($filepath, file_get_contents($source), 'public');
        $result['raw'] = $disk->url($filepath);

        $image->orientate();

        if (!empty($sizes)) {
            foreach ($sizes as $key => $size) {
                $hasResized = resize_image($image, $size['width'], $size['height']);

                if (!$hasResized) {
                    $result[$key] = null;

                    continue;
                }

                $filepath = "{$targetPath}/{$filenamePrefix}_{$size['suffix']}_{$size['width']}x{$size['height']}.{$extension}";
                $disk->put(
                    $filepath,
                    (string)$image->encode($extension, 'jpg' !== $extension ? 95 : null),
                    'public'
                );
                $result[$key] = $disk->url($filepath);
            }
        }

        return $result;
    }
}

if (!function_exists('resize_image')) {
    /**
     * Resize images.
     *
     * @param Image $image
     * @param int $targetWidth
     * @param int $targetHeight
     *
     * @return bool
     */
    function resize_image(&$image, $targetWidth, $targetHeight): bool
    {
        $targetImageRatio = $image->width() / $image->height();
        $imageRatio = $image->width() / $image->height();
        $resizeWidth = null;
        $resizeHeight = null;

        if ($targetImageRatio > $imageRatio) {
            if ($targetWidth > $image->width()) {
                return false;
            }

            $resizeWidth = $targetWidth;
        } else {
            if ($targetHeight > $image->height()) {
                return false;
            }

            $resizeHeight = $targetHeight;
        }

        $image->resize($resizeWidth, $resizeHeight, function ($constraint) {
            $constraint->aspectRatio();
        });

        return true;
    }
}

if (!function_exists('upload_private_images')) {

    /**
     * @param $source
     * @param $targetPath
     * @param array $sizes
     * @return bool|array
     */
    function upload_private_images($source, $targetPath, array $sizes = []): bool|array
    {
        if (!($source instanceof UploadedFile) && !filter_var($source, FILTER_VALIDATE_URL)) {
            return false;
        }

        $targetPath = rtrim($targetPath, '/');
        $filenamePrefix = ltrim(implode('_', [
            auth()->id(),
            pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME),
            uniqid(Carbon::now()->timestamp),
        ]), '/');
        $extension = '';
        $disk = Storage::disk(env('FILESYSTEM_CLOUD_PRIVATE', 's3_private'));

        try {
            $image = Image::make($source);
        } catch (NotReadableException $ex) {
            throw $ex;
        }
        $result = [];

        if (!$image->mime()) {
            $image->setFileInfoFromPath($source);
        }

        $extension = get_image_extension($image);

        // Store raw image
        $filepath = "{$targetPath}/{$filenamePrefix}.{$extension}";
        $disk->put($filepath, file_get_contents($source), 'public');
        $disk->url($filepath);
        $result['raw'] = $filepath;
        $image->orientate();

        if (!empty($sizes)) {
            foreach ($sizes as $key => $size) {
                $hasResized = resize_image($image, $size['width'], $size['height']);

                if (!$hasResized) {
                    $result[$key] = null;

                    continue;
                }

                $filepath = "{$targetPath}/{$filenamePrefix}_{$size['suffix']}_{$size['width']}x{$size['height']}.jpg";
                $disk->put(
                    $filepath,
                    (string)$image->encode('jpg', 'jpg' !== $extension ? 95 : null),
                    'public'
                );
                $disk->url($filepath);
                $result[$key] = $filepath;
            }
        }

        return $result;
    }
}

if (! function_exists('get_image_extension')) {
    function get_image_extension($image): string
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
}

if (!function_exists('get_url_private')) {
    function get_url_private($urlImage): string
    {
        $disk = Storage::disk(env('FILESYSTEM_CLOUD_PRIVATE', 's3_private'));
        $client = Storage::disk(env('FILESYSTEM_CLOUD_PRIVATE', 's3_private'))->getClient();
        $expiry = "+3 minutes";
        $command = $client->getCommand('GetObject', [
            'Bucket' => env('AWS_BUCKET_PRIVATE'), // bucket name
            'Key' => $urlImage
        ]);
        $request = $client->createPresignedRequest($command, $expiry);
        $url = (string)$request->getUri();

        $url = str_replace(
            "https://s3." . env('AWS_DEFAULT_REGION_PRIVATE', 'ap-southeast-1') . ".amazonaws.com/" . env('AWS_BUCKET_PRIVATE', 'medici.dev.private') . "/", 
            env('AWS_URL_PRIVATE', 'https://dev-private.cdn.medici.vn/'), 
            $url
        );

        return $url;
    }
}

if (!function_exists('medici_logger')) {
    /**
     * Create a path log & log info to log file.
     *
     * @param string $action to create path file
     * @param string $description short name what you want to log
     * @param array $data data to log
     * @param array $params to create a path file
     *                            * [] => 'logs/action/Ymd/userLogin/action.log'
     *                            * ['log_user' => false] => 'logs/action/Ymd/action.log'
     *                            * ['log_user' => false, 'user_id' => 123] => 'logs/action/Ymd/123/action.log'
     */
    function medici_logger(string $action, string $description, array $data = [], array $params = [])
    {
        $logger = new Logger();
        $logger->setLogger(new MediciVN\Core\Logger\FileLogger());
        $logger->log($action, $description, $data, $params);
    }
}

if (!function_exists('generate_random_verification_code')) {

    /**
     * The function generate random verification
     *
     * @param int $length
     *
     * @return int
     */
    function generate_random_verification_code(int $length): int
    {
        mt_srand(make_seed());
        do {
            $verificationCode = mt_rand() % 1000000;
        } while ($verificationCode < 100000);
        return $verificationCode;
    }
}

if (!function_exists('make_seed')) {

    /**
     * @return float|int|string
     */
    function make_seed(): int
    {
        list($usec, $sec) = explode(' ', microtime());
        return $sec + $usec * 1000000;
    }
}
