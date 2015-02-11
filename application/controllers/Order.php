<?php

/**
 * Order handler
 * 
 * Implement the different order handling usecases.
 * 
 * controllers/welcome.php
 *
 * ------------------------------------------------------------------------
 */
class Order extends Application {

    function __construct() {
        parent::__construct();
    }

    // start a new order
    function neworder() {
        //FIXME
        $order_num = $this->orders->highest() + 1;

        $newOrder = $this->orders->create();
        $newOrder->num = $order_num;
        $newOrder->date = date("Y-m-d H:i:s");
        $newOrder->status = 'a';

        $this->orders->add($newOrder);

        redirect('/order/display_menu/' . $order_num);
    }

    // add to an order
    function display_menu($order_num = null) {
        if ($order_num == null)
            redirect('/order/neworder');

        $order = $this->orders->get($order_num);

        $this->data['pagebody'] = 'show_menu';
        $this->data['order_num'] = $order_num;
        //FIXME

        $this->data['datetime'] = $order->date;
        $this->data['num'] = $order->num;
        $this->data['total'] = $order->total;

        // Make the columns
        $this->data['meals'] = $this->make_column('m', $order_num);
        $this->data['drinks'] = $this->make_column('d', $order_num);
        $this->data['sweets'] = $this->make_column('s', $order_num);
        $this->data['title'] = 'Order: #' . $order_num . ' || Total: $' . $order->total;

        $this->render();
    }

    // make a menu ordering column
    function make_column($category, $order_num) {
        //FIXME
        $column = $this->menu->some('category', $category);

        foreach($column as $item){
            $item->order_num = $order_num;
        }
        return $column;
    }

    // add an item to an order
    function add($order_num, $item) {
        //FIXME
        $this->orders->add_item($order_num, $item);

        redirect('/order/display_menu/' . $order_num);
    }

    // checkout
    function checkout($order_num) {
        $this->data['title'] = 'Checking Out';
        $this->data['pagebody'] = 'show_order';
        $this->data['order_num'] = $order_num;
        //FIXME

        $order = $this->orders->get($order_num);

        $this->data['total'] = $order->total;
        $this->data['items'] = $this->orders->details($order_num);

        if($this->orders->validate($order_num)){
            $this->data['okornot'] = "";
            $this->data['okornothref'] = "/order/proceed/" . $order_num;
        }else{
            $this->data['okornot'] = "disabled";
            $this->data['okornothref'] = "#";
        }

        $this->render();
    }

    // proceed with checkout
    function proceed($order_num) {
        //FIXME
        $setArray = array("status" => 'c');

        $this->db->from('orders');
        $this->db->where('num', $order_num);
        $this->db->update('orders', $setArray);

        redirect('/');
    }

    // cancel the order
    function cancel($order_num) {
        //FIXME
        $this->orders->flush($order_num);
        $setArray = array("status" => 'x');

        $this->db->from('orders');
        $this->db->where('num', $order_num);
        $this->db->update('orders', $setArray);
        redirect('/');
    }

}
