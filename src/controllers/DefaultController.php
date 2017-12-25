<?php

namespace paulzi\cmyii\controllers;

use yii\web\Controller;

/**
 * Page controller
 */
class DefaultController extends Controller
{
    public function actions()
    {
        return ['view' => 'paulzi\cmyii\actions\PageViewAction'];
    }
}