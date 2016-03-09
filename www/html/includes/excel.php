<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@cdukes.com>
 * Copyright (c) 2009 http://www.gdd.net
 * Licensed under terms of GNU General Public License.
 * All rights reserved.
 *
 * Changelog:
 * 2009-06-17 - created
 *
 */

$basePath = dirname( __FILE__ );
require_once($basePath . "/common_funcs.php");

if ($_POST['dbid']){

	/** Error reporting */
   	error_reporting(E_ALL);
	ini_set('memory_limit', '512M');
	ini_set('max_execution_time', '120');

	/** Include path **/
   	ini_set('include_path', ini_get('include_path').":$basePath/PHPExcel/");


	/** PHPExcel */
   	include 'PHPExcel.php';

	/** PHPExcel_Writer_Excel2007 */
   	include 'PHPExcel/Writer/Excel2007.php';
   	include 'PHPExcel/IOFactory.php';

	$date = date("Y-m-d");
   	$time = date("H:i:s");

	function get_server() {
	   	$protocol = 'http';
	   	if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
		   	$protocol = 'https';
	   	}
	   	$host = $_SERVER['HTTP_HOST'];
	   	$baseUrl = $protocol . '://' . $host;
	   	if (substr($baseUrl, -1)=='/') {
		   	$baseUrl = substr($baseUrl, 0, strlen($baseUrl)-1);
	   	}
	   	return $baseUrl;
   	}
	// Create new PHPExcel object
   	$objPHPExcel = new PHPExcel();

	// Set properties
   	$objPHPExcel->getProperties()->setCreator("Clayton Dukes");
   	$objPHPExcel->getProperties()->setLastModifiedBy("Clayton Dukes");
   	$objPHPExcel->getProperties()->setTitle("Php-Syslog-NG Syslog Export");
   	$objPHPExcel->getProperties()->setSubject("Syslog Report for $date $time");
   	$objPHPExcel->getProperties()->setDescription("Generated by ".get_server(). SITEURL);

	// Add some data
   	$_SESSION['table'] = (isset($table)) ? $table : '';
   	$table = get_input('table', false);
   	$inputValError = array();
   	if($table && !validate_input($table, 'table')) {
	   	array_push($inputValError, "table");
   	}
   	if($table) {
	   	$srcTable = $table;
   	}
   	else {
	   	$srcTable = DEFAULTLOGTABLE;
   	}
   	if(!$dbLink = db_connect_syslog(DBUSER, DBUSERPW)) {
	   	require_once 'html_header.php';
	   	echo "DB Connect Error<p>";
		exit;
		
   	}
   	$query = "SELECT * FROM ".$srcTable." WHERE id IN ('";
   	for($i=0; $i < count($_POST['dbid']); $i++) {
	   	$query .= $_POST['dbid'][$i]."','";
   	}
   	$query = rtrim($query, ",''");
   	$query = "$query')";
   	$results = perform_query($query, $dbLink);
   	$n = 0;
   	 // die($query);
   	while($row = fetch_array($results)) {
	   	$result_array[$n] = $row;
	   	$n++;
   	}
   	$objPHPExcel->setActiveSheetIndex(0);
   	$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'DB ID');
   	$objPHPExcel->getActiveSheet()->SetCellValue('B1', 'Sequence');
   	$objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Host');
   	$objPHPExcel->getActiveSheet()->SetCellValue('D1', 'Facility');
   	$objPHPExcel->getActiveSheet()->SetCellValue('E1', 'First Occurence');
   	$objPHPExcel->getActiveSheet()->SetCellValue('F1', 'Last Occurence');
   	$objPHPExcel->getActiveSheet()->SetCellValue('G1', 'Count');
   	$objPHPExcel->getActiveSheet()->SetCellValue('H1', 'Program');
   	$objPHPExcel->getActiveSheet()->SetCellValue('I1', 'Message');

	$objPHPExcel->getActiveSheet()->duplicateStyleArray(
		   	array(
			   	'font'    => array(
				   	'bold'      => true
					),
			   	'alignment' => array(
				   	'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
					),
			   	'borders' => array(
				   	'top'     => array(
					   	'style' => PHPExcel_Style_Border::BORDER_THIN
						),
				   	'bottom'     => array(
					   	'style' => PHPExcel_Style_Border::BORDER_THIN
						),
					),
			   	'fill' => array(
				   	'type' => PHPExcel_Style_Fill::FILL_SOLID,
				   	'startcolor' => array(
					   	'rgb' => HEADER_COLOR,
						),
					),
					),
			   	'A1:I1'
					);

	for($i=0; $i < count($result_array); $i++) {
	   	$row = $result_array[$i];
	   	$r = ($i + 2);
	   	// echo $row['id']."<br>";
	   	// echo "A{$r}<br>";

		$objPHPExcel->getActiveSheet()->SetCellValue("A{$r}", $row['id']);
	   	// Set A{$r} to a Number format
	   	$objPHPExcel->getActiveSheet()->getStyle("A{$r}")->getNumberFormat()->setFormatCode('0');
	   	$objPHPExcel->getActiveSheet()->SetCellValue("B{$r}", $row['seq']);
	   	$objPHPExcel->getActiveSheet()->SetCellValue("C{$r}", $row['host']);
	   	$objPHPExcel->getActiveSheet()->SetCellValue("D{$r}", $row['facility']);
	   	$objPHPExcel->getActiveSheet()->SetCellValue("E{$r}", $row['fo']);
	   	$objPHPExcel->getActiveSheet()->SetCellValue("F{$r}", $row['lo']);
	   	$objPHPExcel->getActiveSheet()->SetCellValue("G{$r}", $row['counter']);
	   	$objPHPExcel->getActiveSheet()->SetCellValue("H{$r}", $row['program']);
	   	$objPHPExcel->getActiveSheet()->SetCellValue("I{$r}", $row['msg']);
	   	if ( $r&1 ) {
		   	$objPHPExcel->getActiveSheet()->duplicateStyleArray(
				   	array(
					   	'fill' => array(
						   	'type' => PHPExcel_Style_Fill::FILL_SOLID,
						   	'startcolor' => array(
							   	'rgb' => DARK_COLOR,
								),
							),
						),
				   	"A${r}:I${r}"
					);
	   	} else {
		   	$objPHPExcel->getActiveSheet()->duplicateStyleArray(
				   	array(
					   	'fill' => array(
						   	'type' => PHPExcel_Style_Fill::FILL_SOLID,
						   	'startcolor' => array(
							   	'rgb' => LIGHT_COLOR,
								),
							),
						),
				   	"A${r}:I${r}"
					);
	   	}
   	}

	// Rename sheet
	$datetime = date("Y-m-d Hi a");
   	$objPHPExcel->getActiveSheet()->setTitle("$datetime");
   	// Autosize Columns
	// This doesn't seem to work and I don't know why - I searched the documentation and forums for PHPExcel but still couldn't get it to work
   	foreach(range('A', 'I') as $columnID) {
	   	$objPHPExcel->getActiveSheet()->getColumnDimension("$columnID")->setAutoSize(true);
   	}

	switch ($_POST['rpt_type']) {
	   	case 'xml':
		   	$fn = "Php-Syslog-NG_Report_".date("Y-m-d_Hia").".xlsx";
		   	// redirect output to client browser
		   	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		   	header("Content-Disposition: attachment;filename=".$fn."");
		   	header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		   	$objWriter->save('php://output'); 
				break;
	   	case 'csv':
		   	$fn = "Php-Syslog-NG_Report_".date("Y-m-d_Hia").".csv";
		   	// redirect output to client browser
		   	header('Content-Type: text/plain');
		   	header("Content-Disposition: attachment;filename=".$fn."");
		   	header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
		   	$objWriter->save('php://output'); 
				break;
	   	case 'pdf':
			// This looks like crap - so I'm leaving it out of the dropdown in regularresult.php for now
		   	$fn = "Php-Syslog-NG_Report_".date("Y-m-d_Hia").".pdf";
		   	// redirect output to client browser
		   	header('Content-Type: text/plain');
		   	header("Content-Disposition: attachment;filename=".$fn."");
		   	header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
		   	$objWriter->save('php://output'); 
				break;
	   	default: // Default is Excel5 Format
		   	$fn = "Php-Syslog-NG_Report_".date("Y-m-d_Hia").".xls";
		   	// redirect output to client browser
		   	header('Content-Type: application/vnd.ms-excel');
		   	header("Content-Disposition: attachment;filename=".$fn."");
		   	header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		   	$objWriter->save('php://output'); 
	}
} else {
   	echo "No results found, did you select any rows for export?<br><a href=\"javascript: history.go(-1)\">BACK TO SEARCH</a>";
}
?>