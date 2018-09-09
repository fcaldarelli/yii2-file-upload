File upload for Yii2
======================

Single and Multiple file upload handler for Yii2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist fabriziocaldarelli/yii2-file-upload "*"
```

or add

```
"fabriziocaldarelli/yii2-file-upload": "*"
```

to the require section of your `composer.json` file.


Configuration
-----

Once the extension is installed, configure it in config\main.php setting imageBaseUrl, fileUploadBasePath and fileUploadBaseUrl :

**1) Add fileUploader module to config.php**

```php
'modules' => [
    'fileUploader' => [
        'class' => 'sfmobile\fileUpload\Module',

        // Base path and url for files
        'basePath' =>  '/var/www/vhosts/your_hosting/public_files',
        'baseUrl' =>  '/public_files',

        // Database table name to save files metadata
        'dbTableName' => 'tbl_file_upload',
    ],
],
```

**2) Add the module in bootstrap section of config\main.php**

```php
'bootstrap' => ['log', 'fileUploader'],
```

**3) Apply database migration**

```
yii migrate --migrationPath=@vendor/fabriziocaldarelli/yii2-file-upload/migrations
```

Changes to Model, View and Controller
-----

I suggest to create ModelForm class that extends Model class and add an attribute to handle files (in this case, 'photo')

**Changes to Model**
```php
<?php
namespace backend\models;

class Articles extends \yii\db\ActiveRecord
{
    public $formTabularIndex = null; // Needed for file upload with tabular form
    public $photo;  // attribute to store uploaded file

    // Load: initialize the files attached to model
    public function load($data, $formName = null)
    {
        $retLoad = parent::load($data, $formName);

        // Files metadata will be stored with refer_table=articles, section=articles, category=photo and refer_id=model.id
        \sfmobile\fileUpload\FileUploadCore::load($this->formTabularIndex, $this, 'photo', $this->id, 'articles', 'articles', 'photo');

        return $retLoad;

    }

    // Delete: added files deletion
    public function afterDelete()
    {
        parent::afterDelete();

        // When delete the model, files automatically will be deleted
        \sfmobile\fileUpload\FileUploadCore::deleteAll($this->id, 'articles', 'articles', 'photo');

    }

    // Save: after saved the model, also save the files
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // File upload
        \sfmobile\fileUpload\FileUploadCore::sync($this, 'photo', \Yii::$app->user->identity->id, $this->id, 'articles', 'articles', 'photo');

    }
```

It can be used rules validation base on file, such as:

```php
 <?php
public function rules()
{
    return [
        // ...

        // minimum 2 files and maximum 5 files
        ['photo', 'file', 'minFiles' => 2, 'maxFiles' => 5 ],
    ];
}
```

**Changes to View**

Add an formSessionId hidden input field to manage (eventually!) multiple browser tabs opened in same web page (with form upload).
Then can insert your own file input widget or use that provided by the extension, derived from KartikFileInput
```php
<div class="articles-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?php \sfmobile\fileUpload\FileUploadCore::printHtmlFormSessionId(); ?>

    <?= $form->field($model, 'photo')->widget(\sfmobile\fileUpload\components\kartikFileInput\KartikFileInput::className(), [
    ]); ?>
```

This widget is automatically configure to get validation rules from Model.

**Changes to Controller**

Create and Update actions content is the same of standard.
```php
<?php
    public function actionCreate()
    {
        $model = new Articles();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }


        return $this->render('update', [
            'model' => $model,
        ]);
    }    
```

Tabular form
--------------

In case of tabular form (multiple instances of same Model inside the same form), only the action inside the controller has to be modified. Here an example:

**Changes to Controller**
```php
<?php
public function actionUpdateMultiple($ids)
{
    $models = \common\models\Articles::find()->andWhere(['id' => explode(',', $ids)])->all();

    foreach($models as $indexModel => $model)
    {
        $model->formTabularIndex = $indexModel;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
    }

    return $this->render('update-multiple', [
        'models' => $models,
    ]);
}
```

Models from multiple files
--------------

When you need to store multiple models from multiple files, this is the right approach:

**Changes to Model**
Inside the model, in load() you will pass an extra parameter to identify  which $_FILES index to use
```php
<?php
class BannerHomeIndexForm extends \common\models\BannerHome
{
    public $fileInputIndexName = null; // Extra parameter to specify which $_FILES index to use
    public $formTabularIndex = null; // Needed for file upload with tabular form
    public $image;

    // Load: initialize the files attached to model
    public function load($data, $formName = null)
    {
        $retLoad = parent::load($data, $formName);

        // !!! ATTENTION TO LAST PARAMETER !!!!
        \sfmobile\fileUpload\FileUploadCore::load($this->formTabularIndex, $this, 'image', $this->id, 'banner_home', 'banner_home', 'image', $this->fileInputIndexName);

        return $retLoad;

    }

    // Delete: added files deletion
    public function afterDelete()
    {
        // ... as default example ...
    }

    // Save: after saved the model, also save the files
    public function afterSave($insert, $changedAttributes)
    {
        // ... as default example ...
    }
```

**Changes to Controller**
In Controller, you will add fileInputIndexName to pass index of $_FILES where get file data
```php
<?php
public function actionIndex()
{
    if(\Yii::$app->request->isPost)
    {
        foreach($_FILES['BannerHomeForm']['name']['image'] as $indexBanner => $n)
        {
            $model = new BannerHomeForm();

            $model->formTabularIndex = $indexBanner;
            $model->fileInputIndexName = 'BannerHomeForm[image]['.$indexBanner.']';
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
            }
        }
    }
    \sfmobile\fileUpload\FileUploadCore::destroySession();
```
