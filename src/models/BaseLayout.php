<?php

namespace paulzi\cmyii\models;

use paulzi\autotree\AutoTreeTrait;
use paulzi\adjacencyList\AdjacencyListBehavior;
use paulzi\materializedPath\MaterializedPathBehavior;

/**
 * This is the model class for table "{{%layout}}".
 *
 * @property integer $id
 * @property string $path
 * @property integer $depth
 * @property string $title
 * @property string $template
 *
 * @property Block[] $blocks
 * @property BlockState[] $blockStates
 * @property Page[] $pages
 * @property Layout $parent
 * @property Layout[] $parents
 * @property Layout[] $children
 *
 *
 * @method static Layout|null findOne() findOne($condition)
 */
class BaseLayout extends \yii\db\ActiveRecord
{
    use AutoTreeTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cmyii_layout}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class'    => AdjacencyListBehavior::className(),
                'sortable' => false,
            ],
            [
                'class'    => MaterializedPathBehavior::className(),
                'sortable' => false,
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
            [['depth'], 'integer'],
            [['title', 'template'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'depth' => 'Depth',
            'title' => 'Название',
            'template' => 'Шаблон',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBlocks()
    {
        return $this->hasMany(Block::className(), ['layout_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBlockStates()
    {
        return $this->hasMany(BlockState::className(), ['layout_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPages()
    {
        return $this->hasMany(Page::className(), ['layout_id' => 'id']);
    }

    /**
     * @return string
     */
    public static function extraFormTemplate()
    {
        return null;
    }
}
