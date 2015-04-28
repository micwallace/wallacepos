<?php
// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
//             All Rights Reserved,  Very Shafrudin                  //
//                  Contact :  very.shafrudin@gmail.com               
//				Facebook :very.shafrudin@gmail.com 
//    Copying, editing, or deleting of any portion of this script is forbbiden.      //
// Distibution is allowed as it is in complete, with all attributions and comments.  //
// This software is free for both commercial and private use, donations are welcome. //
//                This software comes without any warranty.                          //
//               For any questions or permisions contact author.                     //
// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //

// * * * * * * * * * * * * * * MySql Export Data and/or Value * * * * * * * * * * * * * * * * * * //
//                  MySQL Export, build 08022010                           //
//  If u are interested to contribute to this program or send feedback contact me.   //
// * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * //
 class export_mysql{
  
    var $koneksi;
	var $dbname;
	var $server;
	var $port;
	var $user;
	var $password;
	var $table;
	var $tables;
	var $namafile;
	
  function export_mysql($server, $user, $password, $db){
        $this->dbname = $db;
		$this->user = $user;
		$this->password = $password;
		
		
		$sa = explode(":", $server);
		$this->server = $sa[0];
		$this->port = $sa[1];
		unset($sa);


		$this->koneksi = mysql_connect($this->server, $this->user, $this->password) or $this->error(mysql_error());
		mysql_select_db($this->dbname, $this->koneksi) or $this->error(mysql_error());
  
  }
  
  function buatHeader(){
	$abc = "-- Database Export Class----------------- \n";
	$abc .= "-- Database Name $this->dbname----------------- \n";
	$abc .= "-- Class Created BY Very Shafrudin--------------- \n";
	return $abc;
  }
  
  function exportValue($filenya,$zipped){
   
   
   $headnya = $this->buatHeader();
  
   
   
   //export all data
   $filename = $filenya;
   
   if($handle = fopen($filename,'w')){
    fwrite($handle,$headnya);
	
	$kueri = "SHOW TABLES";
	$kueri_ = mysql_query($kueri,$this->koneksi) or die('Invalid Query1');
	if(mysql_num_rows($kueri_)>=1){
	 while($res = mysql_fetch_array($kueri_) ){
	  $namatabelnya = $res[0];
	   //only value
	   $dumstr = "-- Data Tabel $namatabelnya -------------------- \n";
	   fwrite($handle, $dumstr);
	    
	   $kueriisi = "SELECT * FROM `$namatabelnya";
	   $kueriisi = mysql_query($kueriisi) or die('INvalid Query isi');
	   if(mysql_num_rows($kueriisi)>=1){
	    $struk = $dumfieldisi;
		$dumnomor = 1;
		$maxnomor = 30;
		$dumJumlah = 1;
		while($res = mysql_fetch_array($kueriisi) ){
		   
		   $pjgres = count($res)/2;
		   $isinya = '';
		   for($i=0;$i<$pjgres;$i++){
		      
			 
		     if($i<$pjgres-1){
			  $isinya .= "'".addslashes($res[$i])."',";
			 }else{
			  $isinya .= "'".addslashes($res[$i])."' ";
			 }
			 
		   }
		   //value generate here
		   if( ($dumnomor == 1) and (mysql_num_rows($kueriisi) == 1) ){
		    $dumstr = "INSERT INTO `$namatabelnya` ($struk) VALUES ($isinya); ";//if have only 1 value
		   }else if( ($dumnomor==1) and ($dumJumlah==mysql_num_rows($kueriisi)) ) {
             $dumstr = "INSERT INTO `$namatabelnya` ($struk) VALUES ($isinya); \n";//start at 1 and at last
		   }else if( ($dumnomor == 1) and (mysql_num_rows($kueriisi) > 1) ){
		    $dumstr = "INSERT INTO `$namatabelnya` ($struk) VALUES ($isinya), \n "; //for number 1 and have more than one value
			//echo ("$dumstr <br />");
			//echo('c <br />');
		   }else if ( ($dumnomor>1) and ($dumJumlah<mysql_num_rows($kueriisi)) and ($dumnomor<$maxnomor) ){
		    $dumstr = "($isinya), \n "; //between 1... n
		   }else if(($dumnomor>1) and ($dumJumlah==mysql_num_rows($kueriisi)) ){
		    $dumstr = "($isinya); \n ";// if loop have been reached the number
		   }else if(($dumnomor>1) and ($dumnomor==$maxnomor) ){
		    $dumstr = "($isinya); \n ";// if max loop
		   }
		   
		   if($dumnomor<$maxnomor){
		    $dumnomor++;
		   }else{
		    $dumnomor = 1;
		   }
		   
	       fwrite($handle, $dumstr);
		   $dumJumlah++;
		}
	   }
	  
	 }
	}
	
	fclose($handle);
   }
   
   //options zipped or not
   if($zipped == true){
    
	$namazip = $filename.'.zip';
    $zip = new ZipArchive();
    if ($zip->open($namazip, ZIPARCHIVE::CREATE)!==TRUE) {
     exit("cannot open <$filename>\n");
    }
    $zip->addFile("$filename","$filename");
    $zip->close();
	@unlink($filename);
    //zip or not
  }
   
   
 }
  
  function exportStructure($filenya,$zipped){
  
  
   $headnya = $this->buatHeader();
   
   
   //export all data
   $filename = $filenya;
   
   if($handle = fopen($filename,'w')){
    fwrite($handle,$headnya);
	
	$kueri = "SHOW TABLES";
	$kueri_ = mysql_query($kueri,$this->koneksi) or die('Invalid Query1');
	if(mysql_num_rows($kueri_)>=1){
	 while($res = mysql_fetch_array($kueri_) ){
	  $namatabelnya = $res[0];
	  $kueri = "DESCRIBE $namatabelnya";
	  $tabelstr = mysql_query($kueri,$this->koneksi) or die('Invalid Query2');
	  if(mysql_num_rows($tabelstr)>=1){
	   $dumstr = "-- Struktur Tabel $namatabelnya -------------------- \n";
	   fwrite($handle, $dumstr);
	   $dumstr = "CREATE TABLE IF NOT EXISTS `$namatabelnya` ( \n ";
	   fwrite($handle, $dumstr);
	   $nomor = 1;
	   $dumfieldisi = "";
	   while($res = mysql_fetch_array($tabelstr) ){
		$namafield = $res[0];//FIELD
		$tipefield = $res[1];//TIPE FIELD
		$nullor = $res[2];//NULL or NOT NULL
		if($nullor=='NO'){
		  $dumnull = "NOT NULL";
		}else{
		  $dumnull = "NULL";
		}
		$defaultv = $res[4];//default
		$extrav = $res[5];//auto increment
		
		if($nomor<mysql_num_rows($tabelstr) ){
		 $dumstr = "`$namafield` $tipefield $dumnull $extrav, \n";
		 $dumfieldisi .= "`$namafield`, ";
		}else{
		 $dumstr = "`$namafield` $tipefield $dumnull $extrav ";
		 $dumfieldisi .= "`$namafield` ";
		}
		fwrite($handle, $dumstr);
		$nomor++;
	   }
	   
	   //go to index
	   $indexnya = "SHOW INDEX FROM `$namatabelnya`";
	   $indexnya = mysql_query($indexnya) or die('INvalid Query1');
	   if(mysql_num_rows($indexnya)>=1){
	    $dumstr = ", \n";
		$nomor = 1;
		fwrite($handle, $dumstr);
	    while($res = mysql_fetch_array($indexnya)) {
		  $namafield = $res['Column_name'];
		  $nonunik = $res['Non_unique'];
		  $primari = $res['Key_name'];
		  $fulltext = $res['Index_type'];
		  $dumstr = '';
		  if( ($primari=='PRIMARY') and ($nonunik=='0')  ){
		   //primary key
		   $dumstr = "PRIMARY KEY (`$namafield`)";
		  }else if( ($nonunik=='0') and  ($primari!='PRIMARY') ){
		   //UNIQUE KEY
		   $dumstr = "UNIQUE KEY `$primari` (`$namafield`)";
		  }else if( ($nonunik=='1') and  ($primari!='PRIMARY') ){
		    //KEY
			$dumstr = "KEY `$primari` (`$namafield`)";
		  }else if( ($nonunik=='1') and  ($primari!='PRIMARY') and ($fulltext=='FULLTEXT') ){
		    //FULLTEXT KEY
			$dumstr = "FULLTEXT KEY `$primari` (`$namafield`)";
		  }
		  
		  
		  
		  if($nomor<mysql_num_rows($indexnya) ){
		     $dumstr .= ", \n";
		  }else{
		     $dumstr .= " ";
		  }
		  
		  fwrite($handle, $dumstr);
		  $nomor++;
		}
	   
	   }
	   
	   
	   $dumstr = "\n ); \n";//comma at last
	   fwrite($handle, $dumstr);
	   
	   
	  
	  }
	  
	 }
	}
	
	fclose($handle);
   }
   
   //zip or not options
   if($zipped == true){
    
	$namazip = $filename.'.zip';
    $zip = new ZipArchive();
    if ($zip->open($namazip, ZIPARCHIVE::CREATE)!==TRUE) {
     exit("cannot open <$filename>\n");
    }
    $zip->addFile("$filename","$filename");
    $zip->close();
	@unlink($filename);
    //zip or not
  }
   
   
   
  
  
  }
  
  function exportAll($filenya,$zipped){
   $headnya = $this->buatHeader();
  
   
   
   //export all data
   $filename = $filenya;
   
   if($handle = fopen($filename,'w')){
    fwrite($handle,$headnya);
	
	$kueri = "SHOW TABLES";

	$kueri_ = mysql_query($kueri,$this->koneksi) or die('Invalid Query1');
	if(mysql_num_rows($kueri_)>=1){
	 while($res = mysql_fetch_array($kueri_) ){
	  $namatabelnya = $res[0];
	  $kueri = "DESCRIBE $namatabelnya";
	  $tabelstr = mysql_query($kueri,$this->koneksi) or die('Invalid Query2');
	  if(mysql_num_rows($tabelstr)>=1){
	   $dumstr = "-- Struktur Tabel $namatabelnya -------------------- \n";
	   fwrite($handle, $dumstr);
	   $dumstr = "CREATE TABLE IF NOT EXISTS `$namatabelnya` ( \n ";
	   fwrite($handle, $dumstr);
	   $nomor = 1;
	   $dumfieldisi = "";
	   while($res = mysql_fetch_array($tabelstr) ){
	    //structure
		$namafield = $res[0];//FIELD
		$tipefield = $res[1];//TIPE FIELD
		$nullor = $res[2];//NULL or NOT NULL
		if($nullor=='NO'){
		  $dumnull = "NOT NULL";
		}else{
		  $dumnull = "NULL";
		}
		$defaultv = $res[4];// default
		$extrav = $res[5];//auto increment
		
		if($nomor<mysql_num_rows($tabelstr) ){
		 $dumstr = "`$namafield` $tipefield $dumnull $extrav, \n";
		 $dumfieldisi .= "`$namafield`, ";
		}else{
		 $dumstr = "`$namafield` $tipefield $dumnull $extrav ";
		 $dumfieldisi .= "`$namafield` ";
		}
		fwrite($handle, $dumstr);
		$nomor++;
	   }
	   
	   //go to index
	   $indexnya = "SHOW INDEX FROM `$namatabelnya`";
	   $indexnya = mysql_query($indexnya) or die('INvalid Query1');
	   if(mysql_num_rows($indexnya)>=1){
	    $dumstr = ", \n";
		$nomor = 1;
		fwrite($handle, $dumstr);
	    while($res = mysql_fetch_array($indexnya)) {
		  $namafield = $res['Column_name'];
		  $nonunik = $res['Non_unique'];
		  $primari = $res['Key_name'];
		  $fulltext = $res['Index_type'];
		  $dumstr = '';
		  if( ($primari=='PRIMARY') and ($nonunik=='0')  ){
		   //primary key
		   $dumstr = "PRIMARY KEY (`$namafield`)";
		  }else if( ($nonunik=='0') and  ($primari!='PRIMARY') ){
		   //UNIQUE KEY
		   $dumstr = "UNIQUE KEY `$primari` (`$namafield`)";
		  }else if( ($nonunik=='1') and  ($primari!='PRIMARY') ){
		    //KEY
			$dumstr = "KEY `$primari` (`$namafield`)";
		  }else if( ($nonunik=='1') and  ($primari!='PRIMARY') and ($fulltext=='FULLTEXT') ){
		    //FULLTEXT KEY
			$dumstr = "FULLTEXT KEY `$primari` (`$namafield`)";
		  }
		  
		  
		  
		  if($nomor<mysql_num_rows($indexnya) ){
		     $dumstr .= ", \n";
		  }else{
		     $dumstr .= " ";
		  }
		  
		  fwrite($handle, $dumstr);
		  $nomor++;
		}
	   
	   }
	   
	   
	   $dumstr = "\n ); \n";//comma at last
	   fwrite($handle, $dumstr);
	   
	   
	  
	  }
	  
	   //value
	   $dumstr = "-- Data Tabel $namatabelnya -------------------- \n";
	   fwrite($handle, $dumstr);
	    
	   $kueriisi = "SELECT * FROM `$namatabelnya";
	   $kueriisi = mysql_query($kueriisi) or die('INvalid Query isi');
	   if(mysql_num_rows($kueriisi)>=1){
	    $struk = $dumfieldisi;
		$dumnomor = 1;
		$maxnomor = 30;
		$dumJumlah = 1;
		while($res = mysql_fetch_array($kueriisi) ){
		   
		   $pjgres = count($res)/2;
		   $isinya = '';
		   for($i=0;$i<$pjgres;$i++){
		     
			 
		     if($i<$pjgres-1){
			  $isinya .= "'".addslashes($res[$i])."',";
			 }else{
			  $isinya .= "'".addslashes($res[$i])."' ";
			 }
			 
		   }
		  
		   //value generate here
		   if( ($dumnomor == 1) and (mysql_num_rows($kueriisi) == 1) ){
		    $dumstr = "INSERT INTO `$namatabelnya` ($struk) VALUES ($isinya); ";//if have only 1 value
		   }else if( ($dumnomor==1) and ($dumJumlah==mysql_num_rows($kueriisi)) ) {
             $dumstr = "INSERT INTO `$namatabelnya` ($struk) VALUES ($isinya); \n";//start at 1 and at last
		   }else if( ($dumnomor == 1) and (mysql_num_rows($kueriisi) > 1) ){
		    $dumstr = "INSERT INTO `$namatabelnya` ($struk) VALUES ($isinya), \n "; //for number 1 and have more than one value
			//echo ("$dumstr <br />");
			//echo('c <br />');
		   }else if ( ($dumnomor>1) and ($dumJumlah<mysql_num_rows($kueriisi)) and ($dumnomor<$maxnomor) ){
		    $dumstr = "($isinya), \n "; //between 1... n
		   }else if(($dumnomor>1) and ($dumJumlah==mysql_num_rows($kueriisi)) ){
		    $dumstr = "($isinya); \n ";// if loop have been reached the number
		   }else if(($dumnomor>1) and ($dumnomor==$maxnomor) ){
		    $dumstr = "($isinya); \n ";// if max loop
		   }
		   
		   if($dumnomor<$maxnomor){
		    $dumnomor++;
		   }else{
		    $dumnomor = 1;
		   }
		   
	       fwrite($handle, $dumstr);
		   $dumJumlah++;
		}
	   }
	  
	 }
	}
	
	fclose($handle);
   }
   
   //zip or not
   if($zipped == true){
    
	$namazip = $filename.'.zip';
    $zip = new ZipArchive();
    if ($zip->open($namazip, ZIPARCHIVE::CREATE)!==TRUE) {
     exit("cannot open <$filename>\n");
    }
    $zip->addFile("$filename","$filename");
    $zip->close();
	@unlink($filename);
    //zip or not
  }
   
   
   
  }
  
 
 }
 
?>
