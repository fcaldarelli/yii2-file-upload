<?php

$initialPreview = [];
$initialPreviewConfig = [];
$filesInSession = \sfmobile\fileUpload\FileUploadCore::getFromSession($model, $attribute);

foreach($filesInSession as $fis)
{
    $mimeType = $fis->type;

    $initialPreview[] = $fis->fileInSessionGetAction($model, $attribute);

    $previewType = 'image';

    // If enabled detect preview type, use it
    if($detectPreviewType)
    {
        if(strpos($mimeType, 'image/') === 0) $previewType = 'image';
        if(strpos($mimeType, 'video/') === 0) $previewType = 'video';
        if(strpos($mimeType, 'audio/') === 0) $previewType = 'audio';
        if(strpos($mimeType, 'text/html') === 0) $previewType = 'html';
        if(strpos($mimeType, 'application/pdf') === 0) $previewType = 'pdf';
    }

    $initialPreviewConfig[] = [
        'type' => $previewType,
        'caption' => $fis->name,
        'size' => $fis->size,
        'filetype' => $mimeType,
        'url' => $fis->fileInSessionDeleteAction($model, $attribute)
   ];
}
?>
<?php
echo \kartik\file\FileInput::widget([
    'model' => $model,
    'attribute' => $attribute.'[]',
    'options' => [
        'accept' => $acceptedTypes,
        'multiple' => true,
    ],
    'pluginOptions' => [
        'maxFileCount' => $maxFileCount,
        'minFileCount' => $minFileCount,

        'previewFileType' => 'any',
        'showPreview' => true,
        'showCaption' => true,
        'showRemove' => true,
        'showUpload' => false,
        'overwriteInitial' => false,
        'validateInitialCount' => true,

        'initialPreview'=> $initialPreview,
        'initialPreviewAsData'=>true,
        'initialPreviewConfig' => $initialPreviewConfig,

    ],
]);
?>
