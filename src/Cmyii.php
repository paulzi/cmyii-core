<?php

namespace paulzi\cmyii;

use paulzi\cmyii\models\Page;
use paulzi\cmyii\models\Site;
use Yii;
use yii\base\Module;
use yii\caching\Cache;

/**
 * @property models\Site $site
 * @property models\Page $page
 */
class Cmyii extends Module
{
    /**
     * @var bool
     */
    public $addRule = true;

    /**
     * @var string
     */
    public $cache = 'cache';

    /**
     * @var array
     */
    public $viewOverride = [];

    /**
     * @var models\Site
     */
    protected $_site;

    /**
     * @var models\Page
     */
    protected $_page;

    /**
     * @var models\Layout
     */
    protected $_layout;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->addRule) {
            Yii::$app->urlManager->addRules([
                [
                    'class' => 'paulzi\cmyii\components\PageUrlRule',
                    'route' => $this->addRule === true ? $this->id . '/default/view' : $this->addRule,
                ],
            ], false);
        }
    }

    /**
     * @inheritdoc
     */
    public function getViewPath()
    {
        return $this->module->getViewPath();
    }

    /**
     * @return models\Site
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * @return models\Page
     */
    public function getPage()
    {
        return $this->_page;
    }

    /**
     * @return models\Layout
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * @param string $host
     */
    public function setSiteByHost($host)
    {
        $id = $this->getSiteIdByHost($host);
        $this->_site   = $id ? Site::findOne($id) : null;
        $this->_page   = null;
        $this->_layout = null;
    }

    /**
     * @param string $path
     * @param bool $checkAccess
     */
    public function setPageByPath($path, $checkAccess = true)
    {
        if ($this->_site) {
            $path = trim($path, '/');
            /** @var Page $page */
            $page = Page::find()
                ->active()
                ->andWhere([
                    'site_id' => $this->_site->id,
                    'path'    => $path ? '/' . $path : '',
                ])
                ->one();
            if ($page && (!$checkAccess || $page->checkAccess())) {
                $this->_page = $page;
            }
            if ($this->_page) {
                if ($this->_page->layout_id && $this->_page->layout) {
                    $this->_layout = $this->_page->layout;
                } elseif ($this->_page->basePage) {
                    $this->_layout = $this->_page->basePage->layout;
                } else {
                    $this->_layout = null;
                }
            }
        } else {
            $this->_page   = null;
            $this->_layout = null;
        }
    }

    /**
     * @param $host
     * @return int|null
     */
    public function getSiteIdByHost($host)
    {
        /** @var Cache $cache */
        $cache = Yii::$app->get($this->cache);
        if (!$cache) {
            return $this->getSiteIdByHostInternal($host);
        }
        $cacheKey = [__CLASS__, 'setSiteByHost', $this->id, $host];
        $id = $cache->get($cacheKey);
        if ($id === false) {
            $id = $this->getSiteIdByHostInternal($host);
        }
        $cache->set($cacheKey, $id);
        return $id;
    }

    /**
     * @return string
     */
    protected function defaultVersion()
    {
        return '0.1.0';
    }

    /**
     * @param $host
     * @return int|null
     */
    protected function getSiteIdByHostInternal($host)
    {
        $sites = Site::find()
            ->active()
            ->select('domains')
            ->orderBy('sort')
            ->asArray()
            ->indexBy('id')
            ->column();
        $replace = [
            '\*\.'  => '(?:\*\.)?',
            '\.\*'  => '(?:\.\*)?',
            '\*'    => '[^:\/]*',
            '\?'    => '[^:\/]?',
        ];

        foreach ($sites as $id => $domains) {
            foreach (explode("\n", $domains) as $domain) {
                if ($regexp = trim($domain)) {
                    if ($regexp[0] !== '/') {
                        $regexp = preg_quote($regexp, '/');
                        $regexp = str_replace(array_keys($replace), array_values($replace), $regexp);
                        $regexp = '/^' . $regexp . '$/iu';
                    }
                    try {
                        if (preg_match($regexp, $host)) {
                            return (int)$id;
                        }
                    } catch (\Exception $e) {
                        Yii::warning("Wrong item '{$domain}' in site (ID {$id}) domains list", 'cmyii');
                        continue;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string $roles
     * @param array $params
     * @return bool
     */
    public static function checkRoles($roles, $params = [])
    {
        if ((string)$roles === '') {
            return true;
        }
        $roles = explode(',', $roles);
        foreach ($roles as $role) {
            if ($role === '*') {
                return true;
            }
            if ($role === '?' && Yii::$app->user->isGuest) {
                return true;
            }
            if ($role === '@' && !Yii::$app->user->isGuest) {
                return true;
            }
            if (Yii::$app->user->can($role, $params)) {
                return true;
            }
        }
        return false;
    }
}