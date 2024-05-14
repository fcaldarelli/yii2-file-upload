<?php

namespace sfmobile\fileUpload\storages;

use Yii;

interface Storage
{
    public function readContent($fileUpload);
    public function saveContent($content, $fileUpload);
    public function getAbsolutePath($relativePath);
    public function getUrl($fileUpload, $isAbsoluteUrl=false, $options=null);
    public function fileExists($fileUpload);
    public function deleteFile($fileUpload);
}
