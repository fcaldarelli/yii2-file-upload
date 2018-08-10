<?php

namespace sfmobile\fileUpload\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * FileInSessionController
 * @version 1.0.0
 */
class FileInSessionController extends Controller
{
    public function actionGet($model, $attr, $name, $sid)
    {
        $obj = \sfmobile\fileUpload\FileUploadCore::getFileFromSessionNames($model, $attr, $name, $sid);

        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', $obj->type);
        \Yii::$app->response->data = $obj->contentFile;
        \Yii::$app->response->send();
    }

    public function actionDelete($model, $attr, $name, $sid)
    {
        $obj = \sfmobile\fileUpload\FileUploadCore::deleteFileFromSessionNames($model, $attr, $name, $sid);

        $out = ['action' => 'none'];

        if($obj!=null)
        {
            $out = [
                'action' => 'delete'
            ];
        }

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $out;
    }

}
