<?php
namespace sfmobile\fileUpload;

use yii\web\UploadedFile;
use yii\validators\FileValidator;
use yii\helpers\ArrayHelper;

/**
* FileUploadCore
* @package sfmobile\ext\fileUploader
* @version 1.0
*/
class FileUploadCore {

    private static $formSessionId;

    public static function getFormSessionId()
    {
        if(self::$formSessionId == null) self::$formSessionId = \Yii::$app->request->post(\sfmobile\fileUpload\Module::getInstance()->formSessionKey);
        if(self::$formSessionId == null) self::$formSessionId = self::uuid_v4();
        return self::$formSessionId;
    }

    public static function uuid_v4() {
          return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
          );
      }


    public static function extractAttributeName($attribute)
    {
        $matches = [];
        if (!preg_match(\yii\helpers\BaseHtml::$attributeRegex, $attribute, $matches)) {
             throw new InvalidArgumentException('Attribute name must contain word characters only.');
         }
         /*
         $prefix = $matches[1];
         $attribute = $matches[2];
         $suffix = $matches[3];
         */
        return $matches[2];
    }

    /**
    --------------------------------------------
    --- Helper for ActiveForm sessionFormKey ---
    --------------------------------------------
    */
    public static function printHtmlFormSessionId()
    {
        echo \yii\helpers\Html::hiddenInput(\sfmobile\fileUpload\Module::getInstance()->formSessionKey, self::getFormSessionId());
    }

    /**
    ------------------------------------------
    --- Helper for FileInSessionController ---
    ------------------------------------------
    */
    public static function getFileFromSessionNames($modelName, $attribute, $filename, $formSessionId)
    {
        $keySession = sprintf('%s-%s', \sfmobile\fileUpload\Module::getInstance()->formSessionKey, $formSessionId);

        $s = \Yii::$app->session->get($keySession, []);

        $key = sprintf('%s_%s', $modelName, $attribute);
        $key = str_replace([ '[', ']' ], [ '_', '_' ], $key);

        $arrFiles = isset($s[$key])?$s[$key]:[];

        $file = null;
        foreach($arrFiles as $f)
        {
            if($f->name == $filename) $file = $f;
        }

        return $file;
    }
    public static function deleteFileFromSessionNames($modelName, $attribute, $filename, $formSessionId)
    {
        $keySession = sprintf('%s-%s', \sfmobile\fileUpload\Module::getInstance()->formSessionKey, $formSessionId);

        $s = \Yii::$app->session->get($keySession, []);

        $key = sprintf('%s_%s', $modelName, $attribute);
        $key = str_replace([ '[', ']' ], [ '_', '_' ], $key);

        $arrFiles = isset($s[$key])?$s[$key]:[];

        $indexFound = -1;
        $file = null;
        foreach($arrFiles as $index => $f)
        {
            if($f->name == $filename)
            {
                $file = $f;
                $indexFound = $index;
            }
        }
        if($indexFound != -1) unset($arrFiles[$indexFound]);

        $s[$key] = $arrFiles;

        \Yii::$app->session->set($keySession, $s);

        return $file;
    }

    /**
    -----------------------
    --- DESTROY SESSION ---
    -----------------------
    */
    public static function destroySession()
    {
        $keySession = sprintf('%s-%s', \sfmobile\fileUpload\Module::getInstance()->formSessionKey, $formSessionId);
        \Yii::$app->session->remove($keySession);
    }

    /**
    ---------------------------------
    --- Load from form or session ---
    ---------------------------------
    */
    public static function getFromSession($model, $attribute)
    {
        $keySession = sprintf('%s-%s', \sfmobile\fileUpload\Module::getInstance()->formSessionKey, self::getFormSessionId());
        $s = \Yii::$app->session->get($keySession, []);

        $key = sprintf('%s_%s', \yii\helpers\StringHelper::basename(get_class($model)), $attribute);
        $key = str_replace([ '[', ']' ], [ '_', '_' ], $key);


        return isset($s[$key])?$s[$key]:[];
    }
    public static function setInSession($model, $attribute)
    {
        $keySession = sprintf('%s-%s', \sfmobile\fileUpload\Module::getInstance()->formSessionKey, self::getFormSessionId());
        $s = \Yii::$app->session->get($keySession, []);

        $key = sprintf('%s_%s', \yii\helpers\StringHelper::basename(get_class($model)), $attribute);
        $key = str_replace([ '[', ']' ], [ '_', '_' ], $key);

        $attributeName = self::extractAttributeName($attribute);

        $s = array_merge($s, [
            $key => $model->$attributeName
        ]);

        \Yii::$app->session->set($keySession, $s);
    }


    /**
    ------------
    --- Sync ---
    ------------
    */
    public static function sync($model, $attribute, $userId, $referId, $referTable, $section, $category)
    {
        $arrFiles = [];
        if(is_array($model->$attribute))
        {
            $arrFiles = $model->$attribute;
        }
        else if($model->$attribute !== null)
        {
            $arrFiles = [ $model->$attribute ];
        }

        // f instance of FileUpload
        foreach($arrFiles as $f)
        {
            $f->save($userId, $referId, $referTable, $section, $category, []);
        }

        \sfmobile\fileUpload\FileUploadWrapper::deleteFilesNotInArray($referId, $referTable, $section, $category, $arrFiles);
    }

    /**
    --------------
    --- Delete ---
    --------------
    */
    public static function deleteAll($referId, $referTable, $section, $category)
    {
        $items = \sfmobile\fileUpload\models\FileUpload::find()
        ->andWhere(['refer_id' => $referId, 'refer_table' => $referTable, 'section' => $section, 'category' => $category])
        ->all();

        foreach($items as $item)
        {
            $item->delete();
        }
    }

    /**
    ------------
    --- Load ---
    ------------
    * @param $formTabularIndex
    * @param $model
    * @param $attributeNameInput
    * @param $referId
    * @param $referTable
    * @param $section
    * @param $category
    * @param $fileInputIndexName index name of $_FILES where get file data
    * @description to be implemented in load() model override
    */
    public static function load($formTabularIndex, $model, $attributeNameInput, $referId, $referTable, $section, $category, $fileInputIndexName = null)
    {
        // File upload
        $items = [];
        if($model->isNewRecord == false)
        {
            $items = \sfmobile\fileUpload\models\FileUpload::find()
            ->andWhere(['refer_id' => $referId, 'refer_table' => $referTable, 'section' => $section, 'category' => $category])
            ->all();
        }
        $attributeName = ($formTabularIndex !== null)?sprintf('[%d]%s', $formTabularIndex, $attributeNameInput):$attributeNameInput;
        \sfmobile\fileUpload\FileUploadCore::loadFromFormOrSession($model, $attributeName, $items, $fileInputIndexName);
    }

    /**
    ---------------------------------
    --- Load from form or session ---
    ---------------------------------
    */
    public static function removeFilesWithSameName($arrInput)
    {
        $c1 = 0;
        while($c1<count($arrInput))
        {
            $c2 = $c1+1;
            $found = -1;
            while(($c2<count($arrInput))&&($found == -1))
            {
                if($arrInput[$c1]->name == $arrInput[$c2]->name) $found = $c2;
                $c2++;
            }
            $c1++;

            if($found != -1)
            {
                unset($arrInput[$found]);
                $arrInput = array_values($arrInput);
                $c1 = 0;
            }
        }
        return $arrInput;
    }

    /**
    * @param $model
    * @param $attribute
    * @param $dbModels
    * @param $fileInputIndexName index name of $_FILES where get file data
    */
    public static function loadFromFormOrSession($model, $attribute, $dbModels, $fileInputIndexName = null)
    {
        if((\Yii::$app->request->isPost)&&(\Yii::$app->request->post(\sfmobile\fileUpload\Module::getInstance()->formSessionKey) != self::getFormSessionId()))
        {
            throw new FileUploadException("FormSessionId not set. Have you put \"FileUploadCore::printHtmlFormSessionId();\" inside the ActiveForm?", 1);
        }

        $attributeName = self::extractAttributeName($attribute);

        $attributeIsRequiredAs = 'array';   // object, array

        foreach($model->getActiveValidators($attributeName) as $validator)
        {
            if($validator instanceof FileValidator)
            {
                // is required as array
                if ($validator->maxFiles != 1 || $validator->minFiles > 1) {
                    $attributeIsRequiredAs = 'array';
                }
                else
                {
                    // is required as object
                    $attributeIsRequiredAs = 'object';
                }

            }
        }

        if(\Yii::$app->request->isPost)
        {
            // Load from session
            $sessionFiles = ($fileInputIndexName == null)?self::getFromSession($model, $attribute):[];
            $formFiles = [];

            // Get file from form
            if(count($_FILES)>0)
            {
                if($fileInputIndexName == null)
                {
                    $formFiles = FileUploadWrapper::fromUploadedFiles(UploadedFile::getInstances($model, $attribute));
                }
                else
                {
                    $formFiles = FileUploadWrapper::fromUploadedFiles([ UploadedFile::getInstanceByName($fileInputIndexName) ]);
                }
            }


            // Set attribute files
            $model->$attributeName = array_values(array_merge($sessionFiles, $formFiles));
        }
        else if(\Yii::$app->request->isGet)
        {
            // Set attribute files
            $model->$attributeName = array_values(FileUploadWrapper::fromDatabaseFiles($dbModels));
        }

        // Remove files with same name
        $model->$attributeName = self::removeFilesWithSameName($model->$attributeName);

        // Save in session
        self::setInSession($model, $attribute);

        if($attributeIsRequiredAs == 'object')
        {
            if(count($model->$attributeName)>1)
            {
                $keys = array_keys($model->$attributeName);
                $model->$attributeName = $model->$attributeName[$keys[0]];
                //throw new FileUploadException("FileUploadCore.loadFromFormOrSession : There are more than 1 file. Specify minFiles in 'file' validator > 1");
            }
            else if(count($model->$attributeName) == 1)
            {
                $keys = array_keys($model->$attributeName);
                $model->$attributeName = $model->$attributeName[$keys[0]];
            }
            else if(count($model->$attributeName) == 0)
            {
                $model->$attributeName = null;
            }
        }

    }

}
?>
