<?php
/**
 * Also Viewed global functions
 *
 * @copyright Copyright 2012 Will Riley
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */
 
/**
 * Returns an array of AlsoViewed_Item objects related to an item
 * Once you have this array, you can get each related item
 * from each AlsoViewed_Item object by calling AlsoViewed_Item::getRelatedItem().
 *
 * @param Item|null $item  The base item.  If null, it is the current item in the view.
 * @param string $sortParam  The AlsoViewed_Item field by which to sort the results.
 * By default it is 'total_view_count'.  You might also consider using 'before_view_count' 
 * to sort related items by how frequently they are viewed before $item, or 'after_view_count'
 * to sort related items by how frequently they are viewed after $item. 
 * @param string $sortDire  The direction to sort the results (either 'DESC' or 'ASC').  
 * By default, it is 'DESC'.
 * @param int|null $limit The number of item results per page.  If null, it returns all results
 * @param int|null $page The result page to show.  If $limit is null, this should be null. 
 * If $limit is not null, and this is not null, then it shows the first results page.
 *
 */
function also_viewed_find_related_items($item=null, $sortParam='total_view_count', $sortDir='DESC', $limit=null, $page=null) 
{
     if ($item === null) {
         $item = get_current_record('item');
     }
     $results = array();
     if ($item && $item->exists()) {
         $db = get_db();
         $tbl = $db->getTable('AlsoViewed_Item');
         $results = $tbl->findBy(array(
            'item_id' => $item->id,
            Omeka_Db_Table::SORT_PARAM => $sortParam,
            Omeka_Db_Table::SORT_DIR_PARAM => $sortDir,
          ), $limit, $page);
     }
     return $results;
}
 
/**
 * Returns an unordered list with links to the public show pages of related items.
 *
 * @param Item|null $item  The base item.  If null, it is the current item in the view.
 * @param string $sortParam  The AlsoViewed_Item field by which to sort the results.
 * By default it is 'total_view_count'.  You might also consider using 'before_view_count' 
 * to sort related items by how frequently they are viewed before $item, or 'after_view_count'
 * to sort related items by how frequently they are viewed after $item. 
 * @param string $sortDire  The direction to sort the results (either 'DESC' or 'ASC').  
 * By default, it is 'DESC'.
 * @param int|null $limit The number of item results per page.  If null, it returns all results
 * @param int|null $page The result page to show.  If $limit is null, this should be null. 
 * If $limit is not null, and this is not null, then it shows the first results page.
 *
 */
function also_viewed_related_item_links($item=null, $sortParam='total_view_count', $sortDir='DESC', $limit=null, $page=null)
{
     $relatedItemInfos = also_viewed_find_related_items($item, $sortParam, $sortDir, $limit, $page);
     $html = '<ul>';
     foreach($relatedItemInfos as $relatedItemInfo) {
         $relatedItem = $relatedItemInfo->getRelatedItem();
         $html .= '<li>' . link_to_item(null, array(), 'show', $relatedItem) . '</li>';
     }
     $html .= '</ul>';
     return $html;
}