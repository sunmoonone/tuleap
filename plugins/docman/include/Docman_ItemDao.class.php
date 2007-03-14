<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */
require_once('DocmanConstants.class.php');
require_once('common/dao/include/DataAccessObject.class.php');

class Docman_ItemDao extends DataAccessObject {

    function Docman_ItemDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    /**
     * Return the row that match given id.
     *
     * @return DataAccessResult
     */
    function searchById($id, $params = array()) {
        $_id = (int) $id;
        return $this->_searchWithCurrentVersion(' i.item_id = '.$_id, '', ' ORDER BY version_date DESC LIMIT 1', array(), $params);
    }

    function searchByIdList($idList) {        
        if(is_array($idList) && count($idList) > 0) {
            $sql_where = sprintf(' i.item_id IN (%s)', implode(', ', $idList));
        }
        return $this->_searchWithCurrentVersion($sql_where, '', '');
    }

    function searchByTitle($title) {
        $where = ' i.title = '.$this->da->quoteSmart($title);
        $order = ' ORDER BY version_date DESC LIMIT 1';
        return $this->_searchWithCurrentVersion($where, '', $order);
    }

    /**
     * Return the list of items for a given projet according to filters
     *
     * @return DataAccessResult
     */
    function searchByGroupId($id, &$filters) {
        // Where clause        
        // Select on group_id
        $_id = (int) $id;
        $sql_where = ' i.group_id = '.$_id;
                
        //
        // Order clause        
        $sql_order = '';


        $fromStmts = array();
        //
        // Filters
        if($filters !== null) {
            $fi =& $filters->getFilterIterator();
            $fi->rewind();
            while($fi->valid()) {
                $f =& $fi->current();

                $sqlFilter =& Docman_SqlFilterFactory::getFromFilter($f);
                
                if($sqlFilter !== null) {   
                    // Handle 'from' clause
                    $fromStmts = array_merge($fromStmts, $sqlFilter->getFromClause());                    

                    // Handle 'where' clause
                    $where = $sqlFilter->getWhereClause();
                    if($where != '') {
                        $sql_where .= ' AND '.$where;
                    }

                    // Handle 'order' clause
                    $order = $sqlFilter->getOrderClause();
                    if($order != '') {
                        if($sql_order != '') {
                            $sql_order .= ', ';
                        }
                        $sql_order .= $order;
                    }

                }
                
                $fi->next();
            }
        }

        // Prepare 'order' clause if any
        if($sql_order != '') {
            $sql_order = ' ORDER BY '.$sql_order;
        }
        
        $from = array_unique($fromStmts);
             
        return $this->_searchWithCurrentVersion($sql_where, '', $sql_order, $from);

    }
   
    function _searchWithCurrentVersion($where, $group = '', $order = '', $from = array(), $params = array()) {
        $sql = 'SELECT i.*, '
            .' v.id as version_id, v.number as version_number, v.user_id as version_user_id, v.label as version_label, '
            .' v.changelog as version_changelog, v.date as version_date, v.filename as version_filename, v.filesize as version_filesize, '
            .' v.filetype as version_filetype, v.path as version_path, '
            .' 1 as folder_nb_of_children '
            .' FROM plugin_docman_item AS i LEFT JOIN plugin_docman_version AS v ON (i.item_id = v.item_id) '
            .(count($from) > 0 ? ', '.implode(', ', $from) : '')
            .' WHERE 1 AND ';
        if (!isset($params['ignore_deleted']) || !$params['ignore_deleted']) {
            $sql .= ' i.delete_date IS NULL AND ';
        }
        $sql .= $where . $group . $order;
        return $this->retrieve($sql);
    }
    
    /**
     * Return the list of items that have children for givent group_id.
     *
     * @return DataAccessResult
     */
    function searchAllParent($group_id) {
        $sql = sprintf('SELECT parent_id'
                       .' FROM plugin_docman_item'
                       .' WHERE group_id=%d'
                       .' GROUP BY parent_id',
                       $group_id);

        return $this->retrieve($sql);
    }

    /**
     * Return the list of items for givent group and type.
     *
     * @return DataAccessResult
     */
    function searchAllByType($group_id, $type) {
         $sql = sprintf('SELECT *'
                        .' FROM plugin_docman_item'
                        .' WHERE group_id=%d'
                        .' AND item_type=%d',
                        $group_id,
                        $type);

         return $this->retrieve($sql);
    }

    /**
     * Return the list of user preferences regarding collapsed folders for
     * given group_id and user_id.
     *
     * @return DataAccessResult
     */
    function searchExpandedUserPrefs($group_id, $user_id) {
        $pref_base = PLUGIN_DOCMAN_EXPAND_FOLDER_PREF.'_'.((int)$group_id);

        $sql = sprintf('SELECT preference_name, preference_value'
                       .' FROM user_preferences'
                       .' WHERE user_id=%d'
                       .' AND preference_name LIKE "%s"'
                       ,$user_id
                       ,$pref_base.'_%');

        return $this->retrieve($sql);
    }  

    /**
     * create a row in the table plugin_docman_item
     *
     * @return true or id(auto_increment) if there is no error
     */
    function create($parent_id=null, $group_id=null, $title=null, 
                    $description=null, $create_date=null, $update_date=null, 
                    $user_id=null, $rank=null, $item_type=null, $link_url=null,
                    $wiki_page=null, $file_is_embedded=null) {

        $arg    = array();
        $values = array();

        if($parent_id !== null) {
            $arg[]    = 'parent_id';
            $values[] = ((int) $parent_id);
        }

        if($group_id !== null) {
            $arg[] = 'group_id';
            $values[] = ((int) $group_id);
        }

        if($title !== null) {
            $arg[] = 'title';
            $values[] = $this->da->quoteSmart($title);
        }

        if($description !== null) {
            $arg[] = 'description';
            $values[] = $this->da->quoteSmart($description);
        }

        if($create_date !== null) {
            $arg[] = 'create_date';
            $values[] = ((int) $create_date);
        }

        if($update_date !== null) {
            $arg[] = 'update_date';
            $values[] = ((int) $update_date);
        }

        if($user_id !== null) {
            $arg[] = 'user_id';
            $values[] = ((int) $user_id);
        }

        if($rank !== null) {
            $arg[] = 'rank';
            $values[] = ((int) $rank);
        }

        if($item_type !== null) {
            $arg[] = 'item_type';
            $values[] = ((int) $item_type);
        }

        if($link_url !== null) {
            $arg[] = 'link_url';
            $values[] = $this->da->quoteSmart($link_url);
        }

        if($wiki_page !== null) {
            $arg[] = 'wiki_page';
            $values[] = $this->da->quoteSmart($wiki_page);
        }

        if($file_is_embedded !== null) {
            $arg[] = 'file_is_embedded';
            $values[] = ((int) $file_is_embedded);
        }

        $sql = 'INSERT INTO plugin_docman_item'
            .'('.implode(', ', $arg).')'
            .' VALUES ('.implode(', ', $values).')';
        return $this->_createAndReturnId($sql, $update_date);
    }
    function createFromRow($row) {
        $row['create_date'] = $row['update_date'] = time();
        $arg    = array();
        $values = array();
        $cols   = array('parent_id', 'group_id', 'title', 'description', 'create_date', 'update_date', 'user_id', 'status', 'obsolescence_date', 'rank', 'item_type', 'link_url', 'wiki_page', 'file_is_embedded');
        foreach ($row as $key => $value) {
            if (in_array($key, $cols)) {
                $arg[]    = $key;
                $values[] = $this->da->quoteSmart($value);
            }
        }
        if (count($arg)) {
            $sql = 'INSERT INTO plugin_docman_item '
                .'('.implode(', ', $arg).')'
                .' VALUES ('.implode(', ', $values).')';
            return $this->_createAndReturnId($sql, $row['create_date']);
        } else {
            return false;
        }
    }
    function _createAndReturnId($sql, $date) {
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar = $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
                if ($inserted) {
                    $this->_updateUpdateDateOfParent($row['id']);
                }
            } else {
                $inserted = $dar->isError();
            }
        }
        return $inserted;
    }
    /**
     * Update a row in the table plugin_docman_item 
     *
     * @return true if there is no error
     */
    function updateById($item_id, $parent_id=null, $group_id=null, $title=null,
                    $description=null, $create_date=null, $update_date=null, 
                    $user_id=null, $rank=null, $item_type=null, $link_url=null,
                    $wiki_page=null, $file_is_embedded=null) {       
       
        $argArray = array();

        if($parent_id !== null) {
            $argArray[] = 'parent_id='.((int) $parent_id);
        }

        if($group_id !== null) {
            $argArray[] = 'group_id='.((int) $group_id);
        }

        if($title !== null) {
            $argArray[] = 'title='.$this->da->quoteSmart($title);
        }

        if($description !== null) {
            $argArray[] = 'description='.$this->da->quoteSmart($description);
        }

        if($create_date !== null) {
            $argArray[] = 'create_date='.((int) $create_date);
        }

        if($update_date !== null) {
            $argArray[] = 'update_date='.((int) $update_date);
        }

        if($user_id !== null) {
            $argArray[] = 'user_id='.((int) $user_id);
        }

        if($rank !== null) {
            $argArray[] = 'rank='.((int) $rank);
        }

        if($item_type !== null) {
            $argArray[] = 'item_type='.((int) $item_type);
        }

        if($link_url !== null) {
            $argArray[] = 'link_url='.$this->da->quoteSmart($link_url);
        }

        if($wiki_page !== null) {
            $argArray[] = 'wiki_page='.$this->da->quoteSmart($wiki_page);
        }

        if($file_is_embedded !== null) {
            $argArray[] = 'file_is_embedded='.((int) $file_is_embedded);
        }
        
        $sql = 'UPDATE plugin_docman_item'
            .' SET '.implode(', ', $argArray)
            .' WHERE item_id='.((int) $item_id);

        $inserted = $this->update($sql);
        if ($inserted) {
            $this->_updateUpdateDateOfParent($this->da->quoteSmart($item_id));
        }
        return $inserted;
    }

    function updateFromRow($row) {
        $updated = false;
        $id = false;
        if (isset($row['id'])) {
            $id = $row['id'];
        } else if (isset($row['item_id'])) {
            $id = $row['item_id'];
        }
        if ($id) {
            $dar = $this->searchById($id);
            if (!$dar->isError() && $dar->valid()) {
                $current =& $dar->current();
                $set_array = array();
                foreach($row as $key => $value) {
                    if ($key != 'id' && $value != $current[$key]) {
                        $set_array[] = $key .' = '. $this->da->quoteSmart($value);
                    }
                }
                if (count($set_array)) {
                    $set_array[] = 'update_date = '. $this->da->quoteSmart(time());
                    $sql = 'UPDATE plugin_docman_item'
                        .' SET '.implode(' , ', $set_array)
                        .' WHERE item_id='. $this->da->quoteSmart($id);
                    $updated = $this->update($sql);
                    if ($updated) {
                        $this->_updateUpdateDateOfParent($this->da->quoteSmart($id));
                    }
                }
            }
        }
        return $updated;
    }
    function _updateUpdateDateOfParent($item_id_quoted) {
        $sql = 'SELECT parent_id, update_date FROM plugin_docman_item WHERE item_id = '. $item_id_quoted;
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->valid()) {
            $item = $dar->current();
            $sql = 'UPDATE plugin_docman_item SET update_date = '. $item['update_date'] .' WHERE item_id = '. $item['parent_id'];
            $this->update($sql);
        }
    }
    /**
     * Delete entry that match $item_id in plugin_docman_item
     *
     * @param $item_id int
     * @return true if there is no error
     */
    function delete($item_id) {
        $sql = sprintf("DELETE FROM plugin_docman_item WHERE item_id=%d",
                       $item_id);

        $deleted = $this->update($sql);
        
        return $deleted;
    }
    
    /**
    * @return boolean
    */
    function isItemTheOnlyChildOfRoot($group_id, $item_id) {
        $sql = sprintf(' SELECT b.item_id '.
                       ' FROM plugin_docman_item AS a '.
                       '      INNER JOIN plugin_docman_item AS b '.
                       '      ON ( a.item_id = b.parent_id ) '.
                       ' WHERE a.parent_id = 0 '.
                       '   AND b.group_id = %s '.
                       '   AND b.item_id <> %s '.
                       ' LIMIT 1 ',
            $this->da->quoteSmart($group_id),
            $this->da->quoteSmart($item_id)
        );
        $dar = $this->retrieve($sql);
        return !$dar->valid();
    }

    /**
     * This function intend to reorganize items under $parentId
     *
     * This function only affect rank parameter of sibling bellow $parentId.
     * -> It doesn't move item itself except for 'down' and 'up'. These 2
     * special move (that require $item_id param) change ranking of the item
     * to move up or down.
     */
    function _changeSiblingRanking($parentId, $ordering, $item_id = false) {
        $rank = 0;
        switch ($ordering) {
            case 'beginning':
            case 'end':
                $_select = $ordering == 'end' ? 'MAX(rank)+1' : 'MIN(rank)-1';
                $sql = sprintf('SELECT %s AS rank'.
                               ' FROM plugin_docman_item'.
                               ' WHERE parent_id = %d',
                               $_select,
                               $parentId);
                $dar = $this->retrieve($sql);
                if ($dar && $dar->valid()) {
                    $row = $dar->current();
                    $rank = $row['rank'];
                }
                break;
            case 'down':
            case 'up':
                if($item_id !== false) {
                    if ($ordering == 'down') {
                        $op    = '>';
                        $order = 'ASC';
                    } else {
                        $op    = '<';
                        $order = 'DESC';
                    }
                    $sql = sprintf('SELECT i1.item_id as item_id, i1.rank as rank'.
                                   ' FROM plugin_docman_item i1'.
                                   '  INNER JOIN plugin_docman_item i2 USING(parent_id)'.
                                   ' WHERE i2.item_id = %d'.
                                   '  AND i1.rank %s i2.rank'.
                                   ' ORDER BY i1.rank %s'.
                                   ' LIMIT 1', 
                                   $item_id,
                                   $op,
                                   $order);
                    $dar = $this->retrieve($sql);
                    if ($dar && $dar->valid()) {
                        $row = $dar->current();
                        
                        $sql = sprintf('UPDATE plugin_docman_item i1, plugin_docman_item i2'.
                                       ' SET i1.rank = i2.rank, i2.rank = %d'.
                                       ' WHERE i1.item_id = %d '.
                                       '  AND i2.item_id = %d',
                                       $row['rank'],
                                       $row['item_id'],
                                       $item_id);
                        $res = $this->update($sql);
                        //$can_update = false;
                        // Message for setNewParent function
                        $rank = -1;
                    }
                }
                break;
            default:
                $rank = $ordering?$ordering:0;
                $sql = sprintf('UPDATE plugin_docman_item'.
                               ' SET rank = rank + 1 '.
                               ' WHERE  parent_id = %d '.
                               '  AND rank >= %d',
                               $parentId,
                               $rank);
                $this->update($sql);
                break;
        }

        return $rank;
    }

    function setNewParent($item_id, $new_parent_id, $ordering) {
        $can_update = true;

        $rank = $this->_changeSiblingRanking($new_parent_id, $ordering, $item_id);
        if($ordering == 'down' || $ordering == 'up') {
            $can_update = ($rank == -1) ? false : true;
        }

        if ($can_update) {
            $sql = sprintf('UPDATE plugin_docman_item SET parent_id = %s, rank = %s '.
                ' WHERE  item_id = %s ',
                $this->da->quoteSmart($new_parent_id),
                $this->da->quoteSmart($rank),
                $this->da->quoteSmart($item_id)
            );
            $res = $this->update($sql);
        }
        return $res;
    }
    
    function searchByParentsId($parents) {
        $sql = sprintf('SELECT * FROM plugin_docman_item WHERE parent_id IN (%s) AND delete_date IS NULL ORDER BY rank',
            implode(', ', $parents)
        );
        return $this->retrieve($sql);
    }
    
    function searchRootIdForGroupId($group_id) {
        $sql = sprintf('SELECT item_id FROM plugin_docman_item WHERE parent_id = 0 '.
            ' AND group_id = %s ',
            $this->da->quoteSmart($group_id)
        );
        $dar = $this->retrieve($sql);
        $id = false;
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $id = $row['item_id'];
        }
        return $id;
    }

    function searchSubFolders($group_id, $parentIds = array()) {        
        if(is_array($parentIds) && count($parentIds) > 0) {           
            $sql = sprintf('SELECT item_id'
                           .' FROM plugin_docman_item'
                           .' WHERE group_id = %d'
                           .' AND parent_id IN (%s)'
                           .' AND item_type = %d'
                           , $group_id
                           , implode(',', $parentIds)
                           , PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
            return $this->retrieve($sql);
        }
        else {
            return null;
        }
    }

    function searchCurrentWikiVersion($groupId, $pagename) {
        $version = null;
        $sql = sprintf('SELECT MAX(version) AS version'.
                       ' FROM wiki_page '.
                       '  INNER JOIN wiki_version USING(id)'.
                       ' WHERE group_id = %d'.
                       ' AND pagename = %s',
                       $groupId, $this->da->quoteSmart($pagename));
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            $version = $row['version'];
        }
        return $version;
    }
}

?>
