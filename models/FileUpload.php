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

        // Cancella tutti i files relativi a quel filename (le varie versioni a risoluzioni diverse)
        if(file_exists($this->absolutePath))
        {
            @unlink($this->absolutePath);
        }
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
        $out = null;
        $rel = $this->relativePath;

        if($rel != null)
        {
            $basePath = \sfmobile\fileUpload\Module::getInstance()->basePath;
            $out = $basePath.$rel;
        }
        return $out;
    }

    /**
    * Return full file url, absolute or relative, based on isRequiredAbsolute parameter
    * @param $isAbsoluteUrl boolean Specify if it needed baseUrl
    */
    public function getUrl($isAbsoluteUrl=false, $options=null)
    {
        $out = null;
        $rel = $this->relativePathFromDbRecord();

        if($rel != null)
        {
            $baseUrl = \sfmobile\fileUpload\Module::getInstance()->baseUrl;
            $out = $baseUrl.$rel;

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
