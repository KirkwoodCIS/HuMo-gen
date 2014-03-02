<?php
// *************************************************************
// *** Family                                                ***
// *************************************************************
//error_reporting(E_ALL);
$screen_mode='';
if (isset($_POST["screen_mode"]) AND $_POST["screen_mode"]=='PDF'){ $screen_mode='PDF'; }
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='STAR'){ $screen_mode='STAR'; }
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='STARSIZE'){ $screen_mode='STARSIZE'; }
$hourglass=false;
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='HOUR'){ $screen_mode='STAR'; $hourglass=true; }
if (isset($_GET["screen_mode"]) AND $_GET["screen_mode"]=='HOURSTARSIZE'){ $screen_mode='STARSIZE'; $hourglass=true; }

$pdf_source= array();  // is set in show_sources.php with sourcenr as key to be used in source appendix
// see end of this code
global $chosengen, $genarray, $size, $keepfamily_id, $keepmain_person, $direction;
global $pdf_footnotes;

include_once("header.php"); // returns CMS_ROOTPATH constant

// *** "Last visited" id is used for contact form ***
$last_visited=$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
$_SESSION['save_last_visitid']=$last_visited;

@set_time_limit(300);

$menu=0;
if(isset($_GET['menu']) AND $_GET['menu']=="1") { $menu = 1; }  // called from fanchart iframe with &menu=1-> no menu!

if($screen_mode!='PDF' AND $menu!=1) {  //we can't have a menu in pdf... and don't want it when called in an iframe
	include_once(CMS_ROOTPATH."menu.php");
}

if($screen_mode=='PDF') {  // if PDF mode: necessary parts from menu.php
	if (isset($_SESSION['tree_prefix'])){
		$dataqry = "SELECT * FROM humo_trees LEFT JOIN humo_tree_texts
		ON humo_trees.tree_id=humo_tree_texts.treetext_tree_id
		AND humo_tree_texts.treetext_language='".$selected_language."'
		WHERE tree_prefix='".$tree_prefix_quoted."'";
		@$datasql = $dbh->query($dataqry);
		@$dataDb = $datasql->fetch(PDO::FETCH_OBJ);
	}
}

include_once(CMS_ROOTPATH."include/language_date.php");
include_once(CMS_ROOTPATH."include/language_event.php");
include_once(CMS_ROOTPATH."include/date_place.php");
include_once(CMS_ROOTPATH."include/process_text.php");
include_once(CMS_ROOTPATH."include/calculate_age_cls.php");
include_once(CMS_ROOTPATH."include/person_cls.php");
include_once(CMS_ROOTPATH."include/marriage_cls.php");
include_once(CMS_ROOTPATH."include/show_sources.php");
include_once(CMS_ROOTPATH."include/witness.php");
include_once(CMS_ROOTPATH."include/show_picture.php");


// *** Show person/ family topline: family top text, pop-up settings, PDF export, favorite ***
function topline(){
	global $dataDb, $bot_visit, $descendant_loop, $parent1_marr, $rtlmarker, $family_id, $main_person;
	global $alignmarker, $language, $uri_path, $descendant_report, $family_expanded;
	global $user, $source_presentation, $change_main_person, $maps_presentation, $database, $man_cls, $person_manDb;
	global $woman_cls, $person_womanDb, $selected_language;

	$text='<tr><td class="table_header" width="75%">';

	// *** Text above family ***
	$treetext=show_tree_text($dataDb->tree_prefix, $selected_language);
	$text.='<div class="family_page_toptext fonts">'.$treetext['family_top'].'<br></div>';

	$text.='</td><td class="table_header fonts" width="10%" style="text-align:center";>';

	// *** Hide selections for bots, and second family screen (descendant report etc.) ***
	if (!$bot_visit AND $descendant_loop==0 AND $parent1_marr==0){

		// *** Settings in pop-up screen ***
		//$text.= '<div class="'.$rtlmarker.'sddm" style="position:absolute;left:10px;top:10px;display:inline;">';
		$text.= '<div class="'.$rtlmarker.'sddm" style="left:10px;top:10px;display:inline;">';
			$text.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.'"';
			$text.= ' style="display:inline" ';
			$text.= 'onmouseover="mopen(event,\'help_menu\',0,0)"';
			$text.= 'onmouseout="mclosetime()">';
			$text.= '<b>'.__('Settings').'</b>';
			$text.= '</a>';

			//$text='<div style="z-index:40; padding:4px; direction:'.$rtlmarker.'" id="help_menu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';
			$text.='<div class="sddm_fixed" style="z-index:10; padding:4px; text-align:'.$alignmarker.';  direction:'.$rtlmarker.';" id="help_menu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';

			$text.='<span style="color:blue">=====</span>&nbsp;<b>'.__('Settings family screen').'</b> <span style="color:blue">=====</span><br><br>';

				// *** Extended view button ***
				$text.='<b>'.__('Family Page').'</b><br>';
				
				$desc_rep = ''; if($descendant_report==true) { $desc_rep = '&amp;descendant_report=1'; }

				$selected=' CHECKED'; $selected2=''; if ($family_expanded==true) { $selected=''; $selected2=' CHECKED'; }

				$text.='<input type="radio" name="keuze0" value="" onclick="javascript: document.location.href=\''.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.$desc_rep.'&amp;family_expanded=0&xx=\'+this.value"'.$selected.'>'.__('Compact view')."<br>\n";

				$text.='<input type="radio" name="keuze0" value="" onclick="javascript: document.location.href=\''.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.$desc_rep.'&amp;family_expanded=1&xx=\'+this.value"'.$selected2.'>'.__('Expanded view')."<br>\n";

				// *** Select source presentation (as title/ footnote or hide sources) ***
				if($user['group_sources']!='n') {
					$text.='<hr>';

					$text.='<b>'.__('Sources').'</b><br>';

					$desc_rep = ''; if($descendant_report==true) { $desc_rep = '&amp;descendant_report=1'; }

					$selected=''; if ($source_presentation=='title') { $selected=' CHECKED'; }
					$text.='<input type="radio" name="keuze1" value="" onclick="javascript: document.location.href=\''.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.$desc_rep.'&amp;source_presentation=title&xx=\'+this.value"'.$selected.'>'.__('Show source title')."<br>\n";

					$selected=''; if ($source_presentation=='footnote') { $selected=' CHECKED'; }
					$text.='<input type="radio" name="keuze1" value="" onclick="javascript: document.location.href=\''.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.$desc_rep.'&amp;source_presentation=footnote&xx=\'+this.value"'.$selected.'>'.__('Show source title as footnote')."<br>\n";

					$selected=''; if ($source_presentation=='hide') { $selected=' CHECKED'; }
					$text.='<input type="radio" name="keuze1" value="" onclick="javascript: document.location.href=\''.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.$desc_rep.'&amp;source_presentation=hide&xx=\'+this.value"'.$selected.'>'.__('Hide sources')."<br>\n";
				}

				// *** Show/ hide Google maps ***
				if($descendant_report==false) {
					$text.='<hr>';

					$text.='<b>'.__('Google maps').'</b><br>';

					$selected=''; $selected2='';
					if ($maps_presentation=='false'){ $selected2=' CHECKED'; }
						else{ $selected=' CHECKED'; }
					
					$text.='<input type="radio" name="keuze2" value="" onclick="javascript: document.location.href=\''.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.'&amp;maps_presentation=true&xx=\'+this.value"'.$selected.'>'.__('Show Google maps')."<br>\n";

					$text.='<input type="radio" name="keuze2" value="" onclick="javascript: document.location.href=\''.$_SERVER['PHP_SELF'].'?id='.$family_id.'&amp;main_person='.$main_person.'&amp;maps_presentation=false&xx=\'+this.value"'.$selected2.'>'.__('Hide Google maps')."<br>\n";
				}

			$text.='</div>';
		$text.='</div>';

	$text.='</td><td class="table_header fonts" width="10%" style="text-align:center";>';

		// *** PDF button ***
		if($user["group_pdf_button"]=='y' AND $language["dir"]!="rtl") {
			$text.='<form method="POST" action="'.$uri_path.'family.php?show_sources=1" style="display : inline;">';
			$text.='<input type="hidden" name="id" value="'.$family_id.'">';
			$text.='<input type="hidden" name="main_person" value="'.$main_person.'">';
			$text.='<input type="hidden" name="database" value="'.$database.'">';
			$text.='<input type="hidden" name="screen_mode" value="PDF">';
			if($descendant_report==true) {
				$text.='<input type="hidden" name="descendant_report" value="'.$descendant_report.'">';
			}
			$text.='<input class="fonts" type="Submit" name="submit" value="'.__('PDF Report').'">';
			//$text.='<input type="image" src="images/pdf.jpeg" width="20" border="0" alt="PDF">';
			$text.='</form> ';
		}

	$text.='</td><td class="table_header fonts" width="5%" style="text-align:center";>';

		// *** Add family to favorite list ***
		// If there is a N.N. father, then use mother in favorite icon.
		if ($change_main_person==true OR !isset($person_manDb->pers_gedcomnumber)){
			$name=$woman_cls->person_name($person_womanDb);
			$favorite_gedcomnumber=' ['.$person_womanDb->pers_gedcomnumber.']';
		}
		else{
			$name=$man_cls->person_name($person_manDb);
			$favorite_gedcomnumber=' ['.$person_manDb->pers_gedcomnumber.']';
		}
		if ($name){
			$favorite_values=$name['name'].$favorite_gedcomnumber.'|'.$family_id.'|'.$_SESSION['tree_prefix'].'|'.$main_person;
			$check=false;
			if (isset($_SESSION['save_favorites'])){
				foreach($_SESSION['save_favorites'] as $key=>$value){
					if ($value==$favorite_values){ $check=true; }
				}
			}
			$text.='<form method="POST" action="'.$uri_path.'family.php" style="display : inline;">';
			$text.='<input type="hidden" name="id" value="'.$family_id.'">';
			$text.='<input type="hidden" name="main_person" value="'.$main_person.'">';

			if ($descendant_report==true){ echo '<input type="hidden" name="descendant_report" value="1">'; }
			if ($check==false){
				$text.='<input type="hidden" name="favorite" value="'.$favorite_values.'">';
				$text.=' <input type="image" src="images/favorite.png" name="favorite_button" alt="'.__('Add to favourite list').'" />';
			}
			else{
				$text.='<input type="hidden" name="favorite_remove" value="'.$favorite_values.'">';
				$text.=' <input type="image" src="images/favorite_blue.png" name="favorite_button" alt="'.__('Add to favourite list').'" />';
			}
			$text.='</form>';
		}

	} // End of bot visit

	$text.='</td></tr>';
	
	return $text;
}


$family_nr=1;  // *** process multiple families ***

$family_id=1; // *** standard: show first family ***
if (isset($urlpart[1])){ $family_id=$urlpart[1]; }
if (isset($_GET["id"])){ $family_id=$_GET["id"]; }
if (isset($_POST["id"])){ $family_id=$_POST["id"]; }

$main_person=''; // *** Mainperson of a family ***
if (isset($urlpart[2])){ $main_person=$urlpart[2]; }
if (isset($_GET["main_person"])){ $main_person=$_GET["main_person"]; }
if (isset($_POST["main_person"])){ $main_person=$_POST["main_person"]; }

// *** A favorite ID is used ***
if (isset($_POST["favorite_id"])){
	$favorite_array_id=explode("|",$_POST["favorite_id"]);
	$family_id=$favorite_array_id[0];
	$main_person=$favorite_array_id[1];
}


if($screen_mode=='STAR' OR $screen_mode=='STARSIZE') {
	$chosengen=4;
	if (isset($_GET["chosengen"])){ $chosengen=$_GET["chosengen"]; }
	if (isset($_POST["chosengen"])){ $chosengen=$_POST["chosengen"]; }
	$chosengenanc=4;  // for hourglass -- no. of generations of ancestors
	if (isset($_GET["chosengenanc"])){ $chosengenanc=$_GET["chosengenanc"]; }
	if (isset($_POST["chosengenanc"])){ $chosengenanc=$_POST["chosengenanc"]; }
	if(isset($_SESSION['chartsize'])) { $size=$_SESSION['chartsize']; } 
	else { $size=50; }
	if (isset($_GET["chosensize"])){ $size=$_GET["chosensize"]; }
	if (isset($_POST["chosensize"])){ $size=$_POST["chosensize"]; }
	$_SESSION['chartsize']=$size;
	$keepfamily_id=$family_id;
	$keepmain_person=$main_person;
	$direction=0; // vertical
	if (isset($_GET["direction"])){ $direction=$_GET["direction"]; }
	if (isset($_POST["direction"])){ $direction=$_POST["direction"]; }
}

if($screen_mode=='STARSIZE') {
	if (isset($_SESSION['genarray'])){ $genarray=$_SESSION['genarray']; }
}

if($screen_mode!='STAR' AND $screen_mode!='STARSIZE') {
	// ***************************************************************
	// *** Descendant report                                       ***
	// ***************************************************************
	// == define numbers (max. 60 generations)
	$number_roman = array( 1=>'I', 2=>'II', 3=>'III', 4=>'IV', 5=>'V', 6=>'VI', 7=>'VII', 8=>'VIII', 9=>'IX', 10=>'X',
	11=>'XI', 12=>'XII', 13=>'XIII', 14=>'XIV', 15=>'XV', 16=>'XVI', 17=>'XVII', 18=>'XVIII', 19=>'XIX', 20=>'XX',
	21=>'XXI', 22=>'XXII', 23=>'XXIII', 24=>'XXIV', 25=>'XXV', 26=>'XXVII', 27=>'XXVII', 28=>'XXVIII', 29=>'XXIX', 30=>'XXX',
	31=>'XXXI',32=>'XXXII',33=>'XXXIII',34=>'XXXIV',35=>'XXXV',36=>'XXXVII',37=>'XXXVII',38=>'XXXVIII',39=>'XXXIX',40=>'XL',
	41=>'XLI', 42=>'XLII', 43=>'XLIII', 44=>'XLIV', 45=>'XLV', 46=>'XLVII', 47=>'XLVII', 48=>'XLVIII', 49=>'XLIX', 50=>'L',
	51=>'LI',  52=>'LII',  53=>'LIII',  54=>'LIV',  55=>'LV',  56=>'LVII',  57=>'LVII',  58=>'LVIII',  59=>'LIX',  60=>'LX',);

	//a-z
	$number_generation[]=''; //(1st number_generation is not used)
	for ($i=1; $i<=26; $i++) {
		$number_generation[]=chr($i+96); //chr(97)=a
	}
	//aa-zz
	for ($i=1; $i<=676; $i++) {
		for ($j=1; $j<=26; $j++) {
			$number_generation[]=chr($i+96).chr($j+96); //chr(97)=a
		}
	}

	// **********************************************************
	// *** Maximum number of generations in descendant report ***
	// **********************************************************
	$max_generation=($humo_option["descendant_generations"]-1);

	$descendant_report=false;
	if (isset($_GET['descendant_report'])){ $descendant_report=true; }
	if (isset($_POST['descendant_report'])){ $descendant_report=true; }

	// *** Compact or expanded view ***
	if (isset($_GET['family_expanded'])){
		if ($_GET['family_expanded']=='0'){
			$_SESSION['save_family_expanded']='0';
		}
		else{
			$_SESSION['save_family_expanded']='1';
		}
	}
	// *** Default setting is selected by administrator ***
	$family_expanded=false;
	if ($user['group_family_presentation']=='expanded') $source_presentation=true;
	if ($user['group_family_presentation']=='compact') $source_presentation=false;
	if (isset($_SESSION['save_family_expanded']) AND $_SESSION['save_family_expanded']=='1'){
		$family_expanded=true;
	}

	// *** Source presentation selected by user (title/ footnote or hide sources) ***
	if (isset($_GET['source_presentation'])){
		$_SESSION['save_source_presentation']=safe_text($_GET["source_presentation"]);
	}
	// *** Default setting is selected by administrator ***
	$source_presentation=$user['group_source_presentation'];
	if (isset($_SESSION['save_source_presentation'])){
		$source_presentation=$_SESSION['save_source_presentation'];
	}
	else{
		// *** Save setting in session (if no choice is made, this is admin default setting) ***
		$_SESSION['save_source_presentation']=safe_text($source_presentation);
	}

	// *** Show/ hide Google maps ***
	if (isset($_GET['maps_presentation'])){
		$_SESSION['save_maps_presentation']=safe_text($_GET["maps_presentation"]);
	}
	$maps_presentation='false';
	// *** Default setting is selected by administrator ***
	if ($user['group_maps_presentation']=='show') $maps_presentation='true';
	if ($user['group_maps_presentation']=='hide') $maps_presentation='false';
	if (isset($_SESSION['save_maps_presentation'])){
		$maps_presentation=$_SESSION['save_maps_presentation'];
	}
	else{
		// *** Save setting in session (if no choice is made, this is admin default setting) ***
		$_SESSION['save_maps_presentation']=safe_text($maps_presentation);
	}

}
if($screen_mode=='STAR') {
	$descendant_report=true;
	$genarray = array();
	$family_expanded=false;
}

if($screen_mode=='PDF') {  //initialize pdf generation
	$family_expanded=false;
	$pdfdetails=array();
	$pdf_marriage=array();
	$pdf=new PDF();
	$pers = $dbh->query("SELECT * FROM ".$tree_prefix_quoted."person WHERE pers_gedcomnumber='".safe_text($main_person)."'");
	@$persDb = $pers->fetch(PDO::FETCH_OBJ);
	// *** Use class to process person ***
	$pers_cls = New person_cls;
	$pers_cls->construct($persDb);
	$name=$pers_cls->person_name($persDb);
	if(!$descendant_report==false) {
		$title=pdf_convert(__('Descendant report').__(' of ').$name["standard_name"]);
	}
	else {
		$title=pdf_convert(__('Family group sheet').__(' of ').$name["standard_name"]);
	}
	$pdf->SetTitle($title);
	$pdf->SetAuthor('Huub Mons (pdf: Yossi Beck)');
	$pdf->AddPage();
	$pdf->SetFont('Arial','',12);
} // end if pdfmode

// **************************
// *** Show single person ***
// **************************
if (!$family_id){
	// starfieldchart is never called when there is no own fam so no need to mark this out
	// *** Privacy filter ***
	$person_man = $dbh->query("SELECT * FROM ".$tree_prefix_quoted."person WHERE pers_gedcomnumber='".safe_text($main_person)."'");
	@$person_manDb = $person_man->fetch(PDO::FETCH_OBJ);
	// *** Use class to show person ***
	$man_cls = New person_cls;
	$man_cls->construct($person_manDb);

	if($screen_mode=='PDF') {
		// *** Show familysheet name: user's choice or default ***
		$pdf->Cell(0,2," ",0,1);
		$pdf->SetFont('Arial','BI',12);
		$pdf->SetFillColor(196,242,107);

		$treetext=show_tree_text($dataDb->tree_prefix, $selected_language);
		$family_top=$treetext['family_top'];
		if($family_top!='') {
			$pdf->Cell(0,6,pdf_convert($family_top),0,1,'L',true);
		}
		else {
			$pdf->Cell(0,6,pdf_convert(__('Family group sheet')),0,1,'L',true);
		}

		$pdf->SetFont('Arial','B',12);
		$pdf->Write(8,$man_cls->name_extended("parent1"));
		$pdf->SetFont('Arial','',12);
		$pdf->Write(8,"\n");
		$id='';
		//$pdfdetails= pdf_convert($man_cls->person_data("parent1", $id));
		$pdfdetails= $man_cls->person_data("parent1", $id);
		if($pdfdetails) {
			$pdf->pdfdisplay($pdfdetails,"parent");
		}
	}
	else {
	
		// *** Add tip in person screen ***
		if (!$bot_visit){
			echo '<b>';
			printf(__('TIP: use %s for other (ancestor and descendant) reports.'), '<img src="images/reports.gif">');
			echo '</b><br><br>';
		}

		echo '<table class="humo standard">';

			// *** Show person topline (top text, settings, favorite) ***
			echo topline();

		echo '<tr><td colspan="4">';
			//*** Show person data ***
			echo '<span class="parent1 fonts">'.$man_cls->name_extended("parent1");
			$id='';
			echo $man_cls->person_data("parent1", $id).'</span>';
		echo '</td></tr>';
		echo '</table>';
	} // end if not pdf
}

// *******************
// *** Show family ***
// *******************
else{
	if($screen_mode=='PDF') {
		$pdf->SetFont('Arial','B',15);
		$pdf->Ln(4);
		$name=$pers_cls->person_name($persDb);
		if(!$descendant_report==false) {
			$pdf->MultiCell(0,10,__('Descendant report').__(' of ').$name["standard_name"],0,'C');
		}
		else {
			$pdf->MultiCell(0,10,__('Family group sheet').__(' of ').$name["standard_name"],0,'C');
		}
		$pdf->Ln(4);
		$pdf->SetFont('Arial','',12);
	}
	if($screen_mode!='STARSIZE') {
		$descendant_family_id2[]=$family_id;
		$descendant_main_person2[]=$main_person;
	}

	if($screen_mode=='STAR') {
		$arraynr=0;
	}

	// *** Nr. of generations ***
	if($screen_mode=='STAR') {
//NEW
		if($chosengen != "All") { $max_generation=$chosengen-2; }
		else { $max_generation=100; } // any impossibly high number, will anyway stop at last generation
	}
	if($screen_mode!='STARSIZE') {

	//prepare queries here that will be used in the loops.
	//creating a prepared statement one time will save time
	$family_prep=$dbh->prepare("SELECT fam_man, fam_woman FROM ".$tree_prefix_quoted."family WHERE fam_gedcomnumber=?");
	$family_prep->bindParam(1, $family_id_loop_var);
	$person_prep=$dbh->prepare("SELECT pers_fams FROM ".$tree_prefix_quoted."person WHERE pers_gedcomnumber=?");
	$person_prep->bindParam(1, $parent1_var);
	$family2_prep=$dbh->prepare("SELECT * FROM ".$tree_prefix_quoted."family WHERE fam_gedcomnumber=?");
	$family2_prep->bindParam(1, $id_var);
	$person_man_prep=$dbh->prepare("SELECT * FROM ".$tree_prefix_quoted."person WHERE pers_gedcomnumber=?");
	$person_man_prep->bindParam(1,$pers_man_var);
	$address_qry_prep=$dbh->prepare("SELECT * FROM ".$tree_prefix_quoted."addresses WHERE address_family_id=?");
	$address_qry_prep->bindParam(1,$address_fam_var);
	$famc_adoptive_qry_prep=$dbh->prepare("SELECT * FROM ".$tree_prefix_quoted."events WHERE event_event=? AND event_kind='adoption' ORDER BY event_order");
	$famc_adoptive_qry_prep->bindParam(1,$famc_adopt_var);

	try { // only prepare location statement if table exists otherwise PDO throws exception!
        $result = $dbh->query("SELECT 1 FROM humo_location LIMIT 1"); 
    } catch (Exception $e) {  
        // We got an exception == table not found
        $result = FALSE;
    }
	if($result !== FALSE) { 
		$location_prep=$dbh->prepare("SELECT * FROM humo_location where location_location =?");
		$location_prep->bindParam(1,$location_var);
	}

	$old_stat_prep=$dbh->prepare("UPDATE ".$tree_prefix_quoted."family SET fam_counter=? WHERE fam_gedcomnumber=?");
	$old_stat_prep->bindParam(1,$fam_counter_var);
	$old_stat_prep->bindParam(2,$fam_gednr_var);

	for ($descendant_loop=0; $descendant_loop<=$max_generation; $descendant_loop++){
		$descendant_family_id2[]=0;
		$descendant_main_person[2]=0;
		if (!isset($descendant_family_id2[1])){ break; }

		// *** Copy array ***
		unset ($descendant_family_id);
		$descendant_family_id=$descendant_family_id2;
		unset ($descendant_family_id2);

		unset ($descendant_main_person);
		$descendant_main_person=$descendant_main_person2;
		unset ($descendant_main_person2);

		if($screen_mode=='STAR') {
			if($descendant_loop!=0) {
				if(isset($genarray[$arraynr])) {
					$temppar=$genarray[$arraynr]["par"];
				}
				while(isset($genarray[$temppar]["gen"]) AND $genarray[$temppar]["gen"]==$descendant_loop-1) {
					$lst_in_array += $genarray[$temppar]["nrc"];
					$temppar++;
				}
			}
			$nrchldingen=0;
		}
		else {
			if ($descendant_report==true){
				if($screen_mode!='PDF') {
					echo '<div class="standard_header fonts">'.__('generation ').$number_roman[$descendant_loop+1].'</div>';
				}
				else {
					$pdf->SetLeftMargin(10);
					$pdf->Cell(0,2,"",0,1);
					$pdf->SetFont('Arial','BI',14);
					$pdf->SetFillColor(200,220,255);
					if($pdf->GetY() > 250) { $pdf->AddPage(); $pdf->SetY(20); }
					$pdf->Cell(0,8,pdf_convert(__('generation ')).$number_roman[$descendant_loop+1],0,1,'C',true);
					$pdf->SetFont('Arial','',12);
				}
			}
		}
		// *** Aantal gezinnen in 1 generatie
		for ($descendant_loop2=0; $descendant_loop2<=count($descendant_family_id); $descendant_loop2++){

			if($screen_mode=='STAR') {
				while (isset($genarray[$arraynr]["non"]) AND $genarray[$arraynr]["non"]==1
				AND isset($genarray[$arraynr]["gen"]) AND $genarray[$arraynr]["gen"]==$descendant_loop) {
					$genarray[$arraynr]["nrc"]==0;
					$arraynr++;
				}
			}

			if ($descendant_family_id[$descendant_loop2]==''){ break; }
			$family_id_loop=$descendant_family_id[$descendant_loop2];
			$main_person=$descendant_main_person[$descendant_loop2];
			$family_nr=1;

			// *** Count marriages of man ***
			$family_id_loop_var = $family_id_loop;
			$family_prep->execute();
			try { 
				@$familyDb= $family_prep->fetch(PDO::FETCH_OBJ);
			} catch (PDOException $e) {
				echo __('No valid family number.');
			}			
			 
			$parent1=''; $parent2=''; $change_main_person=false;
			// *** Standard main person is the father ***
			if ($familyDb->fam_man){
				$parent1=$familyDb->fam_man;
			}
			// *** After clicking the mother, the mother is main person ***
			if ($familyDb->fam_woman==$main_person){
				$parent1=$familyDb->fam_woman;
				$change_main_person=true;
			}

			// *** Check for parent1: N.N. ***
			if ($parent1){
				// *** Save man families in array ***
				$parent1_var = $parent1;
				$person_prep->execute();
				@$personDb=$person_prep->fetch(PDO::FETCH_OBJ);

				$marriage_array=explode(";",$personDb->pers_fams);
				$count_marr=substr_count($personDb->pers_fams, ";");
			}
			else{
				$marriage_array[0]=$family_id_loop;
				$count_marr="0";
			}

			// *** Loop multiple marriages of main_person ***
			for ($parent1_marr=0; $parent1_marr<=$count_marr; $parent1_marr++){
				$id=$marriage_array[$parent1_marr];
				$id_var = $id;
				$family2_prep->execute();
				@$familyDb = $family2_prep->fetch(PDO::FETCH_OBJ);
				
				// *** Don't count search bots, crawlers etc. ***
				if (!$bot_visit){
					// *** Update (old) statistics counter ***
					$fam_counter=$familyDb->fam_counter+1;
					$fam_counter_var = $fam_counter;
					$fam_gednr_var = $id;
					$old_stat_prep->execute();

					// *** Extended statistics, first check if table exists ***
					@$statistics = $dbh->query("SELECT * FROM humo_stat_date LIMIT 0,1");
					if ($statistics AND $descendant_report==false AND $user['group_statistics']=='j'){
						$datasql = $dbh->query("SELECT * FROM humo_trees WHERE tree_prefix='".$tree_prefix_quoted."'");
						$datasqlDb = $datasql->fetch(PDO::FETCH_OBJ);
						$stat_easy_id=$datasqlDb->tree_id.'-'.$familyDb->fam_gedcomnumber.'-'.$familyDb->fam_man.'-'.$familyDb->fam_woman;

						$update_sql="INSERT INTO humo_stat_date SET
							stat_easy_id='".$stat_easy_id."',
							stat_ip_address='".$_SERVER['REMOTE_ADDR']."',
							stat_user_agent='".$_SERVER['HTTP_USER_AGENT']."',
							stat_tree_id='".$datasqlDb->tree_id."',
							stat_gedcom_fam='".$familyDb->fam_gedcomnumber."',
							stat_gedcom_man='".$familyDb->fam_man."',
							stat_gedcom_woman='".$familyDb->fam_woman."',
							stat_date_stat='".date("Y-m-d H:i")."',
							stat_date_linux='".time()."'";
						$result = $dbh->query($update_sql);
					}
				}

				// *** Privacy filter man and woman ***
				$pers_man_var = $familyDb->fam_man;
				$person_man_prep->execute();
				@$person_manDb=$person_man_prep->fetch(PDO::FETCH_OBJ);
				
				// *** Proces man using a class ***
				$man_cls = New person_cls;
				$man_cls->construct($person_manDb);

				$pers_man_var = $familyDb->fam_woman;
				$person_man_prep->execute();
				@$person_womanDb=$person_man_prep->fetch(PDO::FETCH_OBJ);

				// *** Proces woman using a clas ***
				$woman_cls = New person_cls;
				$woman_cls->construct($person_womanDb);

				// *** Proces marriage using a class ***
				$marriage_cls = New marriage_cls;
				$marriage_cls->construct($familyDb, $man_cls->privacy, $woman_cls->privacy);
				$family_privacy=$marriage_cls->privacy;

				// *******************************************************************
				// *** Show family                                                 ***
				// *******************************************************************
			if($screen_mode!='STAR') {
				// *** Interne link voor descendant_report ***
				if ($descendant_report==true){
					// *** Interne link (Romeins number_generation) ***
					if($screen_mode!='PDF') {
						echo '<a name="'.$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1].'">';
						echo '&nbsp;</a>';
					}
					else {
						// put internal PDF link to family
						$pdf->Cell(0,1," ",0,1);
						$romannr=$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1];
						if(isset($link[$romannr])) {
							$pdf->SetLink($link[$romannr],-1); //link to this family from child with "volgt"
						}
						$parlink[$id]=$pdf->Addlink();
						$pdf->SetLink($parlink[$id],-1);   // link to this family from parents
					}
				}

				if($screen_mode!='PDF') {

					// *** Add tip in family screen ***
					if (!$bot_visit AND $descendant_loop==0 AND $parent1_marr==0){
						echo '<b>';
						printf(__('TIP: use %s for other (ancestor and descendant) reports.'), '<img src="images/reports.gif">');
						echo '</b><br><br>';
					}

					echo '<table class="humo standard">';

						// *** Show family top line (family top text, settings, favorite) ***
						echo topline();

					echo '<tr><td colspan="4">';
				} //end  "if not pdf"
				else {
					// Show "Family Page", user's choice or default
					$pdf->SetLeftMargin(10);
					$pdf->Cell(0,2," ",0,1);
					if($pdf->GetY() > 260 AND $descendant_loop2!=0) {
						// move to next page so family sheet banner won't be last on page
						// but if we are in first family in generation, the gen banner
						// is already checked so no need here
						$pdf->AddPage(); $pdf->SetY(20);
					}
					$pdf->SetFont('Arial','BI',12);
					$pdf->SetFillColor(186,244,193);

					$treetext=show_tree_text($dataDb->tree_prefix, $selected_language);
					$family_top=$treetext['family_top'];
					if($family_top!='') {
						$pdf->SetLeftMargin(10);
						$pdf->Cell(0,6,pdf_convert($family_top),0,1,'L',true);
					}
					else {
						$pdf->SetLeftMargin(10);
						$pdf->Cell(0,6,pdf_convert(__('Family group sheet')),0,1,'L',true);
					}
					$pdf->SetFont('Arial','',12);

				}
			}  // end if not STAR

				// *************************************************************
				// *** Parent1 (normally the father)                         ***
				// *************************************************************
				if ($familyDb->fam_kind!='PRO-GEN'){  //onecht kind, woman without man
					if ($family_nr==1){
						//*** Show data of man ***
						if($screen_mode=='') {
							echo '<div class="parent1 fonts">';
							// *** Show roman number in descendant_report ***
							if ($descendant_report==true){ echo '<b>'.$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1].'</b> '; }
						}
						if($screen_mode=='PDF') {
							if ($descendant_report==true) { $pdf->Write(8,$number_roman[$descendant_loop+1].'-'.$number_generation[$descendant_loop2+1]." "); }
						}

						if ($change_main_person==true){
							if($screen_mode=='') {
								echo $woman_cls->name_extended("parent1");
								echo $woman_cls->person_data("parent1", $id);

								// *** Change page title ***
								if ($descendant_loop==0 AND $descendant_loop2==0){
									echo '<script type="text/javascript">';
									$name = $woman_cls->person_name($person_womanDb);
									echo 'document.title = "'.__('Family Page').': '.
										$name["index_name"].'";';
									echo '</script>';
								}
							}
							if($screen_mode=='PDF') {
								//  PDF rendering of name + details
								unset ($pdfstr);
								if(!isset($person_womanDb->pers_sexe)) { $pers_sexe = "?";} 
								else $pers_sexe = $person_womanDb->pers_sexe;
								$pdf->writename($pers_sexe,$pdf->GetX()+5,$woman_cls->name_extended("parent1"),"long");
								$pdf->SetLeftMargin($indent);
								$pdfdetails= $woman_cls->person_data("parent1", $id);
								if($pdfdetails) {
									$pdf->pdfdisplay($pdfdetails,"parent1");
								}
								$pdf->SetLeftMargin($indent-5);
							}
							if($screen_mode=='STAR') {
								if($descendant_loop==0) {
									$name=$woman_cls->person_name($person_womanDb);
									$genarray[$arraynr]["nam"]=$name["standard_name"];
									$genarray[$arraynr]["init"]=$name["initials"];
									$genarray[$arraynr]["short"]=$name["short_firstname"];
									$genarray[$arraynr]["sex"]="v";
									$genarray[$arraynr]["fams"]=$id;
									$genarray[$arraynr]["gednr"]=$person_womanDb->pers_gedcomnumber;
									$genarray[$arraynr]["2nd"]=0;
								}
							}
						}
						else{
							if($screen_mode=='') {
								echo $man_cls->name_extended("parent1");
								echo $man_cls->person_data("parent1", $id);

								// *** Change page title ***
								if ($descendant_loop==0 AND $descendant_loop2==0){
									echo '<script type="text/javascript">';
									$name = $man_cls->person_name($person_manDb);
									echo 'document.title = "'.__('Family Page').': '.
										$name["index_name"].'";';
									echo '</script>';
								}
							}
							if($screen_mode=='PDF') {
								//  PDF rendering of name + details
								unset ($pdfstr);
								if(!isset($person_manDb->pers_sexe)) { $pers_sexe = "?";} 
									else $pers_sexe = $person_manDb->pers_sexe;
								$pdf->writename($pers_sexe,$pdf->GetX()+5,$man_cls->name_extended("parent1"),"long");
								$pdf->SetLeftMargin($indent);
								$pdfdetails= $man_cls->person_data("parent1", $id);
								if($pdfdetails) {
									$pdf->pdfdisplay($pdfdetails,"parent1");
								}
								$pdf->SetLeftMargin($indent-5);
							}

							if($screen_mode=='STAR') {
								if($descendant_loop==0) {
									$name=$man_cls->person_name($person_manDb);
									$genarray[$arraynr]["nam"]=$name["standard_name"];
									$genarray[$arraynr]["init"]=$name["initials"];
									$genarray[$arraynr]["short"]=$name["short_firstname"];
									$genarray[$arraynr]["sex"]="m";
									$genarray[$arraynr]["fams"]=$id;
									$genarray[$arraynr]["gednr"]=$person_manDb->pers_gedcomnumber;
									$genarray[$arraynr]["2nd"]=0;
								}
							}
						}

						if($screen_mode=='') {  echo '</div>';}
						//$family_nr++;
					}
					else{
						// *** Show standard marriage text ***
						if($screen_mode=='') {
							echo $marriage_cls->marriage_data($familyDb,$family_nr,'shorter').' ';
						}
						if($screen_mode=='PDF') {
							$pdf->SetLeftMargin($indent);
							$pdf_marriage=$marriage_cls->marriage_data($familyDb,$family_nr,'shorter');
							$pdf->Write(8,$pdf_marriage["relnr_rel"].__(' of ')."\n");
						}

						// *** Only show name in 2nd, 3rd, etc. marriage ***
						if($screen_mode!='STAR') {
							if ($change_main_person==true){
								if($screen_mode!='PDF') {
									echo '<br>'.$woman_cls->name_extended("parent1").'<br>';
								}
								else {
									// PDF rendering of name
									if(!isset($person_womanDb->pers_sexe)) { $pers_sexe = "?";} 
									else $pers_sexe = $person_womanDb->pers_sexe;
									$pdf->writename($pers_sexe,$indent,$woman_cls->name_extended("parent1"),"kort");
								}
							}
							else{
								if($screen_mode!='PDF') {
									echo '<br>'.$man_cls->name_extended("parent1").'<br>';
								}
								else {
									//  PDF rendering of name
									if(!isset($person_manDb->pers_sexe)) { $pers_sexe = "?";} 
									else $pers_sexe = $person_manDb->pers_sexe;
									$pdf->writename($pers_sexe,$indent,$man_cls->name_extended("parent1"),"kort");
								}
							}
						}
						else { //screenmode is STAR
							if($descendant_loop==0) {
								$genarray[$arraynr]=$genarray[$arraynr-1];
								$genarray[$arraynr]["2nd"]=1;
								//$genarray[$arraynr]["fams"]=$id;
							}
							$genarray[$arraynr]["huw"]=$marriage_cls->marriage_data($familyDb,$family_nr,'shorter');
							$genarray[$arraynr]["fams"]=$id;
						}
					}
					$family_nr++;
				} // *** End check of PRO-GEN ***

				// *************************************************************
				// *** Marriage                                              ***
				// *************************************************************
				if ($familyDb->fam_kind!='PRO-GEN'){  //onecht kind, wife without man
					if($screen_mode=='') {
						echo '<br><span class="marriage fonts">';
						// *** $family_privacy='1' = filter ***
						if ($family_privacy){
							// *** Show standard marriage data ***
							echo $marriage_cls->marriage_data($familyDb,'','short');
						}
						else{
							echo $marriage_cls->marriage_data();
						}
						echo '</span><br><br>';
					}
					if($screen_mode=='PDF') {
						//unset ($pdfstr);
						if($family_privacy) {
							$pdf_marriage=$marriage_cls->marriage_data($familyDb,'','short');
							$pdf->SetLeftMargin($indent);
							if($pdf_marriage) {
								$pdf->displayrel($pdf_marriage,"dummy");
							}
						}
						else {
							$pdf_marriage=$marriage_cls->marriage_data();
							$pdf->SetLeftMargin($indent);
							if($pdf_marriage) {
								$pdf->displayrel($pdf_marriage,"dummy");
							}
						}
					}
					if($screen_mode=='STAR') {
						if($family_privacy) {
							$genarray[$arraynr]["htx"]=$marriage_cls->marriage_data($familyDb,'','short');
						}
						else {
							$genarray[$arraynr]["htx"]=$marriage_cls->marriage_data();
						}
					}
				}

				// *************************************************************
				// *** Parent2 (normally the mother)                         ***
				// *************************************************************
				if($screen_mode=='') {
					echo '<div class="parent2 fonts">';
				}
				if ($change_main_person==true){
					if($screen_mode=='') {
						echo $man_cls->name_extended("parent2");
						echo $man_cls->person_data("parent2", $id);
					}
					if($screen_mode=='PDF') {
						unset ($pdfstr);
						// PDF rendering of name + details
						$pdf->Write(8," "); // IMPORTANT - otherwise at bottom of page man/woman.gif image will print, but name may move to following page!
						if(!isset($person_manDb->pers_sexe)) { $pers_sexe = "?";} 
						else $pers_sexe = $person_manDb->pers_sexe;
						$pdf->writename($pers_sexe,$indent,$man_cls->name_extended("parent2"),"kort");
						$pdfdetails= $man_cls->person_data("parent2", $id);
						$pdf->SetLeftMargin($indent);
						if($pdfdetails) {
							$pdf->pdfdisplay($pdfdetails,"parent2");
						}
					}
					if($screen_mode=='STAR') {
						if($person_manDb) {
							$name=$man_cls->person_name($person_manDb);
							$genarray[$arraynr]["sps"]=$name["standard_name"];
							$genarray[$arraynr]["spgednr"]=$person_manDb->pers_gedcomnumber;
						}
						else {
							$genarray[$arraynr]["sps"]= __('Unknown');
							$genarray[$arraynr]["spgednr"]=''; // this is a non existing NN spouse!
						}
						$genarray[$arraynr]["spfams"]=$id;
					}
				}
				else{
					if($screen_mode=='') {
						echo $woman_cls->name_extended("parent2");
						echo $woman_cls->person_data("parent2", $id);
					}
					if($screen_mode=='PDF'){
						unset ($pdfstr);
						// PDF rendering of name + details
						$pdf->Write(8," ");   // IMPORTANT - otherwise at bottom of page man/woman.gif image will print, but name may move to following page!
						if(!isset($person_womanDb->pers_sexe)) { $pers_sexe = "?";} 
						else $pers_sexe = $person_womanDb->pers_sexe;
						$pdf->writename($pers_sexe,$indent,$woman_cls->name_extended("parent2"),"kort");
						$pdfdetails= $woman_cls->person_data("parent2", $id);
						$pdf->SetLeftMargin($indent);
						if($pdfdetails) {
						$pdf->pdfdisplay($pdfdetails,"parent2");
						}
					}
					if($screen_mode=='STAR') {
						if($person_womanDb) {
							$name=$woman_cls->person_name($person_womanDb);
							$genarray[$arraynr]["sps"]=$name["standard_name"];
							$genarray[$arraynr]["spgednr"]=$person_womanDb->pers_gedcomnumber;
						}
						else {
							$genarray[$arraynr]["sps"]= __('Unknown');
							$genarray[$arraynr]["spgednr"]=''; // this is a non existing NN spouse!
						}
						$genarray[$arraynr]["spfams"]=$id;
					}
				}
				if($screen_mode=='') {
					echo '</div>';
				}

				// *************************************************************
				// *** Marriagetext                                          ***
				// *************************************************************
			if($screen_mode!='STAR') {
				if ($family_privacy){
					// No marriage data
				}
				else{
					if ($user["group_texts_fam"]=='j' AND process_text($familyDb->fam_text)){
						if($screen_mode!='PDF') {
							echo '<br>'.process_text($familyDb->fam_text);
							// *** BK: source by family text ***
							echo show_sources2("family","fam_text",$familyDb->fam_gedcomnumber);
						}
						else {
							// PDF rendering of marriage notes
							$pdf->SetFont('Arial','I',11);
							$pdf->Write(6,process_text($familyDb->fam_text)."\n");
							$pdf->Write(6,show_sources2("family","fam_text",$familyDb->fam_gedcomnumber)."\n");
							$pdf->SetFont('Arial','',12);
						}
					}
				}

				// *** Show addresses ***
				if ($user['group_living_place']=='j'){
					if ($familyDb->fam_gedcomnumber){
						$addressnr=0;
						$address_fam_var = $familyDb->fam_gedcomnumber;
						$address_qry_prep->execute();
						if($screen_mode!='PDF') {
							while($addressDb=$address_qry_prep->fetch(PDO::FETCH_OBJ)){
								$nr_addresses=$event_qry->rowCount();
								if ($nr_addresses=='1')
									$residence=__('residence');
								else
									$residence=__('residences');

								$addressnr++;
								if ($addressnr=='1'){ echo '<span class="pers_living_place fonts">'.$residence.': '; }
								if ($addressnr>1){ echo ', '; }
								if ($addressDb->address_date){ echo date_place($addressDb->address_date,'').' '; } //use default function, there is no place...
								echo $addressDb->address_place;

								// *** Show address source ***
								if ($addressDb->address_source){
									echo show_sources2("family","address_source",$addressDb->address_id);
								}
								if ($addressDb->address_text) { echo ' '.$addressDb->address_text; }
							}
							if ($addressnr>0){ echo '</span>'; }
						}
						else {
							//  PDF rendering of addresses
							while($addressDb=$address_qry_prep->fetch(PDO::FETCH_OBJ)){
								$nr_addresses=$event_qry->rowCount();
								if ($nr_addresses=='1')
									$residence=__('residence');
								else
									$residence=__('residences');

								$addressnr++;
								$pdf->SetFont('Arial','',12);
								if ($addressnr=='1'){ $pdf->Write(6,$residence.': '); }
								if ($addressnr>1){ $pdf->Write(6,', '); }
								if ($addressDb->address_date){$pdf->Write(6,date_place($addressDb->address_date,'').' '); }
								$pdf->Write(6,$addressDb->address_place);
								// *** Show address source ***
								if ($addressDb->address_source){
									$pdf->SetFont('Times','',12);  $pdf->Write(6,show_sources2("family","address_source",$addressDb->address_id));
								}
								if ($addressDb->address_text) {$pdf->SetFont('Arial','I',11);  $pdf->Write(6,' '.$addressDb->address_text); }
							} // end while
							if($addressnr>0) {$pdf->Ln(6); }
						}  // end else (pdf)
					}   // end if gedcomnumber
				}   // end if group_living_place

				// *** Family source ***
				if($screen_mode=='PDF') {
					// PDF rendering of sources
					$pdf->Write(6,show_sources2("family","family",$familyDb->fam_gedcomnumber)."\n");
				}
				else {
					echo show_sources2("family","family",$familyDb->fam_gedcomnumber);
				}

			} //end "if not STAR"

			if($screen_mode=='STAR') {
					if($descendant_loop==0) {
					$lst_in_array=$count_marr;
					$genarray[$arraynr]["gen"]=0;
					$genarray[$arraynr]["par"]=-1;
					$genarray[$arraynr]["chd"]=$arraynr + 1;
					$genarray[$arraynr]["non"]=0;
				}
			}

				// *************************************************************
				// *** Children                                              ***
				// *************************************************************

				if($screen_mode=='STAR') {
					if (!$familyDb->fam_children){
						$genarray[$arraynr]["nrc"]=0;
					}
				}

				if ($familyDb->fam_children){
					$childnr=1;
					$child_array=explode(";",$familyDb->fam_children);

					if($screen_mode=='') {
						// *** Show "Child(ren):" ***
						if (count($child_array)=='1'){
							echo '<p><b>'.__('Child').':</b></p>';
						}
						else{
							echo '<p><b>'.__('Children').':</b></p>';
						}

						echo "</td></tr>\n";
					}
					if($screen_mode=='PDF') {
						unset ($pdfstr);
						$pdf->SetLeftMargin(10);
						$pdf->SetDrawColor(200);  // grey line
						$pdf->Cell(0,2," ",'B',1);
					}
					if($screen_mode=='STAR') {
						$genarray[$arraynr]["nrc"]=count($child_array);
					}

					for ($i=0; $i<=substr_count($familyDb->fam_children, ";"); $i++){
						$pers_man_var = $child_array[$i];
						$person_man_prep->execute();
						@$childDb = $person_man_prep->fetch(PDO::FETCH_OBJ);
						
						// *** Use person class ***
						$child_cls = New person_cls;
						$child_cls->construct($childDb);

						if($screen_mode=='') {
							echo '<tr><td colspan="4"><div class="children">';
							echo '<div class="child_nr">'.$childnr.')</div> ';
							echo $child_cls->name_extended("child");
						}
						if($screen_mode=='PDF') {
							//  PDF rendering of name + details
							$pdf->SetFont('Arial','B',11);
							$pdf->SetLeftMargin($indent);
							$pdf->Write(6,$childnr.') ');
							if($childDb->pers_sexe=='M') {
								$pdf->Image("images/man.gif",$pdf->GetX()+1,$pdf->GetY()+1,3.5,3.5);
								$pdf->SetX($pdf->GetX()+5);
							}
							elseif($childDb->pers_sexe=='F') {
								$pdf->Image("images/woman.gif",$pdf->GetX()+1,$pdf->GetY()+1,3.5,3.5);
								$pdf->SetX($pdf->GetX()+5);
							}
							else {
								$pdf->Image("images/unknown.gif",$pdf->GetX()+1,$pdf->GetY()+1,3.5,3.5);
								$pdf->SetX($pdf->GetX()+5);
							}
							$child_indent=$pdf->GetX();
							$pdf->Write(6,$child_cls->name_extended("child"));
							$pdf->SetFont('Arial','',12);
						}
						if($screen_mode=='STAR') {
							$chdn_in_gen=$nrchldingen + $childnr;
							$place=$lst_in_array+$chdn_in_gen;
							$genarray[$place]["gen"]=$descendant_loop+1;
							$genarray[$place]["par"]=$arraynr;
							$genarray[$place]["chd"]=$childnr;
							$genarray[$place]["non"]=0;
							$genarray[$place]["nrc"]=0;
							$genarray[$place]["2nd"]=0;
							$name=$child_cls->person_name($childDb);
							$genarray[$place]["nam"]=$name["standard_name"];
							$genarray[$place]["init"]=$name["initials"];
							$genarray[$place]["short"]=$name["short_firstname"];
							$genarray[$place]["gednr"]=$childDb->pers_gedcomnumber;
							if($childDb->pers_fams) {
								$childfam=explode(";",$childDb->pers_fams);
								$genarray[$place]["fams"]=$childfam[0];
							}
							else {
								$genarray[$place]["fams"]=$childDb->pers_famc;
							}
							if($childDb->pers_sexe == "F") { $genarray[$place]["sex"]="v"; }
								else { $genarray[$place]["sex"]="m"; }
						}

						// *** Build descendant_report ***
						if ($descendant_report==true AND $childDb->pers_fams AND $descendant_loop<$max_generation){

							// *** 1st family of child ***
							$child_family=explode(";",$childDb->pers_fams);

							// *** Check for double families in descendant report (if a person relates or marries another person in the same family) ***
							if (isset($check_double) AND in_array($child_family[0], $check_double)){
								// *** Don't show this family, double... ***
							}
							else
								$descendant_family_id2[]=$child_family[0];

							if($screen_mode!='STAR') {
								// *** Save all marriages of person in check array ***
								for ($k=0; $k<count($child_family) ; $k++) {
									$check_double[]=$child_family[$k];
									// *** Save "Follows: " text in array, also needed for doubles... ***
									$follows_array[]=$number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)];
								}
							}

							if ($screen_mode=='STAR') {
								if (count($child_family>1)) {
									for ($k=1; $k<count($child_family) ; $k++) {
										$childnr++;
										$thisplace=$place+$k;
										$genarray[$thisplace]=$genarray[$place];
										$genarray[$thisplace]["chd"]=$childnr;
										$genarray[$thisplace]["2nd"]=1;
										$genarray[$arraynr]["nrc"]+=1;
									}
								}
							}

							// *** YB: show children first in descendant_report ***
							$descendant_main_person2[]=$childDb->pers_gedcomnumber;
							if($screen_mode=='') {
								//echo '<b><i>, '.__('follows').': </i></b>
								//<a href="'.str_replace("&","&amp;",$_SERVER['REQUEST_URI']).'#'.
								//$number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)].'">'.
								//$number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)].'</a>';

								$search_nr=array_search($child_family[0], $check_double);
								echo '<b><i>, '.__('follows').': </i></b>
								<a href="'.str_replace("&","&amp;",$_SERVER['REQUEST_URI']).'#'.$follows_array[$search_nr].'">'.$follows_array[$search_nr].'</a>';
							}

							if($screen_mode=='PDF') {
								// PDF rendering of link to own family
								$pdf->Write(6,', '.__('follows').': ');
								//$romnr=$number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)];
								$search_nr=array_search($child_family[0], $check_double);
								$romnr=$follows_array[$search_nr];
								$link[$romnr]=$pdf->AddLink();
								$pdf->SetFont('Arial','U',11);  $pdf->SetTextColor(28,28,255);
								//$pdf->Write(6,$number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)]."\n",$link[$romnr]);
								$pdf->Write(6,$romnr."\n",$link[$romnr]);
								$pdf->SetFont('Arial','',12); $pdf->SetTextColor(0);
								$parentchild[$romnr]=$id;
							}

							//echo '<b><i>'.__(', follows: ').'</i></b>
							//  <a href="'.$_SERVER['PHP_SELF'].'#'.$number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)].'">'.
							//  $number_roman[$descendant_loop+2].'-'.$number_generation[count($descendant_family_id2)].'</a>';
						}
						else{
							if($screen_mode=='') {
								echo $child_cls->person_data("child", $id);
							}
							if($screen_mode=='PDF') {
								//  PDF rendering of child details
								$pdf->Write(6,"\n");
								unset ($pdfstr);
								$pdf_child=$child_cls->person_data("child", $id);
								if($pdf_child) {
									$pdf->SetLeftMargin($child_indent);
									$pdf->pdfdisplay($pdf_child,"child");
									$pdf->SetLeftMargin($indent);
								}
							}
							if($screen_mode=='STAR') {
								$genarray[$place]["non"]=1;
							}
						}

						if($screen_mode=='') {
							echo '</div></td></tr>'."\n";
						}
						$childnr++;
					}
					if($screen_mode=='STAR') {
						$nrchldingen += ($childnr-1);
					}
					if($screen_mode=='PDF') {
						$pdf->SetFont('Arial','',12);
					}
				}


				// *********************************************************************************************
				// *** Check for adoptive parents (just for sure: made it for multiple adoptive parents...) ***
				// *********************************************************************************************
				$famc_adopt_var = $familyDb->fam_gedcomnumber;
				$famc_adoptive_qry_prep->execute();
				
				while($famc_adoptiveDb=$famc_adoptive_qry_prep->fetch(PDO::FETCH_OBJ)){
					echo '<tr><td colspan="4"><div class="children">';
					$pers_man_var = $famc_adoptiveDb->event_person_id;
					$person_man_prep->execute();
					@$childDb = $person_man_prep->fetch(PDO::FETCH_OBJ);
					// *** Use person class ***
					$child_cls = New person_cls;
					$child_cls->construct($childDb);
					echo '<b>'.__('Adopted child:').'</b> '.$child_cls->name_extended("child");
					echo '</div></td></tr>'."\n";
				}

				if($screen_mode=='') {
					echo "</table><br>\n";

					// *** Show Google map ***
					if ($descendant_report==false AND $maps_presentation=='true') {
						$show_google_map=false;
						// *** Only show main javascript once ***
						if ($family_nr==2){
							echo '<script src="http://maps.google.com/maps/api/js?v=3&sensor=false" type="text/javascript"></script>';

							echo '<script type="text/javascript">
								var center = null;
								var map=new Array();
								var currentPopup;
								var bounds = new google.maps.LatLngBounds();
							</script>';

							echo '<script type="text/javascript">
								function addMarker(family_nr, lat, lng, info, icon) {
									var pt = new google.maps.LatLng(lat, lng);
									var fam_nr=family_nr;
									bounds.extend(pt);
									//bounds(fam_nr).extend(pt);
									var marker = new google.maps.Marker({
										position: pt,
										icon: icon,
										title: info,
										map: map[fam_nr]
									});
								}
							</script>';
						}

						echo '<script type="text/javascript">

							function initMap'.$family_nr.'(family_nr) {
								var fam_nr=family_nr;
								map[fam_nr] = new google.maps.Map(document.getElementById(fam_nr), {
									center: new google.maps.LatLng(50.917293, 5.974782),
									maxZoom: 16,
									mapTypeId: google.maps.MapTypeId.ROADMAP,
									mapTypeControl: true,
									mapTypeControlOptions: {
										style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR
									}
								});
								';

								unset($location_array); unset($lat_array); unset($lon_array);
								unset($text_array);

								$location_array[]=''; $lat_array[]=''; $lon_array[]='';
								$text_array[]='';

								// BIRTH man
								if ($man_cls->privacy==''){
									$location_var = $person_manDb->pers_birth_place;
									$location_prep->execute();
									$man_birth_result = $location_prep->rowCount();
									if($man_birth_result >0) {
										$info = $location_prep->fetch();
										$name=$man_cls->person_name($person_manDb);
										$google_name=$name["standard_name"];

										$location_array[]=$person_manDb->pers_birth_place;
										$lat_array[]=$info['location_lat'];
										$lon_array[]=$info['location_lng'];
										$text_array[]=addslashes($google_name.", ".__('BORN_SHORT').' '.$person_manDb->pers_birth_place);
									}
								}

								// BIRTH woman
								if ($woman_cls->privacy==''){
									$location_var = $person_womanDb->pers_birth_place;
									$location_prep->execute();
									$woman_birth_result = $location_prep->rowCount();
									
									if($woman_birth_result >0) {
										$info = $location_prep->fetch();
										$name=$woman_cls->person_name($person_womanDb);
										$google_name=$name["standard_name"];

										$key = array_search($person_womanDb->pers_birth_place, $location_array);
										if (isset($key) AND $key>0){
											$text_array[$key].="\\n".addslashes($google_name.", ".__('BORN_SHORT').' '.$person_womanDb->pers_birth_place);
										}
										else{
											$location_array[]=$person_womanDb->pers_birth_place;
											$lat_array[]=$info['location_lat'];
											$lon_array[]=$info['location_lng'];
											$text_array[]=addslashes($google_name.", ".__('BORN_SHORT').' '.$person_womanDb->pers_birth_place);
										}

									}
								}

								// DEATH man
								if ($man_cls->privacy==''){
									$location_var = $person_manDb->pers_death_place;
									$location_prep->execute();
									$man_death_result = $location_prep->rowCount();
									
									if($man_death_result >0) {
										$info = $location_prep->fetch();

										$name=$man_cls->person_name($person_manDb);
										$google_name=$name["standard_name"];
										$key = array_search($person_manDb->pers_death_place, $location_array);
										if (isset($key) AND $key>0){
											$text_array[$key].="\\n".addslashes($google_name.", ".__('DIED_SHORT').' '.$person_manDb->pers_death_place);
										}
										else{
											$location_array[]=$person_manDb->pers_death_place;
											$lat_array[]=$info['location_lat'];
											$lon_array[]=$info['location_lng'];
											$text_array[]=addslashes($google_name.", ".__('DIED_SHORT').' '.$person_manDb->pers_death_place);
										}
									}
								}

								// DEATH woman
								if ($woman_cls->privacy==''){
									$location_var = $person_womanDb->pers_death_place;
									$location_prep->execute();
									$woman_death_result = $location_prep->rowCount();
									if($woman_death_result >0) {
										$info = $location_prep->fetch();

										$name=$woman_cls->person_name($person_womanDb);
										$google_name=$name["standard_name"];
										$key = array_search($person_womanDb->pers_death_place, $location_array);
										if (isset($key) AND $key>0){
											$text_array[$key].="\\n".addslashes($google_name.", ".__('DIED_SHORT').' '.$person_womanDb->pers_death_place);
										}
										else{
											$location_array[]=$person_womanDb->pers_death_place;
											$lat_array[]=$info['location_lat'];
											$lon_array[]=$info['location_lng'];
											$text_array[]=addslashes($google_name.", ".__('DIED_SHORT').' '.$person_womanDb->pers_death_place);
										}

									}
								}

								// MARRIED
								$location_var = $familyDb->fam_marr_place;
								$location_prep->execute();
								$marriage_result = $location_prep->rowCount();
									
								if($marriage_result >0) {
									$info = $location_prep->fetch();

									$name=$man_cls->person_name($person_manDb);
									$google_name=$name["standard_name"];

									$name=$woman_cls->person_name($person_womanDb);
									$google_name.=' & '.$name["standard_name"];

									if ($man_cls->privacy=='' AND $woman_cls->privacy==''){
										$key = array_search($familyDb->fam_marr_place, $location_array);
										if (isset($key) AND $key>0){
											$text_array[$key].="\\n".addslashes($google_name.", ".strtolower(__('Married')).' '.$familyDb->fam_marr_place);
										}
										else{
											$location_array[]=$familyDb->fam_marr_place;
											$lat_array[]=$info['location_lat'];
											$lon_array[]=$info['location_lng'];
											$text_array[]=addslashes($google_name.", ".strtolower(__('Married')).' '.$familyDb->fam_marr_place);
										}
									}
								}


								$child_array=explode(";",$familyDb->fam_children);
								for ($i=0; $i<=substr_count($familyDb->fam_children, ";"); $i++){
									$pers_man_var = $child_array[$i];
									$person_man_prep->execute();
									@$childDb = $person_man_prep->fetch(PDO::FETCH_OBJ);

									// *** Use person class ***
									$person_cls = New person_cls;
									$person_cls->construct($childDb);
									if ($person_cls->privacy==''){

										// *** Child birth ***
										$location_var = $childDb->pers_birth_place;
										$location_prep->execute();
										$child_result = $location_prep->rowCount();
										
										if($child_result >0) {
											$info = $location_prep->fetch();

											$name=$person_cls->person_name($childDb);
											$google_name=$name["standard_name"];
											$key = array_search($childDb->pers_birth_place, $location_array);
											if (isset($key) AND $key>0){
												$text_array[$key].="\\n".addslashes($google_name.", ".__('BORN_SHORT').' '.$childDb->pers_birth_place);
											}
											else{
												$location_array[]=$childDb->pers_birth_place;
												$lat_array[]=$info['location_lat'];
												$lon_array[]=$info['location_lng'];
												$text_array[]=addslashes($google_name.", ".__('BORN_SHORT').' '.$childDb->pers_birth_place);
											}
										}

										// *** Child death ***
										$location_var = $childDb->pers_death_place;
										$location_prep->execute();
										$child_result = $location_prep->rowCount();
										
										if($child_result >0) {
											$info = $location_prep->fetch();

											$name=$person_cls->person_name($childDb);
											$google_name=$name["standard_name"];
											$key = array_search($childDb->pers_death_place, $location_array);
											if (isset($key) AND $key>0){
												$text_array[$key].="\\n".addslashes($google_name.", ".__('DIED_SHORT').' '.$childDb->pers_death_place);
											}
											else{
												$location_array[]=$childDb->pers_death_place;
												$lat_array[]=$info['location_lat'];
												$lon_array[]=$info['location_lng'];
												$text_array[]=addslashes($google_name.", ".__('DIED_SHORT').' '.$childDb->pers_death_place);
											}
										}

									}
								}


								// *** Add all markers from array ***
								for ($i=1; $i<count($location_array); $i++){
									$show_google_map=true;
									echo ("addMarker($family_nr,$lat_array[$i], $lon_array[$i], '".$text_array[$i]."', 'http://chart.apis.google.com/chart?chst=d_map_spin&chld=0.5|0|f7fe2e|10|_|');\n");
								}

								echo 'center = bounds.getCenter();';
								echo 'map[fam_nr].fitBounds(bounds);';
							echo '}
						</script>';

						if ($show_google_map==true){
							echo __('Family events').'<br>';

							echo '<div style="width: 600px; height: 300px; border: 0px; padding: 0px;" id="'.$family_nr.'"></div>';
							
							echo '<script type="text/javascript">
								initMap'.$family_nr.'('.$family_nr.');
							</script>
							';
						}

					}


				}
				if($screen_mode=='STAR') {
					$arraynr++;
				}

			} // Show multiple marriages

		} // Multiple families in 1 generation

	} // nr. of generations
	} // end if not STARSIZE
} // End of single person

// *** If source footnotes are selected, show them here ***
if ($screen_mode!="PDF" AND isset($_SESSION['save_source_presentation']) AND $_SESSION['save_source_presentation']=='footnote'){
	show_sources_footnotes();
}

// *** Extra footer text in family screen ***
if($screen_mode=='') {
	if ($descendant_report==false) {
		// *** Show extra footer text in family screen ***
		$treetext=show_tree_text($dataDb->tree_prefix, $selected_language);
		echo $treetext['family_footer'];

		// *** User is allowed to add a note to a person in the family tree ***
		if ($user['group_user_notes']=='y'){
			// *** Find user that adds a note ***
			$usersql='SELECT * FROM humo_users WHERE user_id="'.$_SESSION['user_id'].'"';
			$user_note=$dbh->query($usersql);
			$userDb=$user_note->fetch(PDO::FETCH_OBJ);

			// *** Name of selected person in family tree ***
			if ($change_main_person==true)
				$name = $woman_cls->person_name($person_womanDb);
			else
				$name = $man_cls->person_name($person_manDb);

			if (isset($_POST['send_mail'])){
				$gedcom_date=strtoupper(date("d M Y")); $gedcom_time=date("H:i:s");

				// *** note_status show/ hide/ moderate options ***
				$user_register_date=date("Y-m-d H:i");
				$sql="INSERT INTO humo_user_notes SET
				note_date='".$gedcom_date."',
				note_time='".$gedcom_time."',
				note_user_id='".safe_text($_SESSION['user_id'])."',
				note_note='".safe_text($_POST["user_note"])."',
				note_fam_gedcomnumber='".safe_text($family_id)."',
				note_pers_gedcomnumber='".safe_text($main_person)."',
				note_tree_prefix='".$tree_prefix_quoted."',
				note_names='".safe_text($name["standard_name"])."'
				;";
				$result=$dbh->query($sql);

				// *** Mail new user note to the administrator ***
				$register_address=$dataDb->tree_email;
				$register_subject="HuMo-gen. ".__('New user note').": ".$userDb->user_name."\n";

				// *** It's better to use plain text in the subject ***
				$register_subject=strip_tags($register_subject,ENT_QUOTES);

				$register_message =__('Message sent through HuMo-gen from the website.')."<br>\n";
				$register_message .="<br>\n";
				$register_message .=__('New user note')."<br>\n";
				$register_message .=__('Name').':'.$userDb->user_name."<br>\n";
				//$register_message .=__('E-mail').": <a href='mailto:".$_POST['register_mail']."'>".$_POST['register_mail']."</a><br>\n";
				$register_message .=$_POST['user_note']."<br>\n";

				$register_message.=__('User note by family').': <a href="'.$_SERVER['SERVER_NAME'].'/'.$_SERVER['PHP_SELF'].'?database='.$tree_prefix_quoted.
				'&amp;id='.$family_id.'&amp;main_person='.$main_person.'">'.safe_text($name["standard_name"]).'</a>';

				$headers  = "MIME-Version: 1.0\n";
				//$headers .= "Content-type: text/plain; charset=utf-8\n";
				$headers .= "Content-type: text/html; charset=utf-8\n";
				$headers .= "X-Priority: 3\n";
				$headers .= "X-MSMail-Priority: Normal\n";
				$headers .= "X-Mailer: php\n";
				$headers .= "From: \"".$userDb->user_name."\" <".$userDb->user_mail.">\n";

				@$mail = mail($register_address, $register_subject, $register_message, $headers);
				
				echo '<table align="center" class="humo">';
				echo '<tr><th><a name="add_info"></a>'.__('Your information is saved and will be reviewed by the webmaster.').'</th></tr>';
				echo '</table>';
			}
			else{

				// *** Script voor expand and collapse of items ***
				echo '
				<script type="text/javascript">
				function hideShow(el_id){
					// *** Hide or show item ***
					var arr = document.getElementsByName(\'row\'+el_id);
					for (i=0; i<arr.length; i++){
						if(arr[i].style.display!="none"){
							arr[i].style.display="none";
						}else{
							arr[i].style.display="";
						}
					}
					// *** Change [+] into [-] or reverse ***
					if (document.getElementById(\'hideshowlink\'+el_id).innerHTML == "[+]")
						document.getElementById(\'hideshowlink\'+el_id).innerHTML = "[-]";
					else
						document.getElementById(\'hideshowlink\'+el_id).innerHTML = "[+]";
				}
				</script>';

				echo '<form method="POST" action="'.$uri_path.'family.php#add_info" style="display : inline;">';
				echo '<input type="hidden" name="id" value="'.$family_id.'">';
				echo '<input type="hidden" name="main_person" value="'.$main_person.'">';

				echo '<table align="center" class="humo" width="40%">';
				echo '<tr><th class="fonts" colspan="2">';
					echo '<a name="add_info"></a>';
					echo '<a href="#add_info" onclick="hideShow(1);"><span id="hideshowlink1">'.__('[+]').'</span></a>';
					echo __('Add information or remarks').'</th></tr>';

				echo '<tr style="display:none;" id="row1" name="row1"><td>'.__('Person').'</td><td>'.$name["standard_name"].'</td></tr>';

				echo '<tr style="display:none;" id="row1" name="row1"><td>'.__('Name').'</td><td>'.$userDb->user_name.'</td></tr>';

				if ($userDb->user_mail==''){
					print '<tr style="background-color:#FF6600; display:none;" id="row1" name="row1"><td>'.__('E-mail address').'</td><td>'.__('Your e-mail address is missing. Please add you\'re mail address here: ').' <a href="user_settings.php">'.__('Settings').'</a></td></tr>';
				}

				$register_text=''; if (isset($_POST['register_text'])){ $register_text=$_POST['register_text']; }
				print '<tr style="display:none;" id="row1" name="row1"><td>'.__('Text').'</td><td><textarea name="user_note" ROWS="5" COLS="40" class="fonts">'.$register_text.'</textarea></td></tr>';

				print '<tr style="display:none;" id="row1" name="row1"><td></td><td><input class="fonts" type="submit" name="send_mail" value="'.__('Send').'"></td></tr>';
				print '</table>';
				print '</form>';
			}

		}

	}
}


// list appendix of sources
if($screen_mode=="PDF" AND !empty($pdf_source) AND ($source_presentation=='footnote' OR $user['group_sources']=='j') ) {
	include_once(CMS_ROOTPATH."source.php");
	$pdf->AddPage(); // appendix on new page
	$pdf->SetFont('Arial',"B",14);
	$pdf->Write(8,__('Sources')."\n\n");
	$pdf->SetFont('Arial','',10);
	// the $pdf_source array is set in show_sources.php with sourcenr as key and value if a linked source is given
	$count=0;
	$source_prep = $dbh->prepare("SELECT * FROM ".$tree_prefix_quoted."sources WHERE source_gedcomnr=?");
	$source_prep->bindParam(1,$source_var);
	foreach($pdf_source as $key => $value) {
		$count++;
		if(isset($pdf_source[$key])) {
			$pdf->SetLink($pdf_footnotes[$count-1],-1);
			$pdf->SetFont('Arial','B',10);
			$pdf->Write(6,$count.". ");
			if($user['group_sources']=='j') {
				source_display($pdf_source[$key]);  // function source_display from source.php, called with source nr.
			}
			elseif ($user['group_sources']=='t') {
				$source_var = $pdf_source[$key];
				$source_prep->execute();
				try {
					@$sourceDb = $source_prep->fetch(PDO::FETCH_OBJ);
				} catch (PDOException $e) {
					echo __("No valid sourcenumber.");
				}
				if ($sourceDb->source_title){
					$pdf->SetFont('Arial','B',10);
					$pdf->Write(6,__('Title').": ");
					$pdf->SetFont('Arial','',10);
					$txt = ' '.trim($sourceDb->source_title);
					if ($sourceDb->source_date or $sourceDb->source_place){ $txt.=" ".date_place($sourceDb->source_date, $sourceDb->source_place); }
					$pdf->Write(6,$txt."\n");
				}
			}
			$pdf->Write(2,"\n");
			$pdf->SetDrawColor(200);  // grey line
			$pdf->Cell(0,2," ",'B',1);
			$pdf->Write(4,"\n");
		}
	}
	unset($value);
}

if($hourglass===false) { // in hourglass there's more code after family.php is included
	if($screen_mode=='STAR' OR $screen_mode=='STARSIZE') {
		include_once(CMS_ROOTPATH."report_descendant.php");
		generate();
		printchart();
	}

	if($screen_mode!='PDF') {
		include_once(CMS_ROOTPATH."footer.php");
	}
	else {
		$pdf->Output($title.".pdf","I");
	}
}
?>