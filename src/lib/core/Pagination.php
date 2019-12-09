<?php 
/**
 * Pagination class
 * digunakan untuk paging
 * 
 * @category Core Class
 * @author   Lukmanul Hakim - Bukulokomedia 
 * 
 */
class Pagination
{
	/**
	 * Method untuk mengecek posisi data
	 * dan halaman/order
	 * @param int $limit
	 * @return number
	 */
	public function getPosition($limit)
	{
		if (empty($_GET['order']))
		{
			$position = 0;
			$_GET['order'] = 1;
		}
		else {
			$position = ($_GET['order']-1) * $limit;
		}

		return $position;
	}


	/**
	 *
	 * Method untuk menghitung total
	 * halaman/order
	 * @param int $totalData
	 * @param int $limit
	 * @return number
	 */
	public function totalPage($totalData, $limit)
	{
		$totalPage = ceil($totalData/$limit);

		return $totalPage;
	}


	// method untuk link halaman 1,2,3 (back end website)
	public function navPage($activePage, $totalPage)
	{
		$page_link = '';

		// Link ke halaman pertama (Awal) dan sebelumnya (Sebelumnya)
		if ($activePage > 1)
		{
			$Sebelumnya = $activePage-1;
			$page_link .= "<span class=disabled><a href=$_SERVER[PHP_SELF]?module=$_GET[module]&order=".abs((int)1).">Awal</a></span>
			<span class=disabled><a href=$_SERVER[PHP_SELF]?module=$_GET[module]&order=".abs((int)$Sebelumnya).">Sebelumnya</a></span> ";
				
		}
		else 
		{
				
			$page_link .= "<span class=disabled> Awal</span>";
		}


		//Page link number 1, 2, 3, ... dst
		$number = ($activePage > 3 ? " ... " : " ");

		for ($i=$activePage-2; $i<$activePage; $i++)
		{
			if ( $i < 1 )
				continue;
			$number .= "<span class=disabled><a href=$_SERVER[PHP_SELF]?module=$_GET[module]&order=". abs((int)$i).">$i</a></span> ";
		}

		//active page
		$number .= " <span class=current> $activePage </span>  ";
		 
		for ($i=$activePage+1; $i<($activePage + 3); $i++)
		{
			if ( $i > $totalPage )
			{
				break;
			}
				
			$number .= "<span class=disabled><a href=$_SERVER[PHP_SELF]?module=$_GET[module]&order=".abs((int)$i).">$i</a></span>  ";
		}
			
		$number .= ($activePage+2< $totalPage ? " ... <span class=disabled><a href=$_SERVER[PHP_SELF]?module=$_GET[module]&order=".abs((int)$totalPage).">$totalPage</a> </span>  " : " ");
			
		$page_link .= "$number";
			
		//Link ke halaman berikutnya (Next) dan halaman terakhir(Terakhir)
		if ($activePage < $totalPage)
		{
			$berikutnya= $activePage + 1;

			$page_link .= " <span class=disabled><a href=$_SERVER[PHP_SELF]?module=$_GET[module]&order=$berikutnya>Berikutnya</a></span>
			<span class=disabled><a href=$_SERVER[PHP_SELF]?module=$_GET[module]&order=$totalPage>Terakhir</a></span> ";
		}
		elseif ( $activePage > $totalPage)
		{
			$page_link .= $this->setErrorPage();
		}
		else 
		{

			$page_link .= "<span class=disabled>Terakhir</span> ";
		}
			
		return $page_link;
	}
	
	protected function setErrorPage()
	{
		$page_error = '';
		
		$page_error .= '<div id="page-wrapper">';
		$page_error .= '<div class="row">';
		$page_error .= '<div class="col-lg-12">';
		$page_error .= '<div class="alert alert-danger alert-dismissable">
				        <h2>404,Page Not Found!</h2>
		               </div>'; // alert error
		$page_error .= '</div>'; // end of col-lg12
		$page_error .= '</div>'; // end of .row
		$page_error .= '</div>'; // end of #page-wrapper
		
		$page_error .= '<script type="text/javascript">function leave() {  window.location = "javascript:history.go(-1)";} setTimeout("leave()", 3640);</script>';
		
		return  $page_error;

	}
	
}