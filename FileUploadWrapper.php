<?php

/**
 * @copyright Copyright &copy; Fabrizio Caldarelli, sfmobile.it, 2018
 * @package sfmobile\fileUploader
 * @version 1.0
 */

namespace sfmobile\fileUpload;

use yii\web\UploadedFile;
use \sfmobile\fileUpload\models\FileUpload;
use yii\helpers\ArrayHelper;

/**
 * FileUploadWrapper
 */
class FileUploadWrapper extends UploadedFile
{
    public $isFromDatabase;
    public $dbModel;

    // Contains file data
    private $contentFile;

    public function getContentFile() 
    {
        return file_get_contents($this->tempName);
    }

    public static function fromDatabaseFiles($databaseFiles)
    {
        $outData = [];

        foreach($databaseFiles as $df)
        {
            if(file_exists($df->absolutePath))
            {
                $m = new self();
                $m->isFromDatabase = true;
                $m->dbModel = $df;
                // $m->contentFile = file_get_contents($df->absolutePath);

                $m->name = $df->file_name_original;
                $m->tempName = $df->absolutePath;
                $m->type = $df->mime_type;
                $m->size = $df->file_size;
                $m->error = 0;

                $outData[] = $m;
            }
        }

        return $outData;
    }

    public static function fromUploadedFiles($uploadedFiles)
    {
        $outData = [];

        foreach($uploadedFiles as $up)
        {
            if($up != null)
            {
                $m = new self();
                $m->isFromDatabase = false;
                $m->dbModel = null;
                // $m->contentFile = file_get_contents($up->tempName);

                $m->name = $up->name;
                $m->tempName = $up->tempName;
                $m->type = $up->type;
                $m->size = $up->size;
                $m->error = $up->error;

                $outData[] = $m;
            }
        }

        return $outData;

    }


    /**
    -------------------------------
    --- File in session actions ---
    -------------------------------
    * @description to be implemented in load() model override
    */
    public function fileInSessionGetAction($model, $attribute)
    {
        $modelName = \yii\helpers\StringHelper::basename(get_class($model));

        $moduleId = (\sfmobile\fileUpload\Module::getInstance()->id);

        return \yii\helpers\Url::to([
            $moduleId.'/file-in-session/get',
            'model' => $modelName,
            'attr' => $attribute,
            'name' => $this->name,
            'sid' => \sfmobile\fileUpload\FileUploadCore::getFormSessionId()
        ], true);
    }
    public function fileInSessionDeleteAction($model, $attribute)
    {
        $modelName = \yii\helpers\StringHelper::basename(get_class($model));

        $moduleId = (\sfmobile\fileUpload\Module::getInstance()->id);

        return \yii\helpers\Url::to([
            $moduleId.'/file-in-session/delete',
            'model' => $modelName,
            'attr' => $attribute,
            'name' => $this->name,
            'sid' => \sfmobile\fileUpload\FileUploadCore::getFormSessionId()
        ], true);
    }

    /**
    --------------------------
    --- DELETE OTHER FILES ---
    --------------------------
    */
    public static function deleteFilesNotInArray($referId, $referTable, $section, $category, $arrFiles)
    {
        $filenameList = [];
        foreach($arrFiles as $f)
        {
            $filenameList[] = $f->name;
        }

        $recordList = FileUpload::find()
        ->andWhere(['section' => $section, 'category' => $category, 'refer_id' => $referId, 'refer_table' => $referTable])
        ->andWhere(['NOT IN', 'file_name_original' , $filenameList ])
        ->all();

        foreach($recordList as $r)
        {
            $r->delete();
        }
    }

    /**
    ---------------------
    --- SAVE THE FILE ---
    ---------------------
    */
    private function saveContentToFile($content, $fileUpload)
    {
        $fileUpload->saveContent($content);
    }

    /**
    * @param $options array of [ 'filter' => [  'andWhere' => [] ], 'saveFields' => [ 'set of field => value' ] ]
    */
    public function save($userId, $referId, $referTable, $section, $category, $options=[])
    {
        $saveContentToFile = false;

        $dbRecordQuery = FileUpload::find()
        ->andWhere(['file_name_original' => $this->name, 'section' => $section, 'category' => $category, 'refer_id' => $referId, 'refer_table' => $referTable]);

        if(ArrayHelper::getValue($options, 'filter.andWhere')!=null) $dbRecordQuery->andWhere(ArrayHelper::getValue($options, 'filter.andWhere'));

        $dbRecord = $dbRecordQuery->one();

        if($dbRecord != null)
        {
            $absolutePathFile = $dbRecord->absolutePath;

            // Check if file exists on database with different file size or file does not exist in filesystem
            if(($dbRecord->file_size != $this->size)||(file_exists($absolutePathFile)== false))
            {
                // Update the content
                $saveContentToFile = true;

                $dbRecord->file_size = $this->size;
                $dbRecord->update_time = date('Y-m-d H:i:s');;
            }
            else
            {
                // skip the file, because file size is the same
                $saveContentToFile = false;
            }
        }
        else
        {
            // If the file does not exist, create it
            $dbRecord = $this->dbModel;
            if($dbRecord == null) $dbRecord = new FileUpload();
            $dbRecord->section = $section;
            $dbRecord->category = $category;
            $dbRecord->user_id = $userId;
            $dbRecord->refer_id = $referId;
            $dbRecord->refer_table = $referTable;
            $dbRecord->file_name = sha1(basename($this->name)).'.'.strtolower(pathinfo( $this->name, PATHINFO_EXTENSION));
            $dbRecord->file_name_original = $this->name;
            $dbRecord->mime_type = $this->type;
            $dbRecord->file_size = $this->size;
            $dbRecord->create_time = date('Y-m-d H:i:s');
            $dbRecord->update_time = null;
            $dbRecord->relative_path = $dbRecord->relativePathFromDbRecord();
            $dbRecord->storage = \sfmobile\fileUpload\Module::getInstance()->defaultStorage;

            if(ArrayHelper::getValue($options, 'saveFields')!=null)
            {
                foreach(ArrayHelper::getValue($options, 'saveFields') as $sfKey => $sfValue)
                {
                    $dbRecord->setAttribute($sfKey, $sfValue);
                }
            }
            $saveContentToFile = true;

            $this->dbModel = $dbRecord;
        }

        if($saveContentToFile)
        {
            $retSave = $dbRecord->save();

            if($retSave == false)
            {
                throw new FileUploadException(implode(',', $dbRecord->getFirstErrors()));
            }

            // Update the content
            $this->saveContentToFile($this->contentFile, $dbRecord);
        }

        return $dbRecord;
    }
}
