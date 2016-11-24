<?php
// FPDF Customization
//
// Copyright (c) 2010 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

require_once(dirname(__FILE__).'/pdftable/lib/pdftable.inc.php');
//require_once('fpdf_alpha.php');
//require_once('fpdf.php');

class NagiosReportPDF extends PDFTable{
//class NagiosReportPDF extends PDF_ImageAlpha{
//class NagiosReportPDF extends FPDF{

	public $page_title="Title";
	public $page_subtitle="Subtitle\nNewline";
	
	// constructor
	//function __construct(){
        //$this->page_title="Blank Title";
	//	}

	//Page header
	function Header(){
	
		// first page header
		if($this->PageNo()==1){

            // default logo stuff
            $logo="nagiosxi-logo-small.png";

            // use custom logo if it exists
            $logosettings_raw=get_option("custom_logo_options");
            if($logosettings_raw=="")
                $logosettings=array();
            else
                $logosettings=unserialize($logosettings_raw);
            
            $custom_logo_enabled=grab_array_var($logosettings,"enabled");
            if($custom_logo_enabled==1){
                $logo=grab_array_var($logosettings,"logo",$logo);
                }
        
			//Logo
			$this->SetY(15);
			$this->Image(get_base_dir().'/images/'.$logo,10,15,0,0);
			//Arial bold 15
			$this->SetFont('Arial','B',15);
			//Move to the right
			$this->Cell(80);
			//Title
			$this->Cell(30,10,$this->page_title,0,0,'R');
			//Line break
			$this->Ln(10);

			//subtitle
			$this->SetFont('Arial','',10);
			$this->MultiCell(0,4,$this->page_subtitle,0,'C');
			$this->Ln(7);
			
			// header line
			//$y=$this->GetY();
			//$this->Line(10,$y,200,$y);
			}
			
		// future pages
		else{
			//Arial bold 15
			$this->SetFont('Arial','B',15);
			//Move to the right
			$this->Cell(80);
			//Title
			$this->Cell(30,10,$this->page_title,0,0,'R');
			//Line break
			$this->Ln(10);
			
			
			// header line
			//$y=$this->GetY();
			//$this->Line(10,$y,200,$y);			

			parent::Header();
			}
			
		// reset font
		//$this->setfont('times','I',8);;
		}

	//Page footer
	function Footer(){
		// footer line
		$this->Line(10,278,200,278);
	
		//Position at 2.0 cm from bottom
		$this->SetY(-20);
		//Arial italic 8
		//$this->SetFont('Arial','I',8);
		$this->SetFont('Arial','',8);
		//Page number
		//$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'L');
		$this->Cell(0,10,'Page '.$this->PageNo().' of {nb}',0,0,'L');
		
		//Page number
		//$this->Cell(0,10,'Produced '.get_datetime_string(time()),0,0,'R');
		$this->Cell(0,10,''.get_datetime_string(time()),0,0,'R');
		}

	}

?>