<?php

namespace sfmobile\fileUpload\storages;

use Yii;
use yii\base\Component;

class FileSystemStorage extends Component implements Storage
{
    /**
     * Base path for uploaded file
     * @since 1.0
     */
    public $basePath;

    /**
     * Base url for file uploaded
     * @since 1.0
     */
    public $baseUrl;
    
    
    public function readContent($fileUpload)
    {
        return file_get_contents($fileUpload->absolutePath);
    }
    public function saveContent($content, $fileUpload)
    {
        $pathFile = $fileUpload->absolutePath;
        $basedir = dirname($pathFile);
        if(file_exists($basedir) == false) @mkdir($basedir, 0777, true);
        file_put_contents($pathFile, $content);
    }

    public function getAbsolutePath($relativePath)
    {
        $out = null;

        if($relativePath != null)
        {
            $basePath = $this->basePath;
            $out = $basePath.$relativePath;
        }
        return $out;
    }

    public function getUrl($fileUpload, $isAbsoluteUrl=false, $options=null)
    {
        $out = null;

        $relativePath = $fileUpload->relativePath;

        if($relativePath != null)
        {
            $baseUrl = $this->baseUrl;
            $out = $baseUrl.$relativePath;

            // If it is requested an absolute url, it checks that fileUploadbaseUrl is already in absolute form.
            // If it is already absolute, it does nothing, otherwise apply baseUrl.
            if($isAbsoluteUrl)
            {
                if((strpos(strtolower($baseUrl), 'http://')===0)||(strpos(strtolower($baseUrl), 'https://')===0))
                {
                    // do nothing because fileUploadBaseUrl is already absolute
                }
                else
                {
                    // it apply host base url
                    $out = \yii\helpers\Url::to($out, true);
                }
            }

        }
        return $out;
    }

    public function fileExists($fileUpload)
    {
        return file_exists($fileUpload->absolutePath);
    }

    public function deleteFile($fileUpload)
    {
        @unlink($fileUpload->absolutePath);
    }

}
