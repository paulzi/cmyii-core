<?php

namespace paulzi\cmyii\actions;

use Yii;
use yii\base\Action;
use paulzi\cmyii\Cmyii;

class PageViewAction extends Action
{
    /**
     * @var string
     */
    public $module = 'cmyii';


    /**
     * @inheritdoc
     */
    public function run()
    {
        /** @var Cmyii $module */
        $module = Yii::$app->getModule($this->module);
        $layout = $module->getLayout();
        if ($layout && $layout->template) {
            $this->controller->layout = $layout->template;
        }
        $page = $module->getPage();
        Yii::$app->view->title = $page->seoTitle ?: $page->title;
        if ($page->seoDescription) {
            Yii::$app->view->registerMetaTag(['name' => 'description', 'content' => $page->seoDescription], 'description');
        }
        if ($page->seoKeywords) {
            Yii::$app->view->registerMetaTag(['name' => 'keywords', 'content' => $page->seoKeywords], 'keywords');
        }
        return $this->controller->renderContent(null);
    }
}