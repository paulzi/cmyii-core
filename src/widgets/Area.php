<?php

namespace paulzi\cmyii\widgets;

use paulzi\cmyii\Cmyii;
use yii\base\Exception;
use yii\base\Widget;
use paulzi\cmyii\models\Block;

class Area extends Widget
{
    /**
     * @var string
     */
    public $module = 'cmyii';

    /**
     * @var ..\models\Layout
     */
    public $layout;

    /**
     * @var ..\models\Page
     */
    public $page;

    /**
     * @var bool
     */
    public $showDisabled = false;

    /**
     * @var bool
     */
    public $checkRoles = true;

    /**
     * @var Block[]
     */
    protected $blocks  = [];


    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->page !== null && $this->layout !== null) {
            throw new Exception("Not allowed to set both parameters in Area widget - layout and page.");
        }
        if ($this->page === null && $this->layout === null) {
            /** @var Cmyii $module */
            $module = \Yii::$app->getModule($this->module);
            $this->page = $module->getPage();
            if (!$this->page) {
                $this->layout = $module->getLayout();
            }
        }
        if ($this->page !== null || $this->layout !== null) {
            /** @var Block $blockClass */
            $blockClass = static::getBlockClass();
            $all = $blockClass::find()
                ->forArea($this->id, $this->page, $this->layout)
                ->all();
            $blockClass::getInheritance($this->blocks, $this->page, $this->layout);
            $blocks = [];
            foreach ($all as $block) {
                if ($this->showDisabled || !$block->isDisabled) {
                    if (!$this->checkRoles || $block->checkAccess()) {
                        $blocks[] = $block;
                    }
                }
            }
            $this->blocks = $blocks;
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $result = null;
        foreach ($this->blocks as $block) {
            $widget = $block->getWidget();
            $widget->page   = $this->page;
            $widget->layout = $this->layout;
            $result .= $widget->run();
        }
        return $result;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return count($this->blocks);
    }

    /**
     * @return string
     */
    protected static function getBlockClass()
    {
        return Block::className();
    }
}