<?php

class BencodeList {
    private $list;
    public function __construct() {
        $this->list = array();
    }

    public function add($o) {
        $this->list []= $o;
    }

    public function can_add_container() {
        return true;
    }

    public function is_ready() {
        return true;
    }

    public function get_data() {
        return $this->list;
    }
}

class BencodeDict {
    private $dict;
    private $key;
    public function __construct() {
        $this->dict = array();
        $this->key = null;
    }

    public function add($o) {
        if (is_null($this->key)) {
            $this->key = $o;
        } else {
            $this->dict[$this->key] = $o;
            $this->key = null;
        }
    }

    public function can_add_container() {
        return is_null($this->key);
    }

    public function is_ready() {
        return is_null($this->key);
    }

    public function get_data() {
        return $this->dict;
    }
}

function bdecode($data) {
    $stack = new SplStack();
    $stack->push(new BencodeList());

    $len = strlen($data);
    $at = 0;
    while ($at < $len) {
        switch ($data[$at]) {
            case '0':
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            case '6':
            case '7':
            case '8':
            case '9':

                $n = 0;
                for ($i=0; $i<11 && $at<$len && ord('0') <= ord($data[$at]) && ord($data[$at]) <= ord('9'); $i++) {
                    $n = $n * 10 + ord($data[$at++]) - ord('0');
                }

                if ($i===11 || $at >= $len || $data[$at] !== ':') {
                    return false;
                }

                $at++;
                if ($at + $n > $len) {
                    return false;
                }

                $str = substr($data, $at, $n);
                $at += $n;

                $stack->top()->add($str);

                break;
            case 'i':

                $at++;

                $n = 0;
                $sign = 1;
                if ($at < $len && $data[$at] === '-') {
                    $sign = -1;
                    $at++;
                }

                for ($i=0; $i<15 && $at<$len && ord('0') <= ord($data[$at]) && ord($data[$at]) <= ord('9'); $i++) {
                    $n = $n * 10  + ord($data[$at++]) - ord('0');
                }

                if ($i === 15 || $at >= $len || $data[$at] !== 'e') {
                    return false;
                }

                $at++;
                $stack->top()->add($n * $sign);

                break;
            case 'l':
                $stack->push(new BencodeList());
                $at++;
                break;
            case 'd':
                $stack->push(new BencodeDict());
                $at++;
                break;
            case 'e':

                $cur = $stack->pop();
                if ($stack->isEmpty() || !$cur->can_add_container() || !$cur->is_ready()) {
                    return false;
                }

                $stack->top()->add($cur->get_data());
                $at++;

                break;
            default:
                return false;
        }
    }

    if ($at !== $len) {
        return false;
    }

    $res = $stack->pop();
    if (!$stack->isEmpty() || !$res->is_ready()) {
        return false;
    }

    $res = $res->get_data();
    if (count($res) !== 1) {
        return false;
    }

    return $res[0];
}

function is_assoc($arr) {
    for ($k = 0, reset($arr); $k === key($arr); next($arr)) $k++;
    return !is_null(key($arr));
}

function bencode($data) {
    $res = '';
    $stack = new SplStack();
    $stack->push(array('x', $data));
    $rev = new SplStack();
    while (!$stack->isEmpty()) {
        $cur = $stack->pop();
        switch ($cur[0]) {
            case 'x':
                if (is_array($cur[1])) {
                    $stack->push(array('e'));
                    if (is_assoc($cur[1])) {
                        $res .= 'd';

                        foreach ($cur[1] as $key => $val) {
                            $rev->push($key);
                            $rev->push($val);
                        }

                        while (!$rev->isEmpty()) {
                            $stack->push(array('x', $rev->pop()));
                        }

                    } else {
                        $res .= 'l';
                        for ($i=count($cur[1])-1; $i>=0; $i--) {
                            $stack->push(array('x', $cur[1][$i]));
                        }
                    }
                } else if (is_int($cur[1])) {
                    $res .= 'i'. $cur[1] . 'e';
                } else if (is_string($cur[1])) {
                    $res .= strlen($cur[1]) . ':' . $cur[1];
                } else {
                    return false;
                }

                break;
            case 'e':
                $res .= 'e';
                break;
            default:
                return false;
        }
    }

    return $res;
}

