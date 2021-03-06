<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\SearsRecurringPayment */

$this->title = 'Update Sears Recurring Payment: ' . ' ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Sears Recurring Payments', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="sears-recurring-payment-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
