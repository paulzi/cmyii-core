<?php

namespace paulzi\cmyii\models;

/**
 * This is the base ActiveQuery class for cmyii.
 */
class ActiveQuery extends \yii\db\ActiveQuery
{
    /**
     * @return $this
     */
    public function active()
    {
        $this->andWhere(['is_disabled' => false]);
        return $this;
    }
}