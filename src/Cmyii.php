<?php

namespace paulzi\cmyii;

use paulzi\cmyii\models\Layout;
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
     * @var bool
     */
    public $addRuleToAppend = false;

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
    protected $_site = false;

    /**
     * @var models\Page
     */
    protected $_page = false;

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
            ], $this->addRuleToAppend);
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
     * @return Site
     */
    public function getSite()
    {
        if ($this->_site === false) {
            $this->initSitePageFromRequest();
        }
        return $this->_site;
    }

    /**
     * @param Site $site
     */
    public function setSite($site)
    {
        $this->_site = $site;
    }

    /**
     * @return Page
     */
    public function getPage()
    {
        if ($this->_page === false) {
            $this->initSitePageFromRequest();
        }
        return $this->_page;
    }

    /**
     * @param Page $page
     */
    public function setPage($page)
    {
        $this->_page = $page;
    }

    /**
     * @return Layout
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * @param Layout $layout
     */
    public function setLayout($layout)
    {
        $this->_layout = $layout;
    }

    /**
     */
    public function initSitePageFromRequest()
    {
        $request = Yii::$app->request;
        $this->setSiteByHost($request->getHostInfo());
        $this->setPageByPath($request->getPathInfo());
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
        return '0.1.7';
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