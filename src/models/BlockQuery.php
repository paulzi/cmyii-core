<?php

namespace paulzi\cmyii\models;


/**
 * This is the ActiveQuery class for [[Block]].
 *
 * @see Block
 *
 * @method Block one($db = null)
 * @method Block[] all($db = null)
 */
class BlockQuery extends \yii\db\ActiveQuery
{
    /**
     * @param string|null $area
     * @param Page|null $page
     * @param Layout|null $layout
     * @return $this
     */
    public function forArea($area, $page, $layout = null)
    {
        $conditions = ['or'];
        if ($page) {
            $conditions[] = ['page_id' => $page->id];
            $conditions[] = ['is_inherit' => 1, 'page_id' => $page->getInheritPages()];

            if ($page->basePage->layout) {
                $layout = $page->basePage->layout;
            }
        }
        if ($layout) {
            $conditions[] = ['layout_id' => $layout->id];
            $conditions[] = ['is_inherit' => 1, 'layout_id' => explode('/', $layout->path)];
        }
        return $this
            ->andFilterWhere(['area' => $area])
            ->andWhere($conditions)
            ->orderBy(['sort' => SORT_ASC]);
    }
}