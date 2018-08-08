<?php

namespace paulzi\cmyii\components;

use Yii;
use paulzi\cmyii\Cmyii;
use paulzi\cmyii\models\Page;

class PageUrlRule extends \yii\base\BaseObject implements \yii\web\UrlRuleInterface
{
    /**
     * @var string
     */
    public $module = 'cmyii';

    /**
     * @var string
     */
    public $route;


    /**
     * @inheritdoc
     */
    public function createUrl($manager, $route, $params)
    {
        if ($route !== $this->route) {
            return false;
        }
        $page = Cmyii::getInstance()->page;
        if (!$page) {
            return null;
        }

        /** @var Cmyii $module */
        $host = null;
        $module = Yii::$app->getModule($this->module);
        if ($page->site_id !== $module->getSiteIdByHost($manager->getHostInfo())) {
            $host = $page->site->getPrimaryDomain();
        }
        $url = $host . $page->path;

        if (!empty($params) && ($query = http_build_query($params)) !== '') {
            $url .= '?' . $query;
        }
        return $url;
    }

    /**
     * @inheritdoc
     */
    public function parseRequest($manager, $request)
    {
        /** @var Cmyii $module */
        $module = Yii::$app->getModule($this->module);
        $module->initSitePageFromRequest($request);
        $page = $module->getPage();

        return $page ? [$this->route, []] : false;
    }
}