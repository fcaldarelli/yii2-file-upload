<?php
namespace sfmobile\fileUpload\components\kartikFileInput;

use yii\widgets\InputWidget;
use yii\validators\FileValidator;

/**
* Kartik File Input Widget Wrapper
* @package sfmobile\fileUpload\components\kartikFileInput
* @version 1.0.0
*/
class KartikFileInput extends InputWidget {

    /**
    * @var accepted file types for upload
    * @since 1.0.0
    */
    public $acceptedTypes = '*/*';

    /**
    * @var max files to uploader. Default (null) is infinite
    * @since 1.0.0
    */
    public $maxFileCount = null;

    /**
    * @var auto detect file preview type
    * @since 1.0.0
    */
    public $detectPreviewType = true;

    /**
    * @var string define validateInitialCount option
    * @since 1.0.0
    */
    public $validateInitialCount = true;

    /**
    * @var string define minFileCount option
    * @since 1.0.0
    */
    public $minFileCount = 0;

    /**
    * @var string get rules from validator linked to model
    * @since 1.0.0
    */
    public $applyModelRules = true;

    public function init(){
        parent::init();

        if($this->applyModelRules)
        {
            $this->execApplyModelRules();
        }
    }

    public function run(){

        return $this->render('kartikFileInput', [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'acceptedTypes' => $this->acceptedTypes,
            'maxFileCount' => $this->maxFileCount,
            'detectPreviewType' => $this->detectPreviewType,
            'validateInitialCount' => $this->validateInitialCount,
            'minFileCount' => $this->minFileCount,
        ]);
    }

    private function execApplyModelRules()
    {
        foreach($this->model->getActiveValidators($this->attribute) as $validator)
        {
            if($validator instanceof FileValidator)
            {
                if($validator->minFiles>=1) $this->minFileCount = $validator->minFiles;
                if($validator->maxFiles>0) $this->maxFileCount = $validator->maxFiles;
                if($validator->mimeTypes !== null) $this->acceptedTypes = $validator->mimeTypes;
            }
        }
    }
}
?>
