<?php

namespace paulzi\cmyii\models;

use paulzi\cmyii\Cmyii;
use Yii;
use yii\helpers\Json;
use paulzi\sortable\SortableBehavior;
use paulzi\sortable\SortableTrait;

/**
 * This is the model class for table "{{%block}}".
 *
 * @property integer $id
 * @property integer $layout_id
 * @property integer $page_id
 * @property string $area
 * @property string $title
 * @property string $widget_class
 * @property string $template
 * @property integer $sort
 * @property integer $is_inherit
 *
 * @property Page $page
 * @property Layout $layout
 *
 * @method static Block|null findOne() findOne($condition)
 *
 * @property \paulzi\cmyii\widgets\BlockWidget $widget
 * @property bool|null $isDisabled
 * @property array $initParams
 */
class BaseBlock extends \yii\db\ActiveRecord
{
    use SortableTrait;

    /**
     * @var \paulzi\cmyii\widgets\BlockWidget
     */
    private $_widget;

    /**
     * @var array
     */
    private $_widgetInitParams = [];

    /**
     * @var bool|null
     */
    private $_isDisabled;

    /**
     * @var string|null
     */
    private $_roles;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cmyii_block}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => SortableBehavior::className(),
                'query' => ['page_id', 'layout_id', 'area'],
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['layout_id', 'page_id', 'sort', 'is_inherit'], 'integer'],
            [['area', 'title', 'widget_class'], 'required'],
            [['area', 'title', 'widget_class', 'template'], 'string', 'max' => 255],
            [['is_inherit'], 'filter', 'filter' => function($value) { return (bool)$value; }],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'layout_id' => 'Layout ID',
            'page_id' => 'Page ID',
            'area' => 'Area',
            'title' => 'Название',
            'widget_class' => 'Тип данных',
            'template' => 'Шаблон отображения',
            'roles' => 'Доступен ролям',
            'is_inherit' => 'Наследовать',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPage()
    {
        return $this->hasOne(Page::className(), ['id' => 'page_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLayout()
    {
        return $this->hasOne(Layout::className(), ['id' => 'layout_id']);
    }

    /**
     * @inheritdoc
     * @return BlockQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new BlockQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->is_inherit = (bool)$this->is_inherit;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->is_inherit = (bool)$this->is_inherit;
        return parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            $widget = $this->getWidget();
            $widget->delete();
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \paulzi\cmyii\widgets\BlockWidget
     * @throws \yii\base\InvalidConfigException
     */
    public function getWidget()
    {
        if ($this->_widget === null) {
            $this->_widget = Yii::createObject(array_merge($this->_widgetInitParams, ['class' => $this->widget_class]));
            $this->_widget->block = $this;
        }
        return $this->_widget;
    }

    /**
     * @return bool|null
     */
    public function getIsDisabled()
    {
        return $this->_isDisabled;
    }

    /**
     * @return bool|null
     */
    public function getRoles()
    {
        return $this->_roles;
    }

    /**
     * @return bool
     */
    public function checkAccess()
    {
        return Cmyii::checkRoles($this->roles, ['block' => $this]);
    }

    /**
     * @return array
     */
    public function getInitParams()
    {
        return $this->_widgetInitParams;
    }

    /**
     * @param Block[] $blocks
     * @param Page|null $page
     * @param Layout|null $layout
     * @return Block[]
     */
    public static function getInheritance($blocks, $page, $layout = null)
    {
        $conditions = ['or'];
        if ($page) {
            $conditions[] = ["bs.[[page_id]]" => $page->id];
            $conditions[] = ["bs.[[page_id]]" => $page->getInheritPages()];
            if ($page->basePage->layout) {
                $layout = $page->basePage->layout;
            }
        }
        if ($layout) {
            $conditions[] = ["bs.layout_id" => explode('/', $layout->path)];
        }
        $states = BlockState::find()
            ->alias('bs')
            ->joinWith(['page p', 'layout l'], false)
            ->andWhere(["bs.[[block_id]]" => array_map(function($value){ return $value->id; }, $blocks)])
            ->andWhere($conditions)
            ->orderBy([
                "p.[[path]]" => SORT_ASC,
                "l.[[path]]" => SORT_ASC,
            ])
            ->asArray()
            ->all();

        // processing
        $iStates = [];
        foreach ($states as $state) {
            $blockId = $state['block_id'];
            $iStates[$blockId]['page_id']   = $state['page_id']   ? (int)$state['page_id']   : null;
            $iStates[$blockId]['layout_id'] = $state['layout_id'] ? (int)$state['layout_id'] : null;
            $iState = &$iStates[$blockId];
            if (isset($iState['state_children'])) {
                $iState['state'] = $iState['state_children'];
            }
            $iState['state_local']    = isset($state['state'])          ? (bool)$state['state']          : null;
            $iState['state_children'] = isset($state['state_children']) ? (bool)$state['state_children'] : null;
            if ($state['template'] !== null) {
                $iState['template'] = $state['template'];
            }
            if ((string)$state['roles'] !== '') {
                $iState['roles'] = $state['roles'];
            }
            if ($state['params'] !== null) {
                if (!isset($iState['params'])) {
                    $iState['params'] = Json::decode($state['params'], true);
                } else {
                    $iState['params'] = array_merge($iState['params'], Json::decode($state['params'], true));
                }
            }
            unset($iState);
        }

        $result = [];
        foreach ($blocks as $block) {
            $iState = &$iStates[$block->id];
            if (($page && $iState['page_id'] === $page->id) || (!$page && $layout && $iState['layout_id'] === $layout->id) || (!$page && !$layout)) {
                $block->_isDisabled = isset($iState['state_local'])    ? !$iState['state_local']    : !empty($iState['state']);
            } else {
                $block->_isDisabled = isset($iState['state_children']) ? !$iState['state_children'] : !empty($iState['state']);
            }
            if (!$block->_isDisabled) {
                $result[] = $block;
            }
            if (isset($iState['template'])) {
                $block->template = $iState['template'];
            }
            if (isset($iState['roles'])) {
                $block->_roles = $iState['roles'];
            }
            if (isset($iState['params'])) {
                $block->_widgetInitParams = $iState['params'];
            }
            unset($iState);
        }
        return $result;
    }

    /**
     * @param Page|null $page
     * @param Layout|null $layout
     * @return $this
     */
    public function inherit($page, $layout = null)
    {
        static::getInheritance([$this], $page, $layout);
        return $this;
    }

    /**
     * @return string
     */
    public static function extraFormTemplate()
    {
        return null;
    }
}
