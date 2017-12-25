<?php

namespace paulzi\cmyii\migrations;

use yii\db\Migration;

class m150515_131138_cmyii_init extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }


        // site table
        $this->createTable('{{%cmyii_site}}', [
            'id'          => $this->primaryKey(),
            'title'       => $this->string()->notNull(),
            'domains'     => $this->text(),
            'sort'        => $this->integer()->notNull()->defaultValue(0),
            'is_disabled' => $this->boolean()->notNull()->defaultValue(false),
        ], $tableOptions);


        // layout table
        $this->createTable('{{%cmyii_layout}}', [
            'id'        => $this->primaryKey(),
            'parent_id' => $this->integer(),
            'path'      => $this->string(),
            'depth'     => $this->integer()->notNull()->defaultValue(0),
            'title'     => $this->string()->notNull(),
            'template'  => $this->string(),
        ], $tableOptions);
        $this->createIndex('cmyii_layout_parent_id_idx', '{{%cmyii_layout}}', 'parent_id');
        $this->createIndex('cmyii_layout_path_idx',      '{{%cmyii_layout}}', 'path', true);
        $this->addForeignKey('cmyii_layout_parent_id_cmyii_layout_id_fkey', '{{%cmyii_layout}}', 'parent_id', '{{%cmyii_layout}}', 'id', 'CASCADE', 'CASCADE');


        // page table
        $this->createTable('{{%cmyii_page}}', [
            'id'             => $this->primaryKey(),
            'site_id'        => $this->integer()->notNull(),
            'parent_id'      => $this->integer(),
            'sort'           => $this->integer(),
            'depth'          => $this->integer(),
            'slug'           => $this->string()->notNull(),
            'title'          => $this->string()->notNull(),
            'path'           => $this->string()->notNull(),
            'link'           => $this->string(),
            'layout_id'      => $this->integer(),
            'is_disabled'    => $this->boolean()->notNull()->defaultValue(false),
            'roles'          => $this->string(),
            'seoTitle'       => $this->string(),
            'seoH1'          => $this->string(),
            'seoDescription' => $this->string(),
            'seoKeywords'    => $this->string(),
        ], $tableOptions);
        $this->createIndex('cmyii_page_site_id_path_idx',   '{{%cmyii_page}}', ['site_id', 'path'], true);
        $this->createIndex('cmyii_page_parent_id_sort_idx', '{{%cmyii_page}}', ['parent_id', 'sort']);
        $this->createIndex('cmyii_page_layout_id_idx',      '{{%cmyii_page}}', ['layout_id']);
        $this->addForeignKey('cmyii_page_site_id_cmyii_site_id_fkey',     '{{%cmyii_page}}', 'site_id',   '{{%cmyii_site}}',   'id', 'CASCADE',  'CASCADE');
        $this->addForeignKey('cmyii_page_layout_id_cmyii_layout_id_fkey', '{{%cmyii_page}}', 'layout_id', '{{%cmyii_layout}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('cmyii_page_parent_id_cmyii_page_id_fkey',   '{{%cmyii_page}}', 'parent_id', '{{%cmyii_page}}',   'id', 'CASCADE',  'CASCADE');


        // block table
        $this->createTable('{{%cmyii_block}}', [
            'id'           => $this->primaryKey(),
            'layout_id'    => $this->integer(),
            'page_id'      => $this->integer(),
            'area'         => $this->string()->notNull(),
            'title'        => $this->string()->notNull(),
            'widget_class' => $this->string()->notNull(),
            'template'     => $this->string(),
            'sort'         => $this->integer()->notNull()->defaultValue(0),
            'is_inherit'   => $this->boolean()->notNull()->defaultValue(false),
        ], $tableOptions);
        $this->createIndex('cmyii_block_layout_id_area_idx', '{{%cmyii_block}}', ['layout_id', 'area']);
        $this->createIndex('cmyii_block_page_id_area_idx',   '{{%cmyii_block}}', ['page_id', 'area']);
        $this->addForeignKey('cmyii_block_layout_id_cmyii_layout_id_fkey', '{{%cmyii_block}}', 'layout_id', '{{%cmyii_layout}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cmyii_block_page_id_cmyii_page_id_fkey',     '{{%cmyii_block}}', 'page_id',   '{{%cmyii_page}}',   'id', 'CASCADE', 'CASCADE');


        // block_state table
        $this->createTable('{{%cmyii_block_state}}', [
            'id'             => $this->primaryKey(),
            'block_id'       => $this->integer()->notNull(),
            'layout_id'      => $this->integer(),
            'page_id'        => $this->integer(),
            'template'       => $this->string(),
            'roles'          => $this->string(),
            'state'          => $this->boolean(),
            'state_children' => $this->boolean(),
            'params'         => $this->text(),
        ], $tableOptions);
        $this->createIndex('cmyii_block_state_block_id_layout_id_page_id_idx', '{{%cmyii_block_state}}', ['block_id', 'layout_id', 'page_id'], true);
        $this->createIndex('cmyii_block_state_layout_id_idx',                  '{{%cmyii_block_state}}', ['layout_id']);
        $this->createIndex('cmyii_block_state_page_id_idx',                    '{{%cmyii_block_state}}', ['page_id']);
        $this->addForeignKey('cmyii_block_state_block_id_cmyii_block_id_fkey',   '{{%cmyii_block_state}}', 'block_id',  '{{%cmyii_block}}',  'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cmyii_block_state_layout_id_cmyii_layout_id_fkey', '{{%cmyii_block_state}}', 'layout_id', '{{%cmyii_layout}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cmyii_block_state_page_id_cmyii_page_id_fkey',     '{{%cmyii_block_state}}', 'page_id',   '{{%cmyii_page}}',   'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cmyii_block_state}}');
        $this->dropTable('{{%cmyii_block}}');
        $this->dropTable('{{%cmyii_page}}');
        $this->dropTable('{{%cmyii_layout}}');
        $this->dropTable('{{%cmyii_site}}');
    }
}
