<?php
/**
 * Also Viewed plugin
 *
 * This plugin tracks how often items were viewed before and after each other.
 * After installing the plugin, it will count item public page views by anonymous users.
 * If a user is logged in, it will not count page views.
 *
 * The plugin also provides some global functions to show related item links.
 * For example, you can add the following code to your
 * items/show.php theme page:
 *
 * <div>
 * <?php echo also_viewed_related_item_links(); ?>
 * </div>
 *
 * For more information on how to use these global functions, check out:
 * helpers/AlsoViewedFunctions.php
 *
 * In addition, the plugin automatically shows the 
 * top related items for each item on the admin items show page sidebar.
 * 
 * @copyright Copyright 2012 Will Riley 
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

require_once dirname(__FILE__) . '/helpers/AlsoViewedFunctions.php';

/**
 * The Also Viewed plugin.
 * 
 * @package Omeka\Plugins\AlsoViewed
 */
class AlsoViewedPlugin extends Omeka_Plugin_AbstractPlugin
{
    const DEFAULT_DISPLAY_ITEM_COUNT = 5; // the maximum number of items 
                                          // to display in each admin sidebar widget
    
    protected $_hooks = array(
        'install',
        'uninstall',
        'public_items_show',
        'admin_items_show_sidebar'
    );
    
    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        $db = $this->_db;

        // create also viewed items table
        $db->query("CREATE TABLE IF NOT EXISTS `{$db->prefix}also_viewed_items` (
           `id` int(10) unsigned NOT NULL auto_increment,
           `item_id` int(10) unsigned NOT NULL,
           `related_item_id` int(10) unsigned NOT NULL,
           `before_view_count` int(10) unsigned NOT NULL,
           `after_view_count` int(10) unsigned NOT NULL,
           `total_view_count` int(10) unsigned NOT NULL,
           `added` timestamp NOT NULL default '0000-00-00 00:00:00',
           `modified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
           PRIMARY KEY  (`id`),
           KEY (`item_id`),
           KEY (`related_item_id`),
           KEY (`total_view_count`),
           KEY (`before_view_count`),
           KEY (`after_view_count`)
           ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

        $this->_installOptions();
    }

    /**
     * Uninstall the plugin.
     */
    function hookUninstall()
    {
        $db = $this->_db;
        
        // drop the tables
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}also_viewed_items`";
        $db->query($sql);
        
        $this->_uninstallOptions();
    }
    
    /**
     * Track the item on the public items show page.
     */
    public function hookPublicItemsShow()
    {
        $item = get_current_record('item');        
        $this->_trackItem($item);
    }
    
    /**
     * Show the top total, before, and after related items on the admin items show page.
     */
    public function hookAdminItemsShowSidebar()
    {
        $displayItemCount = self::DEFAULT_DISPLAY_ITEM_COUNT;
        $item = get_current_record('item');        
        $html = '<div class="panel" id="also-viewed-related-items-total">';
        $html .= '<h4>' . __('Top %s Related Items (Viewed Before and After)', $displayItemCount) . '</h4>';
        $html .= also_viewed_related_item_links($item, 
                                                'total_view_count', 
                                                'd', 
                                                $displayItemCount,
                                                null,
                                                true);
        $html .= '</div>';
        $html .= '<div class="panel" id="also-viewed-related-items-before">';
        $html .= '<h4>' . __('Top %s Related Items (Viewed Before)', $displayItemCount) . '</h4>';
        $html .= also_viewed_related_item_links($item, 
                                                'before_view_count', 
                                                'd', 
                                                $displayItemCount, 
                                                null, 
                                                true);
        $html .= '</div>';
        $html .= '<div class="panel" id="also-viewed-related-items-after">';
        $html .= '<h4>' . __('Top %s Related Items (Viewed After)', $displayItemCount) . '</h4>';
        $html .= also_viewed_related_item_links($item, 
                                                'after_view_count', 
                                                'd', 
                                                $displayItemCount,
                                                null,
                                                true);
        $html .= '</div>';
        echo $html;
    }

    /**
     * Increments the before view count for the previous item as coming after the base item
     * and increments the after view count for the base item as coming after the previously viewed
     * item. 
     *
     * @param Item $item The base item to track. 
     */    
    protected function _trackItem($item) 
    {
        if (!current_user()) {
            $session = new Zend_Session_Namespace('AlsoViewed');
            if ($item and $item->exists()) {            
                $db = $this->_db;            
                if (isset($session->prevItemId)) {                
                    $prevItemId = (int)$session->prevItemId;
                    if ($prevItemId) {
                        $prevItem = $db->getTable('Item')->find($prevItemId);
                        if ($prevItem && $prevItem->id != $item->id) {
                            $tbl = $db->getTable('AlsoViewed_Item');
                            $av = $tbl->findBy(array('item_id' => $prevItem->id, 
                                                     'related_item_id' => $item->id));
                            if (!$av) {
                                $av = new AlsoViewed_Item;
                                $av->item_id = $prevItem->id;
                                $av->related_item_id = $item->id;
                            } else {
                                $av = $av[0];
                            } 
                            $av->after_view_count++;                        
                            $av->total_view_count++;
                            $av->save();
                            $av = $tbl->findBy(array('item_id' => $item->id, 
                                                     'related_item_id' => $prevItem->id));
                            if (!$av) {
                                $av = new AlsoViewed_Item;
                                $av->item_id = $item->id;
                                $av->related_item_id = $prevItem->id;
                            } else {
                                $av = $av[0];
                            }
                            $av->before_view_count++;
                            $av->total_view_count++;
                            $av->save();
                        }
                    }
                }
                $session->prevItemId = $item->id;
            }
        }
    }
}