<?php

use yii\db\Schema;
use yii\db\Migration;

// yii migrate/up --migrationPath=@vendor/kyra/sm/migrations

class m140708_122841_create_gallery_tables extends Migration
{
    public function up()
    {
        $this->createTable('gallery', [
            'GalleryID' => 'pk',
            'UrlKey' => Schema::TYPE_STRING.'(255) NOT NULL',
            'GalleryName' => Schema::TYPE_STRING.'(255) NOT NULL',
            'GalleryDescription' => Schema::TYPE_TEXT,
            'FacebookAlbumID' => Schema::TYPE_STRING.'(255) DEFAULT NULL',
            'SortOrder' => Schema::TYPE_INTEGER.' DEFAULT 1',
            'HeaderIID' => Schema::TYPE_INTEGER.' DEFAULT NULL',
        ]);
        $this->createIndex('guk', 'gallery', 'UrlKey', true);
        $this->createIndex('ghiid', 'gallery', 'HeaderIID');


        $this->createTable('gallery_images', [
            'GIID' => 'pk',
            'GalleryID' => Schema::TYPE_INTEGER.' NOT NULL',
            'IID' => Schema::TYPE_INTEGER.' NOT NULL',
            'SortOrder' => Schema::TYPE_INTEGER.' DEFAULT 1',
        ]);
        $this->createIndex('gid', 'gallery_images', 'GalleryID');
        $this->createIndex('giid', 'gallery_images', 'IID');


        $this->addForeignKey('gal_hiid', 'gallery', 'HeaderIID', 'images', 'IID', 'SET NULL', 'CASCADE');
        $this->addForeignKey('gi_iid', 'gallery_images', 'IID', 'images', 'IID', 'CASCADE', 'CASCADE');
        $this->addForeignKey('gi_gid', 'gallery_images', 'GalleryID', 'gallery', 'GalleryID', 'CASCADE', 'CASCADE');
    }

    public function down()
    {
        $this->dropTable('gallery');
        $this->dropTable('gallery_images');
    }

}
