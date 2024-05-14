<?php

namespace sfmobile\fileUpload\storages;

use Yii;
use yii\base\Component;
use Aws\S3\S3Client;

class S3Storage extends Component implements Storage
{
    /**
     * S3 Key
     * @since 1.0
     */
    public $s3Key;

    /**
     * S3 Secret
     * @since 1.0
     */
    public $s3Secret;

    /**
     * S3 Endpoint
     * @since 1.0
     */
    public $s3Endpoint;

    /**
     * S3 Bucket
     * @since 1.0
     */
    public $s3Bucket;

    public function getClient()
    {
        // SDK object 
        $client = new S3Client([
            'region' => 'us-east-1',
            'version' => 'latest',
            'endpoint' => $this->s3Endpoint,
            'credentials' => [
                'key' => $this->s3Key,
                'secret' => $this->s3Secret
            ],
            // Set the S3 class to use objects.dreamhost.com/bucket
            // instead of bucket.objects.dreamhost.com
            'use_path_style_endpoint' => true
        ]);

        return $client;
    }

    public function getKey($relativePath)
    {
        return substr($relativePath, 1);
    }
    
    public function readContent($fileUpload)
    {
        return file_get_contents($fileUpload->absolutePath);
    }
    public function saveContent($content, $fileUpload)
    {
        $client = $this->getClient();

        $result = $client->putObject([
            'Bucket' => $this->s3Bucket,
            'Key'    => $this->getKey($fileUpload->relativePath),
            'Body' => $content,
        ]);
    }

    public function getAbsolutePath($relativePath)
    {
        $client = $this->getClient();

        $tmpFile = tempnam(sys_get_temp_dir(), 's3_');

        $client->getObject([
            'Bucket' => $this->s3Bucket,
            'Key'    => $this->getKey($relativePath),
            'SaveAs' => $tmpFile,
        ]);

        return $tmpFile;
    }

    public function getUrl($fileUpload, $isAbsoluteUrl=false, $options=null)
    {
        $client = $this->getClient();

        $cmd = $client->getCommand('GetObject', [
            'Bucket' => $this->s3Bucket,
            'Key'    => $this->getKey($fileUpload->relativePath),
            'ResponseContentDisposition' => 'attachment; filename="' . urlencode($fileUpload->file_name_original) . '"',
        ]);
    
        $request = $client->createPresignedRequest($cmd, '+1 minutes');
    
        $presignedUrl = (string) $request->getUri();

        return $presignedUrl;
    }

    public function fileExists($fileUpload)
    {
        $client = $this->getClient();
        return $client->doesObjectExist($this->s3Bucket, $this->getKey($fileUpload->relativePath));
    }

    public function deleteFile($fileUpload)
    {
        $client = $this->getClient();

        $result = $client->deleteObject([
            'Bucket' => $this->s3Bucket,
            'Key'    => $this->getKey($fileUpload->relativePath),
        ]);

        if ($result['@metadata']['statusCode'] == 204) {
            // echo "File deleted successfully.";
        } else {
            // echo "Failed to delete the file.";
        }
    }

}
