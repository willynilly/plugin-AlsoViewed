<?php
/**
 * AlsoViewed_Item class - represents an also viewed item
 *
 * @copyright Copyright 2012 Will Riley
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @package AlsoViewed
 */
class AlsoViewed_Item extends Omeka_Record_AbstractRecord
{
    public $item_id;
    public $related_item_id;
    public $before_view_count = 0;
    public $after_view_count = 0;
    public $total_view_count = 0;
    public $added;
    public $modified;
    
    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Timestamp($this);
    }
    
    /**
     * Returns the base item object
     * 
     * @return Item The base item object
     */
    public function getItem()
    {
        if ($this->item_id) {
           return $this->_db->getTable('Item')->find($this->item_id);
        }
        return null;
    }
    
    /**
     * Returns the related item object
     * 
     * @return Item The related item object
     */
    public function getRelatedItem()
    {
        if ($this->related_item_id) {
           return $this->_db->getTable('Item')->find($this->related_item_id);
        }
        return null;
    }
}