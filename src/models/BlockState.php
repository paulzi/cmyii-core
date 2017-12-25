<?php

namespace paulzi\cmyii\models;

/**
 * This is the model class for table "{{%block_state}}".
 *
 * @property integer $id
 * @property integer $block_id
 * @property integer $layout_id
 * @property integer $page_id
 * @property string $template
 * @property string $roles
 * @property integer $state
 * @property integer $state_children
 * @property string $params
 *
 * @property Page $page
 * @property Layout $layout
 */
class BlockState extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cmyii_block_state}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['block_id', 'layout_id', 'page_id', 'state', 'state_children'], 'integer'],
            [['params'], 'string'],
            [['template', 'roles'], 'string', 'max' => 255],
            [['block_id', 'layout_id', 'page_id'], 'unique', 'targetAttribute' => ['block_id', 'layout_id', 'page_id'], 'message' => 'The combination of Module ID, Layout ID and Page ID has already been taken.'],
            [['state', 'state_children', 'roles'], 'default'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'block_id' => 'Block ID',
            'layout_id' => 'Layout ID',
            'page_id' => 'Page ID',
            'template' => 'Template',
            'roles' => 'Доступен ролям',
            'state' => 'Состояние на текущей странице',
            'state_children' => 'Состояние в дочерних страницах',
            'params' => 'Params',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBlock()
    {
        return $this->hasOne(Block::className(), ['id' => 'block_id']);
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
}
