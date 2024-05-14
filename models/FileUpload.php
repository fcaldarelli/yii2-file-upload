<?php

namespace sfmobile\fileUpload\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tbl_file_upload".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $section
 * @property string $category
 * @property integer $refer_id
 * @property string $file_name
 * @property string $file_name_original
 * @property string $description
 * @property string $mime_type
 * @property integer $file_size
 * @property string $relative_path
 * @property string $refer_table
 * @property string $update_time
 * @property string $create_time
 * @version 1.0
 */
class FileUpload extends \yii\db\ActiveRecord
{
    public function afterDelete()
    {
        parent::afterDelete();

        $storage = $this->getStorage();

        // Cancella tutti i files relativi a quel filename (le varie versioni a risoluzioni diverse)
        if($storage->fileExists($this))
        {
            $storage->deleteFile($this);
        }
    }

    /**
	 **************************************
	 * Storage
	 **************************************
    */
    private function getStorage()
    {
        $storages = \sfmobile\fileUpload\Module::getInstance()->storages;
        $foundStorage = \Yii::createObject($storages[$this->storage]);
        return $foundStorage;
    }

    /**
	 **************************************
	 * Path and Url
	 **************************************
    */
    public function relativePathFromDbRecord()
    {
        $rel = sprintf('/%s/%s/%d/%s', $this->section, $this->category, $this->refer_id, $this->file_name);

        return $rel;
    }

    public function getRelativePath()
    {
        return $this->relativePathFromDbRecord();
    }

    public function getAbsolutePath()
    {
        return $this->getStorage()->getAbsolutePath($this->relativePath);
    }

    /**
    * Return full file url, absolute or relative, based on isRequiredAbsolute parameter
    * @param $isAbsoluteUrl boolean Specify if it needed baseUrl
    */
    public function getUrl($isAbsoluteUrl=false, $options=null)
    {
        return $this->getStorage()->getUrl($this, $isAbsoluteUrl, $options);
    }

    public function saveContent($content)
    {
        return $this->getStorage()->saveContent($content, $this);
    }

    public function readContent()
    {
        return $this->getStorage()->readContent($this);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return \sfmobile\fileUpload\Module::getInstance()->dbTableName;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['section', 'category', 'refer_id', 'file_name', 'file_name_original',  'mime_type', 'file_size', 'relative_path', 'refer_table'], 'required'],
            [['user_id', 'refer_id', 'file_size'], 'integer'],
            [['create_time'], 'safe'],
            [['section', 'category', 'file_name', 'file_name_original', 'refer_table'], 'string', 'max' => 150],
            [['description'], 'string', 'max' => 500],
            [['mime_type'], 'string', 'max' => 100],
            [['relative_path'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'section' => Yii::t('app', 'Section'),
            'category' => Yii::t('app', 'Category'),
            'refer_id' => Yii::t('app', 'Refer ID'),
            'file_name' => Yii::t('app', 'File Name'),
            'file_name_original' => Yii::t('app', 'File Name Original'),
            'description' => Yii::t('app', 'Description'),
            'mime_type' => Yii::t('app', 'Mime Type'),
            'file_size' => Yii::t('app', 'File Size'),
            'relative_path' => Yii::t('app', 'Relative Path'),
            'refer_table' => Yii::t('app', 'Refer Table'),
            'create_time' => Yii::t('app', 'Create Time'),
        ];
    }

}
