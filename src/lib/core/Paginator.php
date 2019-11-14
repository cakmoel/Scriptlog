<?php 
/**
 * PHP Pagination Class
 *
 * @package  SCRIPTLOG/LIB/CORE/Paginator
 * @category Core Class
 * @author   David Carr - dave@daveismyname.com - http://www.daveismyname.com
 * @version  1.0
 * @since    October 20, 2012
 * 
 */
class Paginator 
{

	/**
	 * set the number of items per page.
	 *
	 * @var integer $_perpage
	 */
	private $_perPage;

	/**
	 * set get parameter for fetching the page number
	 *
	 * @var string
	 */
	private $_instance;

	/**
	 * sets the page number.
	 *
	 * @var integer $_page
	 */
	private $_page;

	/**
	 * set the limit for the data source
	 *
	 * @var string
	 */
	private $_limit;

	/**
	 * set the total number of records/items.
	 *
	 * @var integer $_totalRows
	 */
	private $_totalRows = 0;
	
	/**
	 * Errors
	 * @var string
	 */
	private $_errors;
	
	/**
	 * sanitize
	 * @var string
	 */
	private $_sanitize;

	/**
	 *  __construct
	 *
	 *  pass values when class is istantiated
	 *
	 * @param integer $_perPage  sets the number of iteems per page
	 * @param integer $_instance sets the instance for the GET parameter
	 */
	public function __construct($perPage, $instance)
	{
	   if (is_numeric($perPage)) {
	       
	       $this->_instance = $instance;
	       $this->_perPage = $perPage;
	       $this->set_instance();
	       
	   }
		
	}

	/**
	 * get_start
	 *
	 * creates the starting point for limiting the dataset
	 * @return integer
	 */
	private function get_start()
	{
	    return abs((int)($this->_page * $this->_perPage) - $this->_perPage);
	}

	/**
	 * set_instance
	 *
	 * sets the instance parameter, if numeric value is 0 then set to 1
	 *
	 * @var integer
	 */
	private function set_instance()
	{
	  $requestInstance = filter_input(INPUT_GET, $this->_instance, FILTER_SANITIZE_NUMBER_INT); 
	  $this->_page = (int)(!isset($requestInstance) ? 1 : $requestInstance);
	  $this->_page = ($this->_page == 0 ? 1 : $this->_page);
	    
	}

	/**
	 * set_total
	 *
	 * collect a numberic value and assigns it to the totalRows
	 *
	 * @var integer
	 */
	public function set_total($_totalRows){
		$this->_totalRows = $_totalRows;
	}

	/**
	 * get_limit
	 *
	 * returns the limit for the data source, calling the get_start method and passing in the number of items perp page
	 *
	 * @return string
	 */
	public function get_limit(Sanitize $sanitize)
	{
	  $this->_sanitize = $sanitize;
	  $position = $this->_sanitize->sanitasi((int)$this->get_start(), 'sql');
	  return "LIMIT ".$position.",$this->_perPage";
	}

	/**
	 * page_links
	 *
	 * create the html links for navigating through the dataset
	 *
	 * @var string $path optionally set the path for the link
	 * @var string $ext optionally pass in extra parameters to the GET
	 * @return string returns the html menu
	 */
 public function page_links(Sanitize $sanitize, $path = '?', $ext = null)
 {
	   $this->_sanitize = $sanitize;
	   
	   $adjacents = "2";
	   $prev =  (int)$this->_page - 1;
	   $next =  (int)$this->_page + 1;
	   $lastpage = ceil($this->_totalRows/$this->_perPage);
	   $lpm1 = $lastpage - 1;

		$pagination = null;
			
	try {
		   
		    if ($this->_page > $this->_totalRows) {
		       throw new Exception("Error 404!");
		    }
		    
		    if($lastpage > 1) {
		        
		        $pagination .= '<ul class="pagination">';
		        
		        if ($this->_page > 1)
		            $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($prev, 'sql')}"."$ext'><i class='fa fa-angle-left'></i></a></li>";
		        /*else
		            $pagination.= '<li><a href="#"><i class="fa fa-angle-double-left"></i></a></li>';
		            */
		                
		        if ($lastpage < 7 + ($adjacents * 2)) {
		            
		            for ($counter = 1; $counter <= $lastpage; $counter++) {
		                 
		               if ($counter == $this->_page)
		                   $pagination.= "<li class='active'><a class='btn btn-outline-secondary' href='#'>$counter</a></li>";
		               else
		                   $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($counter, 'sql')}"."$ext'>$counter</a></li>";
		            
		            }
		              
		         } elseif($lastpage > 5 + ($adjacents * 2)) {
		             
		          if($this->_page < 1 + ($adjacents * 2)) {
		                   
		              for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
		                       
		                       if ($counter == $this->_page)
		                            $pagination.= "<li class='active'><a href='#'>$counter</a></li>";
		                       else
		                           $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($counter, 'sql')}"."$ext'>$counter</a></li>";
		                   
		               }
		                        
		                   $pagination.= "...";
		                        
		                   $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($lpm1, 'sql')}"."$ext'>$lpm1</a></li>";
		                        
		                   $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($lastpage, 'sql')}"."$ext'>$lastpage</a></li>";
		                    
		         } elseif($lastpage - ($adjacents * 2) > $this->_page && $this->_page > ($adjacents * 2)) {
		                   
		             $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance=1"."$ext'>1</a></li>";
		             $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance=2"."$ext'>2</a></li>";
		               $pagination.= "...";
		                        
		             for ($counter = $this->_page - $adjacents; $counter <= $this->_page + $adjacents; $counter++) {
		                 
		                  if ($counter == $this->_page)
		                      
		                      $pagination.= "<li class='active'><a class='btn btn-outline-secondary' href='#'>$counter</a></li>";
		                  else
		                      $pagination.= "<li><a href='".$path."$this->_instance={$this->_sanitize->sanitasi($counter, 'sql')}"."$ext'>$counter</a></li>";
		             }
		                        
		              $pagination.= "..";
		              $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($lpm1, 'sql')}"."$ext'>$lpm1</a></li>";
		              $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($lastpage, 'sql')}"."$ext'>$lastpage</a></li>";
		                    
		         } else {
		             
		             $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance=1"."$ext'>1</a></li>";
		             $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance=2"."$ext'>2</a></li>";
		         $pagination.= "..";
		                        
		           for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
		             
		            if ($counter == $this->_page)
		                 $pagination.= "<li><a class='btn btn-outline-secondary' href='#'>$counter</a></li>";
		            else
		                $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($counter, 'sql')}"."$ext'>$counter</a></li>";
		           }
		           
		        }
		                
		      }
		                
		     if ($this->_page < $counter - 1)
		         $pagination.= "<li><a class='btn btn-outline-secondary' href='".$path."$this->_instance={$this->_sanitize->sanitasi($next, 'sql')}"."$ext'><i class='fa fa-angle-right'></i></a></li>";
		      /*else
		         $pagination.= "<li><a href='#'><i class='fa fa-angle-double-right'></i></a></li>";
		       */
		                    
		     $pagination.= "</ul>\n";
		                 
		  }
		    
		return $pagination;
		    
     } catch (Exception $e) {
		   
		 $this->_errors = LogError::newMessage($e);
		 $this->_errors = LogError::customErrorMessage();
		   
		}		
 }
	
}