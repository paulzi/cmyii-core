<?php

namespace paulzi\cmyii\models;

use paulzi\sortable\SortableBehavior;
use paulzi\sortable\SortableTrait;

/**
 * This is the model class for table "{{%site}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $domains
 * @property integer $sort
 * @property integer $is_disabled
 *
 * @property Page $root
 * @property Page[] $pages
 *
 * @method static Site|null findOne() findOne($condition)
 */
class BaseSite extends \yii\db\ActiveRecord
{
    use SortableTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cmyii_site}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            ['class' => SortableBehavior::className()],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['domains'], 'string'],
            [['sort'], 'integer'],
            [['is_disabled'], 'boolean'],
            [['is_disabled'], 'filter', 'filter' => function($value) { return (bool)$value; }],
            [['title'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'domains' => 'Список обслуживаемых доменов',
            'sort' => 'Sort',
            'is_disabled' => 'Выключен',
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->loadDefaultValues();
        $this->is_disabled = (bool)$this->is_disabled;
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->is_disabled = (bool)$this->is_disabled;
        return parent::afterFind();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoot()
    {
        return $this->hasOne(Page::className(), ['site_id' => 'id'])
            ->andWhere(['parent_id' => null]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPages()
    {
        return $this->hasMany(Page::className(), ['site_id' => 'id']);
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
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            $this->root->deleteWithChildren();
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $page = new Page([
            'site_id' => $this->id,
            'title'   => 'Главная страница',
            'path'    => '',
            'slug'    => '',
        ]);
        $page->makeRoot()->save();
    }

    /**
     * @return null|string
     */
    public function getPrimaryDomain()
    {
        $list = explode("\n", $this->domains);
        foreach ($list as $item) {
            $item = trim($item);
            if ($item && $item[0] !== '/' && mb_strpos($item, '?') === false && mb_strpos($item, '*') === false) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public static function extraFormTemplate()
    {
        return null;
    }
}
