<?php

namespace paulzi\cmyii\models;

use paulzi\cmyii\Cmyii;
use yii\db\Expression;
use yii\helpers\Url;
use paulzi\autotree\AutoTreeTrait;
use paulzi\adjacencyList\AdjacencyListBehavior;
use paulzi\materializedPath\MaterializedPathBehavior;
use paulzi\cmyii\widgets\BlockWidget;

/**
 * This is the model class for table "{{%page}}".
 *
 * @property integer $id
 * @property integer $site_id
 * @property integer $parent_id
 * @property integer $sort
 * @property integer $depth
 * @property string $slug
 * @property string $title
 * @property string $path
 * @property string $link
 * @property integer $layout_id
 * @property integer $is_disabled
 * @property string $roles
 * @property string $seoTitle
 * @property string $seoH1
 * @property string $seoDescription
 * @property string $seoKeywords
 *
 * @property Block[] $blocks
 * @property BlockState[] $blockStates
 * @property Page $parent
 * @property Page[] $children
 * @property Layout $layout
 * @property Site $site
 *
 * @property Page[] $parents
 * @property Page $basePage
 * @property string $url
 * @property string $isActive
 * @property bool $available
 *
 * @method static Page|null findOne() findOne($condition)
 */
class BasePage extends \yii\db\ActiveRecord
{
    use AutoTreeTrait;

    /**
     * @var Page
     */
    private $_basePage = false;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cmyii_page}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            ['class' => AdjacencyListBehavior::className()],
            [
                'class'         => MaterializedPathBehavior::className(),
                'itemAttribute' => 'slug',
                'treeAttribute' => 'site_id',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @inheritdoc
     * @return ActiveQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ActiveQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['slug'], 'required', 'when' => function () { return !$this->isRoot(); }],
            [['site_id', 'parent_id', 'sort', 'depth', 'layout_id'], 'integer'],
            [['is_disabled'], 'boolean'],
            [['is_disabled'], 'filter', 'filter' => 'boolval'],
            [['slug', 'title', 'path', 'link', 'roles', 'seoTitle', 'seoH1', 'seoDescription', 'seoKeywords'], 'string', 'max' => 255],
            [['site_id', 'path'], 'unique', 'targetAttribute' => ['site_id', 'path'], 'message' => 'The combination of Site ID and Path has already been taken.'],
            [['roles'], 'default'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'             => 'ID',
            'site_id'        => 'ID сайта',
            'parent_id'      => 'ID родителя',
            'sort'           => 'Сортировка',
            'lft'            => 'Lft',
            'rgt'            => 'Rgt',
            'depth'          => 'Depth',
            'slug'           => 'Идентификатор',
            'title'          => 'Название',
            'path'           => 'Путь',
            'link'           => 'Переопределить ссылку',
            'layout_id'      => 'Макет',
            'is_disabled'    => 'Отключен',
            'roles'          => 'Доступен ролям',
            'seoTitle'       => 'Seo Title',
            'seoH1'          => 'Seo H1',
            'seoDescription' => 'Seo Description',
            'seoKeywords'    => 'Seo Keywords',
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->loadDefaultValues();
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            $ids = $this->getDescendants(null, true)
                ->select('id')
                ->column();
            /** @var BlockWidget[] $widgets */
            $widgets = [];
            $blocks = Block::find()
                ->andWhere(['page_id' => $ids])
                ->all();
            foreach ($blocks as $block) {
                if (!isset($widgets[$block->widget_class])) {
                    $widgets[$block->widget_class] = $block->getWidget();
                }
                $block->delete();
            }
            foreach ($widgets as $widget) {
                $widget->deletePages($ids);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBlocks()
    {
        return $this->hasMany(Block::className(), ['page_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBlockStates()
    {
        return $this->hasMany(BlockState::className(), ['page_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLayout()
    {
        return $this->hasOne(Layout::className(), ['id' => 'layout_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::className(), ['id' => 'site_id']);
    }

    /**
     * @return Page|null
     */
    public function getBasePage()
    {
        if ($this->_basePage !== false) {
            return $this->_basePage;
        }
        $this->_basePage = $this->getFromInherit('layout_id');
        return $this->_basePage = $this->_basePage ?: $this;
    }

    /**
     * @param string $field
     * @param mixed $nullValue
     * @return Page|null
     */
    public function getFromInherit($field, $nullValue = null)
    {
        if (!$this->isRelationPopulated('parents')) {
            return $this->getParents()
                ->andWhere(['not', [$field => $nullValue]])
                ->orderBy(['path' => SORT_DESC])
                ->limit(1)
                ->one();
        }
        foreach ($this->parents as $page) {
            if ($page->$field !== $nullValue) {
                return $page;
            }
        }
        return null;
    }

    /**
     * @return ActiveQuery|array
     */
    public function getInheritPages()
    {
        if (!$this->isRelationPopulated('parents')) {
            $query = $this->getParents()
                ->andWhere(new Expression('LENGTH(path) >= LENGTH(:basePath)'))
                ->addParams([':basePath' => $this->basePage->path]);
            return $query->select('id')->orderBy(['path' => SORT_ASC]);
        }

        $result = array_map(function ($value) { return $value->id; }, $this->parents);
        $pos = array_search($this->basePage->id, $result);
        if ($pos !== false) {
            $result = array_slice($result, $pos);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return Url::toRoute(['page/view', 'id' => $this->id]);
    }

    /**
     * @param string $module
     * @return bool
     */
    public function getIsActive($module = 'cmyii')
    {
        /** @var Cmyii $module */
        $module = \Yii::$app->getModule($module);
        $list   = [];
        if ($module->getPage()) {
            $list   = array_map(function ($item) { return $item->id; }, $module->getPage()->parents);
            $list[] = $module->getPage()->id;
        }

        return in_array($this->id, $list, true);
    }

    /**
     * @return bool
     */
    public function checkAccess()
    {
        return Cmyii::checkRoles($this->roles, ['page' => $this]);
    }

    /**
     * @return bool
     */
    public function getAvailable()
    {
        return !$this->is_disabled && $this->checkAccess();
    }

    /**
     * @return string
     */
    public static function extraFormTemplate()
    {
        return null;
    }
}
