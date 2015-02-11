    <?php

/**
 * Data access wrapper for "orders" table.
 *
 * @author jim
 */
class Orders extends MY_Model {

    // constructor
    function __construct() {
        parent::__construct('orders', 'num');
    }

    // add an item to an order
    function add_item($num, $code) {
        $this->db->from('orderitems');
        $this->db->where('order', $num);
        $this->db->where('item', $code);
        $query = $this->db->get();
        $query = $query->result_array();

        if(empty($query)){

            $newOrderItem = array( "order" => $num, "item" => $code, "quantity" => 1);
            $this->db->insert('orderitems', $newOrderItem);
        }else{
            $updateOrder = array ("order" => $num, "item" => $code, "quantity" => $query[0]['quantity'] + 1);
            $this->db->where('order', $num);
            $this->db->where('item', $code);
            $this->db->update('orderitems', $updateOrder);
        }


        $newTotal = array("total" => $this->total($num) );
        $this->db->where('num', $num);
        $this->db->update('orders', $newTotal);
    }

    // calculate the total for an order
    function total($num) {
        $this->db->from('orderitems');
        $this->db->join('menu', 'menu.code = orderitems.item');
        $this->db->where('order', $num);

        $query = $this->db->get();
        $query = $query->result_array();
        //var_dump($query);

        $total = 0;
        foreach($query as $item){
            $total += $item['quantity'] * $item['price'];
        }

        //$this->data['total'] = money_format(LC_MONETARY, $total);

        return $total;
    }

    // retrieve the details for an order
    function details($num) {
        $CI = &get_instance();
        $CI->load->model('orderitems');
        $orderItems = $this->orderitems->group($num);

        foreach($orderItems as $item){
            $this->db->from('menu');
            $this->db->where('code', $item->item);
            $query = $this->db->get()->result_array();
            $item->name = $query[0]['name'];
        }

        return $orderItems;
        
    }

    // cancel an order
    function flush($num) {
        $this->db->from('orderitems');
        $this->db->where('order', $num);
        $query = $this->db->get();
        $query = $query->result_array();

        $CI = &get_instance();
        $CI->load->model('orderitems');

        foreach($query as $item){
            $this->orderitems->delete($num, $item['item']);
        }


    }

    // validate an order
    // it must have at least one item from each category
    function validate($num) {
        $this->db->from('orderitems');
        $this->db->where('order', $num);
        $query = $this->db->get();
        $query = $query->result_array();

        $isMeal = false;
        $isDrink = false;
        $isSweet = false;
        foreach($query as $item){
            $this->db->from('menu');
            $this->db->where('code', $item['item']);
            $menuItem = $this->db->get();
            $menuItem = $menuItem->result_array();


            if($menuItem[0]['category'] == "m"){
                $isMeal = true;
            }else if($menuItem[0]['category'] == "d"){
                $isDrink = true;
            }elseif($menuItem[0]['category'] == "s"){
                $isSweet = true;
            }
        }

        return ($isMeal && $isDrink && $isSweet);
    }

}
