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
        $CI = &get_instance();
        $CI->load->model('orderitems');
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
    // select order items from orderitem where menu code is matching from menu table and put this in array
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
        return $result; // found total for order
    }

    // retrieve the details for an order
    // details include all item make a group of similar code number 
    function details($num) {
        $CI = &get_instance();
        $CI->load->model('orderitems');
        $orderItems = $this->orderitems->group($num);
            // select item where menu table where code is matching item and return them
        foreach($orderItems as $item){
            $this->db->from('menu');
            $this->db->where('code', $item->item);
            $query = $this->db->get()->result_array();
            $item->name = $query[0]['name'];
        }
        return $orderItems;        
    }

    // cancel an order // delete item if not proceeded  
    function flush($num) {
// if the order exists, delete all related orderitems
if($this->exists($num))
{
$items = $this->orderitems->delete_some($num);
}
}

    // validate an order
    // it must have at least one item from each category
    // return true if  all categories combination true. 
    // assume at starting all values are false 
    function validate($num) {
        $boolCatm = false;
        $boolCatd = false;
        $boolCats = false;
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
                $boolCatm = true;
            }else if($menuItem[0]['category'] == "d"){
                $boolCatd = true;
            }elseif($menuItem[0]['category'] == "s"){
                $boolCats = true;
            }
        }
         // proceed only of all are true
        return ($boolCatm && $boolCatd && $boolCats);
    }

}

