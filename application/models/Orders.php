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
    // update database with new order combination
    function add_item($num, $code) {
    
       $this->db->from('orderitems');
        $this->db->where('order', $num);
        $this->db->where('item', $code);
        $data = $this->db->get();
        $data = $data->result_array();

        if($data == null){

            $newOrderCombination = array( "order" => $num, "item" => $code, "quantity" => 1);
            $this->db->insert('orderitems', $newOrderCombination);
        }else{
            $updateOrderTable = array ("order" => $num, "item" => $code, "quantity" => $data[0]['quantity'] + 1);
            $this->db->where('order', $num);
            $this->db->where('item', $code);
            $this->db->update('orderitems', $updateOrderTable);
        }
        $newTotal = array("total" => $this->total($num) );
        $this->db->where('num', $num);
        $this->db->update('orders', $newTotal);
    }

    // calculate the total for an order
    // if you are proceeding with order
    function total($num) {
        $this->db->from('orderitems');
        $this->db->join('menu', 'menu.code = orderitems.item');
        $this->db->where('order', $num);
        $items = $this->db->get();
        $items = $items->result_array();
        $result = 0;
        foreach($items as $item){
            $result += $item['quantity'] * $item['price'];
        } 

        return $result;
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
    // return true if  all categories combination true.
    function validate($num) {
        $boolMeal = false;
        $boolDrink = false;
        $boolSweet = false;
        $this->db->from('orderitems');
        $this->db->where('order', $num);
        $data = $this->db->get();
        $data = $data->result_array();
        
        foreach($data as $item){
            $this->db->from('menu');
            $this->db->where('code', $item['item']);
            $menuItem = $this->db->get();
            $menuItem = $menuItem->result_array();
            
            if($menuItem[0]['category'] == "m"){
                $boolMeal = true;
            }else if($menuItem[0]['category'] == "d"){
                $boolDrink = true;
            }elseif($menuItem[0]['category'] == "s"){
                $boolSweet = true;
            }
        }

        return ($boolMeal && $boolDrink && $boolSweet);
    }

}
