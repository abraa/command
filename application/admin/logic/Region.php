<?php
/**
 * ====================================
 * ggzy
 * ====================================
 * Author: 1002571
 * Date: 2017/11/14 13:56
 * ====================================
 * File: Region.php
 * ====================================
 */

namespace app\admin\logic;

use app\admin\BaseLogic;

class Region extends BaseLogic
{
    public function format($data)
    {
        if (empty($this->data['type']) && !empty($data)) {
            foreach ($data as $key => &$row) {
                if ($row['have_children']) {
                    $row['state'] = 'closed';
                }
            }
        }
        return $data;
    }
}
