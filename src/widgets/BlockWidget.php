<?php

namespace paulzi\cmyii\widgets;

use Yii;
use paulzi\cmyii\Cmyii;
use yii\base\Widget;
use paulzi\cmyii\models\Block;
use paulzi\cmyii\models\Page;
use paulzi\cmyii\models\Layout;

abstract class BlockWidget extends Widget
{
    /**
     * @var Block
     */
    public $block;

    /**
     * @var Page
     */
    public $page;

    /**
     * @var Layout
     */
    public $layout;


    /**
     */
    public function delete()
    {
    }

    /**
     * @param array $ids
     */
    public function deletePages($ids)
    {
    }

    /**
     * @param array $params
     * @return string
     */
    public function renderBlock($params = [])
    {
        $template = static::getViewTemplate($this->block->template);
        return $this->render($template, $params);
    }

    /**
     * @param string $template
     * @return string
     */
    public static function getViewTemplate($template)
    {
        $module = Cmyii::getInstance();
        $class  = static::className();
        $result = $template;
        if (isset($module->viewOverride[$class])) {
            $path = $module->viewOverride[$class] . '/' . $template;
            if (file_exists(Yii::getAlias($path))) {
                $result = $path;
            }
        }
        return $result;
    }
}