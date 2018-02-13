<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;

/**
 * This is the model class for table "cms_page".
 *
 * @property integer $page_id
 * @property string $title
 * @property string $root_template
 * @property string $meta_keywords
 * @property string $meta_description
 * @property string $identifier
 * @property string $content_heading
 * @property string $content
 * @property string $creation_time
 * @property string $update_time
 * @property integer $is_active
 * @property integer $sort_order
 * @property string $layout_update_xml
 * @property string $custom_theme
 * @property string $custom_root_template
 * @property string $custom_layout_update_xml
 * @property string $custom_theme_from
 * @property string $custom_theme_to
 *
 * @property CmsPageStore[] $cmsPageStores
 * @property CoreStore[] $stores
 */
class CmsPage extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cms_page';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['meta_keywords', 'meta_description', 'content', 'layout_update_xml', 'custom_layout_update_xml'], 'string'],
            [['creation_time', 'update_time', 'custom_theme_from', 'custom_theme_to'], 'safe'],
            [['is_active', 'sort_order'], 'integer'],
            [['title', 'root_template', 'content_heading', 'custom_root_template'], 'string', 'max' => 255],
            [['identifier', 'custom_theme'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'page_id' => 'Page ID',
            'title' => 'Title',
            'root_template' => 'Root Template',
            'meta_keywords' => 'Meta Keywords',
            'meta_description' => 'Meta Description',
            'identifier' => 'Identifier',
            'content_heading' => 'Content Heading',
            'content' => 'Content',
            'creation_time' => 'Creation Time',
            'update_time' => 'Update Time',
            'is_active' => 'Is Active',
            'sort_order' => 'Sort Order',
            'layout_update_xml' => 'Layout Update Xml',
            'custom_theme' => 'Custom Theme',
            'custom_root_template' => 'Custom Root Template',
            'custom_layout_update_xml' => 'Custom Layout Update Xml',
            'custom_theme_from' => 'Custom Theme From',
            'custom_theme_to' => 'Custom Theme To',
        ];
    }

    public static function getGeneralSelectColumns()
    {
        return [
            'page_id',
            'title',
            'identifier',
            'sort_order',
        ];
    }

    public static function getPageColumns()
    {
        return [
            'page_id',
            'title',
            'identifier',
            'content',
        ];
    }
}
