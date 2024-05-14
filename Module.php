<?php

/**
 * @copyright Copyright &copy; Fabrizio Caldarelli, sfmobile.it, 2018
 * @package sfmobile\fileUpload
 * @version 1.0
 */

namespace sfmobile\fileUpload;

/**
 * Yii2FileUploader module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * Database table name
     * @since 1.0
     */
    public $formSessionKey = 'sfmobile_fileUpload_form_sessionKey';

    /**
     * Database table name
     * @since 1.0
     */
    public $dbTableName = 'tbl_file_upload';

    /**
     * Default storage when create a new file
     * @since 1.0
     */
    public $defaultStorage = 'local';    

    /**
     * List of storages. Implementation of Storage interface
     * The first storage is default
     * @since 1.0
     */
    public $storages = [];

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'sfmobile\fileUpload\controllers';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
