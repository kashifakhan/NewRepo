
<?php
session_start();
include("config.php");
global $conn,$stmt;
global $category_array,$array1,$wish_array;
$category_array=array();
$array1=array();
$wish_array=array();
global $product_array,$limits,$start,$numbr,$total_records,$page_id,$total_pages,$numbr1,$numbr3,$lmt1,$lmt,$starts,$offset1,$offset,$limit,$image_women,$image_men,$image_kids,$image_digital,$image_sport;
global $c_name,$c_parent_id,$id_c_update,$name_c_update,$parentid_c_update,$id_c_p_update,$name_c_p_update,$parentid_c_p_update;
global $cart;
$cart=array();
$product_array=array();
$image_women=array();
$image_men=array();
$image_kids=array();
$image_digital=array();
$image_sport=array();
$total_records=0;
$limits=6; 
function addProduct()
{
		global $conn,$stmt;

		$id12=$_POST['p_id'];
		$name12=$_POST['p_name'];
		$price12=$_POST['p_price'];
		$category12=$_POST['p_category'];
		
		if(isset($_FILES['p_image']))
		{

			$temp=$_FILES['p_image']['tmp_name'];
			$nm=$_FILES['p_image']['name'];
			if(move_uploaded_file($temp,"../uploadsnew/".$nm))
			{
				$image12=$nm;
				//echo "image uploaded sucessfully";
				
			} else
			{
				echo "no image inserted";
			}
		}
		$stmt=$conn->prepare("INSERT INTO product_table (id,name,price,image,category) VALUES(?,?,?,?,?)");
		$stmt->bind_param("ssiss",$id12,$name12,$price12,$image12,$category12);
		$execute=$stmt->execute();
		if($execute==false)
			{
				$_SESSION['error']="gfg";
				echo "no data inserted";
			}
			else
			{
				$_SESSION['message']="gfgh";
				//echo "data inserted successfully";
			}
			$stmt->close();
			$conn->close();
			
	}

	function updateProduct()
	{
			global $conn,$stmt,$id2,$name2,$price2,$quantity2,$image2,$category2;
			$idtoedit=$_GET['e_id'];
			$stmt=$conn->prepare(" SELECT * FROM product_table  WHERE id=?");
			$stmt->bind_param("s",$idtoedit);
			$stmt->execute();
			$stmt->bind_result($id2,$name2,$price2,$image2,$category2);

			while($stmt->fetch()) {

			}
						 $stmt->close();
		   		         $conn->close();
	}

	function editProduct($hiddenidtoedit,$page_id)
		{
			global $conn,$stmt;
			$id_updated= $_POST['p_id'];
			$name_updated= $_POST['p_name'];
			$price_updated= $_POST['p_price'];
			$category_updated= $_POST['p_category'];
						   
				 if(isset($_FILES['p_image']))
				 {
					$tempn=$_FILES['p_image']['tmp_name'];
					$nm1=$_FILES['p_image']['name'];
				 	if(move_uploaded_file($tempn,"../uploadsnew/".$nm1))
				 	{
				 		echo "image uploaded successfully";
						$image_updated=$nm1;
				
					}
				 }
			
			$stmt=$conn->prepare("UPDATE product_table  SET id=?,name=?,price=?,image=?,category=? WHERE id=?");
			$stmt->bind_param("ssisss",$id_updated,$name_updated,$price_updated,$image_updated,$category_updated,$hiddenidtoedit);
			$stmt->execute();
			header("location:manageprod.php?page_id=<?php echo $page_id;?>");
		}

  function deleteProduct()
  {
  		global $conn,$stmt;
  		$idtodel=$_GET['d_id'];
		$page_id=$_GET['page_id'];
		$stmt=$conn->prepare(" DELETE FROM product_table WHERE id=?");
		$stmt->bind_param("s",$idtodel);
		$stmt->execute();
  }
function getAllProducts($page)
{	
		global $page,$start,$limits,$page_id;
		global $product_array,$stmt,$conn,$image_women,$image_men,$image_kids,$image_digital,$image_sport;
		$query="SELECT * FROM ";
		$query1=" ";
		$start=countProducts($query1);	
		echo $start;
			if($page=="index.php" || $page=="manageprod.php")
			{
				$query.=" product_table ";
				if(isset($_POST['match_category']))
				{
					$query.=" WHERE category='".$_POST['p_category']."' ";
				}
				else if(isset($_GET['ctgry']))
				{
					$query.="WHERE category='".$_GET['ctgry']."' ";
				}
			$query.=" LIMIT ".$start.",".$limits;
			//echo $query;
			$product_array=returnResult($query);
			}
			else if($page=="UserPageIndex.php")
			{
				$query.=" product_table ";
				$product_array=returnResult($query);
				getImagebyCategory($product_array,$image_women,$image_men,$image_kids,$image_digital,$image_sport);
			}
			else if($page=="manage_orders.php")
			{
				//echo "see orders";
				$query.=" table_order";
				$orders_array=returnResult($query);
				return $orders_array;
			}
			else
			{

			}
					
}

function getImagebyCategory($product_array,$image_women,$image_men,$image_kids,$image_digital,$image_sport)
{
    global $product_array,$image_women,$image_men,$image_kids,$image_digital,$image_sport;
  foreach ($product_array as $key => $value) 
  {

    if($product_array[$key]['category']=="Men")
        {
          array_push($image_men,array("id"=>$product_array[$key]['id'],"name"=>$product_array[$key]['name'],"price"=>$product_array[$key]['price'],"image"=>$product_array[$key]['image'],"category"=>$product_array[$key]['category']));
        }
        else if($product_array[$key]['category']=="Women")
        {
        array_push($image_women,array("id"=>$product_array[$key]['id'],"name"=>$product_array[$key]['name'],"price"=>$product_array[$key]['price'],"image"=>$product_array[$key]['image'],"category"=>$product_array[$key]['category']));
        }
        else if($product_array[$key]['category']=="Kids")
        {
         array_push($image_kids,array("id"=>$product_array[$key]['id'],"name"=>$product_array[$key]['name'],"price"=>$product_array[$key]['price'],"image"=>$product_array[$key]['image'],"category"=>$product_array[$key]['category']));
        }
         else if($product_array[$key]['category']=="Digital")
        {
          array_push($image_digital,array("id"=>$product_array[$key]['id'],"name"=>$product_array[$key]['name'],"price"=>$product_array[$key]['price'],"image"=>$product_array[$key]['image'],"category"=>$product_array[$key]['category']));
        }
          else if($product_array[$key]['category']=="Sports")
        {
          array_push($image_sport,array("id"=>$product_array[$key]['id'],"name"=>$product_array[$key]['name'],"price"=>$product_array[$key]['price'],"image"=>$product_array[$key]['image'],"category"=>$product_array[$key]['category']));
        }
  }
 

	
}

function checkPageId()
{
	if(!isset($_GET["page_id"]))
	{
	  $page_id=0;
	}
	else
	{
	  $page_id=$_GET["page_id"];
	}	
	return $page_id;
}

//$start=$page_id*$limits;
   function getCategory()
	{
	global $category_array;
	global $conn,$stmt,$start,$limits;

	 $query1="SELECT * FROM  ";
	 if(isset($_GET['showcategories']))
	 { 	
	 	$start=countProducts($query1);

	 	$query1.=" category_table LIMIT ?,? ";
	 	$stmt=$conn->prepare($query1);
	 	
	 	echo $start."<br>";
	 	$bnd=$stmt->bind_param("ii",$start,$limits);
	 	if($bnd==false)
	 	{
	 		echo "error";
	 	}
	 	
	 }
	 else
	 {
	 	$query1.=" category_table";
	 	$stmt=$conn->prepare($query1);
	 }
	 $stmt->execute();
	 $stmt->bind_result($id13,$name13,$parent_id13);
	 while ($stmt->fetch()) 
	 {
	   array_push($category_array, array("id"=>$id13,"name"=>$name13,"parent_id"=>$parent_id13));
	 }
	
	 return $category_array;
	}

	function editCategory()
	{
		global $conn,$stmt,$id_c_update,$name_c_update,$parentid_c_update,$id_c_p_update,$name_c_p_update,$parentid_c_p_update;
		$idtoedit=$_GET['e_id'];
		$stmt=$conn->prepare("SELECT * FROM category_table WHERE id=?");
		$stmt->bind_param("i",$idtoedit);
		$stmt->execute();
		$stmt->bind_result($id_c_update,$name_c_update,$parentid_c_update);
		while ($stmt->fetch()) 
		{
		}
		
		$stmt=$conn->prepare("SELECT * FROM category_table WHERE id=?");
		$stmt->bind_param("i",$parentid_c_update);
		$stmt->execute();
		$stmt->bind_result($id_c_p_update,$name_c_p_update,$parentid_c_p_update);
		while ($stmt->fetch()) {
		
		}
		
	}
	function setCategory()
	{
		global $c_name,$c_parent_id;
		$c_name=$_POST['category_name'];
		if($_POST['dd_category']==" ")
		{
		
			$c_parent_id=0;
			put_asParentCategory($c_name,$c_parent_id);
			
		}
		else
		{
			
			$category_selected=$_POST['dd_category'];
			makeSubCategory($category_selected,$c_name);
		}
	}
		function put_asParentCategory($c_name,$c_parent_id)
		{
			global $conn,$stmt,$c_name,$c_parent_id;

			$stmt=$conn->prepare("INSERT INTO category_table (category_name,parent_id) VALUES(?,?)");
			$bnd=$stmt->bind_param("si",$c_name,$c_parent_id)	;
			$stmt->execute();
			if(false==$bnd)
			{
				echo "not inserted";
			}
			
			
		}

		function makeSubCategory($category_selected,$c_name1)
		{
			global $conn,$stmt;
			$dd_selected=$category_selected;
			$new_category=$c_name1;
			//echo $new_category;
			$stmt=$conn->prepare("SELECT * FROM category_table WHERE category_name=?");
			if($stmt==false)
			{
				echo "error";
			}
			
			$stmt->bind_param("s",$dd_selected);
			$stmt->execute();
			$stmt->bind_result($sub_cat_id,$ab,$cd);
			while($stmt->fetch())
			{
				$cat_parent_id=$sub_cat_id;
			}
			$stmt->close();
			$stmt=$conn->prepare("INSERT INTO category_table (category_name,parent_id) VALUES(?,?)");
			$stmt->bind_param("si",$new_category,$cat_parent_id);
			$stmt->execute();
			$stmt->close();
			$conn->close();
		}
	

	 function returnResult($query)
	 {
	 	global $conn,$stmt,$limits,$start,$product_array,$page;
	 	$stmt=$conn->query($query);
	    $var=$stmt->num_rows;
	 		 	  		
	 		 	  		if($var > 0)
	 		 	  		{
	 		 	  			//echo "<br>354345";
	 		 	  			while($row=$stmt->fetch_assoc())
	 				 	  	{
	 				 	  		
	 		 	  			if($page=="manage_orders.php")
	 		 	  			{
	 		 	  				
	 		 	  				array_push($product_array,array("email"=>$row['user_email'],"orders"=>$row['order_data'],"date"=>$row['order_date'],"price"=>$row['order_price']));
	 		 	  				
	 		 	  			}
	 		 	  			else
	 		 	  			{
	 		 	  			//
	 				 	  	  array_push($product_array, array("id"=>$row['id'],"name"=>$row['name'],"price"=>$row['price'],"image"=>$row['image'],"category"=>$row['category']));
	 				 	  	}

	 		 	  		}
	 		 	  	}
	 		 	  	
	 			 	  	return $product_array;
	 }

	 function masterFun($id_c,$name_c,$price_c,$image_c,$category_c,$cart)
		{
			global $cart;
			if(isset($_SESSION['cart']))
			{
				$cart=$_SESSION['cart'];
				if(isExist($id_c,$cart))
				{
					$cart=updateProd($id_c,$cart);
					$_SESSION['cart']=$cart;
					$cart=$_SESSION['cart'];
					
				}
				else
				{
				array_push($cart,array("id"=>$id_c,"name"=>$name_c,"price"=>$price_c,"image"=>$image_c,"category"=>$category_c,"quantity"=>1));
				$_SESSION['cart']=$cart;
				$cart=$_SESSION['cart'];
				
				}

			}
			else
			{
				array_push($cart,array("id"=>$id_c,"name"=>$name_c,"price"=>$price_c,"image"=>$image_c,"category"=>$category_c,"quantity"=>1));
				$_SESSION['cart']=$cart;
				$cart=$_SESSION['cart'];
				
			}
				
				//echo json_encode(array("arraycart"=>$cart));
		}

		function isExist($id_c,$cart)
		{
			global $cart;
			foreach($cart as $key=>$value)
			{
				if($id_c==$cart[$key]['id'])
				{
					return true;
				}
			}
			return false;
		}

		function updateProd($id_c,$cart)
		{
			foreach($cart as $key=>$value)
			{
				if($id_c==$cart[$key]['id'])
				{
					$cart[$key]['quantity']=$cart[$key]['quantity']+1;

				}
			}
			return $cart;

		}
	 
		function addtoCart()
	 {
	 				global $cart,$product_array,$stmt,$conn;
	 				$c=0;
					$query="SELECT * FROM product_table "; 
					//echo $query;
					if(isset($_GET['p_id']))
					{
						$query.=" WHERE id=?";
						$vallId= $_GET['p_id'];
						$c=1;

					}
					else if (isset($_POST['match_category'])) 
						{
							$query.=" WHERE category=?";
							$vallId=$_POST['p_category'];
					
						}
						$stmt=$conn->prepare($query);
						$stmt->bind_param("s",$vallId);
						$stmt->execute();

					 $stmt->bind_result($id14,$name14,$price14,$image14,$category14);
					while($stmt->fetch())
					{
						if($c==1)
						{
							echo $c;
							masterFun($id14,$name14,$price14,$image14,$category14,$cart);
							
						}
						else
						{ 
							array_push($product_array,array("id"=>$id14,"name"=>$name14,"price"=>$price14,"image"=>$image14,"category"=>$category14));
						}
						
						
					}
					
	 }



	  function deleteFromCart()
	  {
	  	global $cart;
	  	foreach ($cart as $key => $value)
	  	 {

	  		$did=$_GET['d_id'];
	  		if($cart[$key]['id']==$did)
	  		{
	  			unset($cart[$key]);

	  		}
	  		//$cart=array_values($cart[$key]);
	  	}
	  	$_SESSION['cart']=$cart;
	  	$cart=$_SESSION['cart'];
	  }
	   function filterFun()
	   {
			$k=1;
			$listchecked=0;	
			$query1=" ";   	
	   	 global $stmt,$conn,$start,$limits,$product_array,$array1;


	   	 $query="SELECT * FROM product_table ";
	   	 if(isset($_GET['ctgry']))
	   	 {
	   	 	$query1=" WHERE category='".$_GET['ctgry']."'";
	   	 }
	   	 if(!empty($_GET['check_list']) || !empty($_POST['check_list']))
	 	  	{
	 	  		$array1=!empty($_GET['check_list'])?$_GET['check_list']:$_POST['check_list'];
	 	  		$tr=count($array1);
	 	  		$query1=" WHERE category IN('";  
	 	  		foreach ($array1 as $value) 
	 	  		{
	 	  			
		 	  		if($k<$tr)
		 	  		{
		 	  			$query1.=$value."','";
		 	  			$k++;
		 	  		}
		 	  		else
		 	  		{
		 	  			$query1.=$value."') ";
		 	  		}
	 	  		}
	 	  		$listchecked=1;	

	 	  	}
	 	  	if(isset($_POST['submit_price1']))	
	 	  				{
	 	  					if($listchecked==1)
	 	  					{
	 	  						$query1.="AND ";
	 	  					}
	 	  					else
	 	  					{
	 	  						$query1.="WHERE "; 
	 	  					}
	 	  	  				$query1.=" price BETWEEN '".$_POST['min']."' AND '".$_POST['max']."'";
	 	  	  				

	 	  				}
	 	  				$start=countProducts($query1);
	 	  				$query1.=" LIMIT ".$start.",".$limits;
	 	  				$query=$query.$query1;
	 	  				//echo $query;
	 	  				$product_array=returnResult($query);
	 	  				
	 	  		}

	 	  		function countProducts($query1)
	 	  		{
	 	  			global $stmt,$conn,$start,$page_id,$total_pages,$limits,$page;
	 	  			$page_id=checkPageId();
	 	  			$query_count="SELECT COUNT(*) as count FROM "; 
	 	  			if(isset($_GET['showcategories']))
	 	  			{
	 	  				$query_count.=" category_table";
	 	  			}
	 	  			else if($page=="manage_orders.php")
	 	  			{
	 	  				$query_count.=" table_order";
	 	  			}
	 	  			else if(isset($_POST['match_category']))
	 	  			{
	 	  				$query_count.=" product_table WHERE category='".$_POST['p_category']."'";
	 	  			}
	 	  			else if(isset($_GET['ctgry']))
	 	  			{
	 	  				$query_count.=" product_table WHERE category='".$_GET['ctgry']."'";
	 	  			}
	 	  			else
	 	  			{
	 	  				$query_count.=" product_table".$query1;
	 	  			}
	 	  			//echo $query_count;
	 	  				$stmt=$conn->query($query_count);
	 	  				   $var=$stmt->num_rows;
	 	  						 	  		
	 	  						 	  		if($var > 0)
	 	  						 	  		{
	 	  						 	  			
	 	  						 	  		while($row=$stmt->fetch_assoc())
	 	  								 	{
	 	  									$cnn=$row['count']; 
	 	  									}

	 	  									}			 	  
			 	  		 $total_pages=ceil($cnn/6);
		     			$start=$page_id*$limits;
		     			return $start;				 	  

	 	  		}
	   

 //
	 	 
	 function  loginFunction()
	 {
	 	global $stmt,$conn,$page;
	 	$stmt=$conn->prepare("SELECT * FROM login");

	 	//$stmt->bind_param("sss",$_POST['email_user'],$_POST['name_user'],$_POST['password_user']);
	 	$execute=$stmt->execute();
	 	if($execute==false)
	 	{
	 		echo "no login details found";
	 	}
	 	$stmt->bind_result($idu,$nameu,$passwordu,$role);
	 	while ($stmt->fetch()) 
	 	{
	 		if($idu==$_POST['email_user'] && $nameu==$_POST['name_user'] && $passwordu==$_POST['password_user'])
	 		{
	 			$_SESSION['user_name']=$nameu;
	 			$_SESSION['email']=$idu;
	 			if($_GET['page_name']=="UserPageIndex.php" || $_GET['page_name']=="product1.php" )
	 			{
	 				header("location:checkout.php");
	 			}
	 			else
	 			{
	 				header("location:cart.php");
	 			}
	 			
	 		}
	 		
	}
	 		
	 			echo "wrong login details";
	 		
	 }

	 function registerFunction()

	 {
	 	global $stmt,$conn;
	 	$role="User";
	 	$stmt=$conn->prepare("INSERT INTO login (id_u,name_u,password_u,role_u) VALUES(?,?,?,?)");
	 	$stmt->bind_param("ssss",$_POST['register_email'],$_POST['register_name'],$_POST['regsister_pass'],$role);
	 	$execute=$stmt->execute();
	 	if($execute==false)
	 	{
	 		echo "user not registerd";
	 	}

	 }
	 function placeOrder()
	 {
	 	global $stmt,$conn;
	 	$date=date('Y/m/d H:i:s');
	 	$total_price=$_SESSION['total_price'];
	 	 $cart=json_encode($_SESSION['cart']);
	 	$stmt=$conn->prepare("INSERT INTO table_order (user_email,order_date,order_price,order_data) VALUES(?,?,?,?)");
	 	$stmt->bind_param("ssss",$_SESSION['email'],$date,$total_price,$cart);
	 	$stmt->execute();
	 		session_unset($_SESSION['user_name']);
	 		session_unset($_SESSION['total_price']);
	 		session_unset($_SESSION['email']);
	 		session_destroy();
	 		header("location:thanku.php");
	 }

	 function addToWishList()
	 {
	 	global $stmt,$conn,$wish_array;
	 	$idtowish=$_GET['wish_id'];
	 	$query="SELECT * FROM product_table WHERE id='".$idtowish."'";
	 	//$product_array=returnResult($query);
	 	if(isset($_SESSION['wishlist']))
	 	{
	 		$wish_array=$_SESSION['wishlist'];
	 		if(alreadyExist($idtowish,$wish_array))
	 		{
	 			$_SESSION['message']="already added";
	 		}
	 		else
	 		{
	 			$wish_array=returnResult($query);
	 			$_SESSION['wishlist']=$wish_array;
	 			$wish_array=$_SESSION['wishlist'];
	 		}
	 		
	 	}
			 else
			 {
			 	$wish_array=returnResult($query);
	 			$_SESSION['wishlist']=$wish_array;
	 			$wish_array=$_SESSION['wishlist'];
			 }
			// print_r($_SESSION['wishlist']);
	}
	function alreadyExist($idtowish,$wish_array)
	{
		foreach ($wish_array as $key => $value) 
		{
			if($wish_array[$key]['id']==$idtowish)
			{
				return true;
			}
		}
		return false;
	}
	function editQuantity($value1,$ids_array)
	{
		global $cart;
		foreach ($cart as $key => $value)
		 {
			if($cart[$key]['id']==$ids_array)
			{
				$cart[$key]['quantity']=$value1;
			}
		}
		$_SESSION['cart']=$cart;
		$cart=$_SESSION['cart'];
	}
	function showQuickView($id_to_view)
	{
		global $product_array;
		$query="SELECT * FROM product_table WHERE id = "."'".$id_to_view."'";
		//echo $query;
		$product_array=returnResult($query);
		return $product_array;
		//print_r($product_array);
		
	}

 ?>

