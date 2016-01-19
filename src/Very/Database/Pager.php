<?php namespace Very\Database;

class Pager {
    public $total_count = 0;
    public $per_page;
    public $curr_page;
    public $total_page = null;
    public $nearer_page = 10;

    /**
     * @var $data_provider \Very\Database\PDOConnection;
     */
    private $data_provider;

    public function __construct($dao, $curr_page = 1, $per_page = 10) {
        $this->data_provider = $dao;
        $this->per_page      = $per_page;
        $this->curr_page     = $curr_page;
        return $this;
    }

    public function ct($cmd, $params = array()) {
        if (is_numeric($cmd))
            $this->setTotalCount($cmd);
        else
            $this->setTotalCount($this->getCount($cmd, $params));
        return $this;
    }

    public function rs($cmd, $params = array()) {
        if (is_string($cmd)) {
            $rs = $this->getResult($cmd, $this->getPerPage(), ($this->getCurrPage() - 1) * $this->getPerPage(), $params);
        } else {
            $rs = $cmd;
        }

        return array('pager' => $this, 'rs' => $rs);
    }

    public function getCount($count_sql, $params = array()) {
        return $this->data_provider['ct']->getOne($count_sql, $params);
    }

    public function getResult($select_sql, $per_page, $offset, $params = array()) {
        return $this->data_provider['select']->selectLimit($select_sql, $per_page, $offset, $params);
    }

    public function getTotalCount() {
        return $this->total_count;
    }

    public function setTotalCount($total_count) {
        $this->total_count = $total_count;
        $this->setTotalPage(ceil($total_count / $this->getPerPage()));
        return $this;
    }

    public function setNearerPage($offset) {
        $this->nearer_page = $offset;
        return $this;
    }

    public function getNearerPage() {
        return $this->nearer_page;
    }

    public function getPerPage() {
        return $this->per_page;
    }

    public function setPerPage($per_page) {
        $this->per_page = $per_page;
    }

    public function getCurrPage() {
        return $this->curr_page;
    }

    public function setCurrPage($curr_page) {
        $this->curr_page = $curr_page;
    }

    public function getTotalPage() {
        return $this->total_page;
    }

    public function setTotalPage($total_page) {
        $this->total_page = $total_page;
    }

    public function hasFirstPage() {
        return $this->getCurrPage() != 1;
    }

    public function hasLastPage() {
        return $this->getCurrPage() != $this->getTotalPage();
    }

    public function hasPrePage() {
        return $this->getCurrPage() > 1;
    }

    public function hasNextPage() {
        return $this->getCurrPage() < $this->getTotalPage();
    }

    public function getNearerPages() {
        $min_page = $this->getNearerPage() > $this->getTotalPage() ? 1 : max(1, $this->getCurrPage() - ceil($this->getNearerPage() / 2));
        $max_page = $this->getNearerPage() > $this->getTotalPage() ? $this->getTotalPage() : min($this->getTotalPage(), $min_page + $this->getNearerPage() - 1);
        if ($this->getTotalPage() > $this->getNearerPage()) {
            $min_page = min($min_page, $this->getTotalPage() - $this->getNearerPage() + 1);
        }

        if ($min_page <= $max_page)
            return range($min_page, $max_page);
        else
            return array();
    }
}
