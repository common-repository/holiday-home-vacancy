<?php


class HHomeV_AppVacancy {


    public $date;
    public $bookingId = 0;
    public $status = false;
    //departure date needs to be selectable, can be first booking date of other booking
    public $to;

    protected $bookings = false;
    protected $reserved = false;
    protected $firstDay = false;
    protected $paid = false;
    


    public function __construct() {

        $this->date = wp_date("Y-m-d");
    }
    

    
    public function setDate($day, $month, $year) {
    
        if($day == "" or $month == "" or $year == "") return false; 
        
        $this->date = wp_date($year . "-" . $month . "-" . $day);
    }
    
    

    private function changeMonth($up_down) {
    
        return wp_date("Y-m-d", strtotime($up_down. " month", strtotime($this->date)));
    }

    public function returnHeaderInfo() {

        $month      = wp_date("n", strtotime($this->date));    
        $year       = wp_date("Y", strtotime($this->date));    
        $monthNames = $this->setMonthNames();

        $prevMonth = $this->changeMonth(-1); // in Y-m-d
        $nextMonth = $this->changeMonth(+1); // in Y-m-d

        return array($monthNames[$month], $year, $prevMonth, $nextMonth);   
    }
    

    
    public function showCalender($status = false, $to = false) {

        $this->status = $status;
        $this->to = $to;
    
        $month  = wp_date("n", strtotime($this->date));    
        $year   = wp_date("Y", strtotime($this->date));    

        global $wpdb;
        $entries = $wpdb->get_results("SELECT * FROM `{$wpdb->base_prefix}holiday_home_occupation`");

        $days = array('Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su');

        if ($entries) {
            foreach ($entries as $key => $entry) {

                if ($entry->key == 'mo') { $days[0] = $entry->value; }
                else if ($entry->key == 'tu') { $days[1] = $entry->value; }
                else if ($entry->key == 'we') { $days[2] = $entry->value; }
                else if ($entry->key == 'th') { $days[3] = $entry->value; }
                else if ($entry->key == 'fr') { $days[4] = $entry->value; }
                else if ($entry->key == 'sa') { $days[5] = $entry->value; }
                else if ($entry->key == 'su') { $days[6] = $entry->value; }
            }
        }
        
        $html = "<table align='center' cellspacing='4' cellpadding='0' class='table--calender'>";

        if ($this->status == 2) $html = "<table align='center' cellspacing='3' cellpadding='0' class='table--calender'>";
        
        $maxDays = wp_date("t", strtotime($this->date));
        
        for($day = 1; $day <= $maxDays; $day++) {
        
            $dayId = wp_date("w", strtotime($year ."-". $month ."-". $day));
            if (!$dayId) $dayId = 7;
            
            if($day == 1) { 
            
                $html .= "<tr>";
                    $html .= "<th>".$days[0]."</th>";
                    $html .= "<th>".$days[1]."</th>";
                    $html .= "<th>".$days[2]."</th>";
                    $html .= "<th>".$days[3]."</th>";
                    $html .= "<th>".$days[4]."</th>";
                    $html .= "<th>".$days[5]."</th>";
                    $html .= "<th>".$days[6]."</th></tr>";
                $html .= "<tr>";
                
                for($em = 1; $em < $dayId; $em++) $html .= "<td class='emptyTD'>&nbsp;</td>";
            
                $html .= $this->createTD($day, $year ."-". $month ."-". $day, $dayId);
            }   
            else $html .= $this->createTD($day, $year ."-". $month ."-". $day, $dayId);
            
            if ($dayId == 7 && $day != $maxDays) $html .= "</tr><tr>";
        }

        $leftOverEmpty = 7 - $dayId;

        for($i = 1; $i <= $leftOverEmpty; $i++) $html .= "<td class='emptyTD'><span>&nbsp;</span></td>";
        
        $html .= "</tr></table>";

        return $html;               
    }



    private function createTD($dayNo, $currentDate, $dayId) {
            
        $class = array();
        $this->bookingId = 0;

        if (wp_date("Y-n-j") == $currentDate)          $class[] = "today"; 
        if (strtotime(wp_date("Y-n-j")) > strtotime($currentDate)) $class[] = "past";

        
        if ($this->checkBooked($currentDate))       $class[] = "booked";
                                                
        if($dayId == 6 or $dayId == 7)              $class[] = "weekend";

        return "<td class='". esc_html(implode(" ", $class)) ."'><span>" . esc_html($dayNo) . "</span></td>"; 
        
    }   

    
    private function checkBooked($currentDate) {
        
        $bookings = $this->getBookings();

        if(empty($bookings)) return false;

        $time = strtotime($currentDate);

        foreach($bookings as $booking) {
             
            if($time >= strtotime($booking->from) && $time <= strtotime($booking->to)) { 
                
                $this->bookingId = $booking->id;
                return true;  
            }
        }   
        return false; 
    }


    public function getBookings() {
    
        $infos = query_posts('post_type=calendar_occupation&posts_per_page=-1'); 
        wp_reset_query();
                
        foreach ($infos as $key => &$info) {
            
            $info->from = get_post_meta( $info->ID, 'hhomev_from_location', true );
            $info->to = get_post_meta( $info->ID, 'hhomev_to_location', true );
            
        }
        return $infos;
    }
    
    
    
    public function setMonthNames() {

        global $wpdb;
        $entries = $wpdb->get_results("SELECT * FROM `{$wpdb->base_prefix}holiday_home_occupation`");
        
        $name = array();

        $name[1] = "January"; 
        $name[2] = "February"; 
        $name[3] = "March"; 
        $name[4] = "April";
        $name[5] = "May"; 
        $name[6] = "June"; 
        $name[7] = "July"; 
        $name[8] = "August";
        $name[9] = "September"; 
        $name[10] = "October"; 
        $name[11] = "November"; 
        $name[12] = "December";

        if ($entries) {
            foreach ($entries as $key => $entry) {

                if ($entry->key == 'jan') { $name[1] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'jan') { $name[1] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'feb') { $name[2] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'mar') { $name[3] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'apr') { $name[4] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'may') { $name[5] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'jun') { $name[6] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'jul') { $name[7] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'aug') { $name[8] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'sep') { $name[9] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'oct') { $name[10] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'nov') { $name[11] = esc_html(sanitize_text_field($entry->value)); }
                else if ($entry->key == 'dec') { $name[12] = esc_html(sanitize_text_field($entry->value)); }
            }
        }

        return $name;
    }

}