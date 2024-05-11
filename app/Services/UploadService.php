<?php

namespace App\Services;


use http\Env;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HTTP_RESPONSE;
use Aws\S3\S3Client;

class UploadService extends Service
{

    private $key;
    private $file;

    /**
     * @param $file
     * @param $event
     * @return JsonResponse|string
     */
    public function uploadFile($file, $event = null)
    {
        $this->key = "banners/{$event}/banner_{$event}.jpeg";
        $this->file = $file;
        return $this->upload();
    }

    /**
     * @param $file
     * @param $event
     * @return JsonResponse|string
     */
    public function uploadDocuments($file, $path)
    {
        $this->key = "documents/" . $path;
        $this->file = $file;
        return $this->upload();
    }


    /**
     * @param string|null $filePath
     * @param int|null $timeDuration
     * @return string
     */
    public function getFileHash(string $filePath = null, int $timeDuration = null)
    {
        if (empty($filePath)) {
            return '';
        }
        return Storage::disk('s3')->temporaryUrl($filePath, now()->addMinutes($timeDuration ?? 720));
    }


    private function upload()
    {

        try {

            if (!isset($this->file)) {
                throw new Exception("File not uploaded", 1);
            }

            $clientS3 = new \Aws\S3\S3Client([
                'region' => 'us-east-1',
                'version' => '2006-03-01',
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY')
                ]
            ]);

            $response = $clientS3->putObject(array(
                'Bucket' => env('AWS_BUCKET'),
                'Key' => $this->key,
                'SourceFile' => $this->file,
            ));

            if (!$response) {
                return '';
            }

            return $this->key;

        } catch (\Exception $e) {
            return $this->returnRequestError((array)$e);
        }

    }

}
