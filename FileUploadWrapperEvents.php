<?php

/**
 * @copyright Copyright &copy; Fabrizio Caldarelli, sfmobile.it, 2018
 * @package sfmobile\fileUploader
 * @version 1.0
 */

namespace sfmobile\fileUpload;

/**
 * FileUploadWrapper
 */
class FileUploadWrapperEvents
{
    public function beforeSave($fileUploadWrapper, $fileUpload, $content, &$userObject) {
    }
    
    public function afterSave($fileUploadWrapper, $fileUpload, $content, &$userObject) {
    }
}
