<?php
/**
 * Created by PhpStorm.
 * User: ofaruk
 * Date: 2018-01-06
 * Time: 1:24 AM
 */

namespace Omar\Models;

use Omar\Pagination;
use \RedBeanPHP\R;
use Symfony\Component\BrowserKit\Request;

class Todo extends \RedBeanPHP\SimpleModel
{
    protected $table = 'todos';

    public function getAll($userId, $limit, $page) {
        $result = [];
        $links = 3;
        $query      = "SELECT * from ".$this->table." where user_id = $userId";
        $count =R::getRow("select count(*) as num from ".$this->table." Where user_id=?",[$userId]);
        $total = $count["num"];
        $paginator  = new Pagination($query, $total);

        $todos = $paginator->getData( $limit, $page );
        $link = $paginator->createLinks( $links, 'pagination pagination-sm' );

        $result = ['todos' => $todos, 'link' => $link];

        return $result;
    }

    public function delete($id){
        $query = R::load($this->table, intval($id));
        $result = R::trash($query);
        return $result;
    }

    public function add($userId, $description) {
        //R::debug(true);
        $todo = R::dispense($this->table);
        $todo->user_id = $userId;
        $todo->description = $description;
        $id = R::store($todo);

        return $id;
    }

    public function update($id, $userId) {
        $todo = R::dispense($this->table);
        $todo->id = $id;
        $todo->user_id = $userId;
        $todo->complete = 1;
        $result = R::store($todo);

        return $result;
    }

}