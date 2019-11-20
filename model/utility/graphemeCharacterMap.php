<?php
/**
* This file is part of the Research Environment for Ancient Documents (READ). For information on the authors
* and copyright holders of READ, please refer to the file AUTHORS in this distribution or
* at <https://github.com/readsoftware>.
*
* READ is free software: you can redistribute it and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
* or (at your option) any later version.
*
* READ is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with READ.
* If not, see <http://www.gnu.org/licenses/>.
*/
/**
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
*/
require_once (dirname(__FILE__) . '/../entities/Entity.php');
//setup internal configuration to ensure that array key lookup works
if (function_exists('iconv') && PHP_VERSION_ID < 50295)
{
  // These are settings that can be set inside code
  iconv_set_encoding("internal_encoding", "UTF-8");
  iconv_set_encoding("output_encoding", "UTF-8");
  iconv_set_encoding("input_encoding", "UTF-8");
}
else if (PHP_VERSION_ID >= 50295)
{
  ini_set('default_charset', 'UTF-8');
}
//mb_internal_encoding("UTF-8");

$graphemeTypeTermIDMap = array(
"V" => Entity::getIDofTermParentLabel('vowel-graphemetype'),//term dependency
"C" => Entity::getIDofTermParentLabel('consonant-graphemetype'),//term dependency
"O" => Entity::getIDofTermParentLabel('unknown-graphemetype'),//term dependency
"A" => Entity::getIDofTermParentLabel('alphabetic-graphemetype'),//term dependency
"L" => Entity::getIDofTermParentLabel('logograph-graphemetype'),//term dependency
"I" => Entity::getIDofTermParentLabel('intrasyllablepunctuation-graphemetype'),//term dependency
"P" => Entity::getIDofTermParentLabel('punctuation-graphemetype'),//term dependency
"M" => Entity::getIDofTermParentLabel('vowelmodifier-graphemetype'),//term dependency
"N" => Entity::getIDofTermParentLabel('numbersign-graphemetype'));//term dependency

GLOBAL $graphemeCharacterMap;
$graphemeCharacterMap = array(
	// Digits
	"0"=>array("srt"=>"700","typ"=>"N"),
	"½"=>array("srt"=>"705","typ"=>"N"),
	"1"=>array("srt"=>"710","typ"=>"N"),
	"2"=>array("srt"=>"720","typ"=>"N"),
	"3"=>array("srt"=>"730","typ"=>"N"),
	"4"=>array("srt"=>"740","typ"=>"N"),
	"5"=>array("srt"=>"750","typ"=>"N"),
	"6"=>array("srt"=>"760","typ"=>"N"),
	"7"=>array("srt"=>"770","typ"=>"N"),
	"8"=>array("srt"=>"780","typ"=>"N"),
	"9"=>array("srt"=>"790","typ"=>"N"),
	// Punctuation and Intra-word Punctuation
	","=>array("srt"=>"800","typ"=>"P"),
	"."=>array("srt"=>"810","typ"=>"I"),
	";"=>array("srt"=>"820","typ"=>"P"),
	":"=>array("srt"=>"830","typ"=>"I"),
	"!"=>array("srt"=>"840","typ"=>"P"),
	"?"=>array("srt"=>"850","typ"=>"P"),
	"["=>array("srt"=>"860","typ"=>"I"), //Open - space before
	"]"=>array("srt"=>"870","typ"=>"I"), //Close - space after
	"{"=>array("srt"=>"860","typ"=>"I"), //Open - space before
	"}"=>array("srt"=>"870","typ"=>"I"), //Close - space after
	"("=>array("srt"=>"860","typ"=>"I"), //Open - space before
	")"=>array("srt"=>"870","typ"=>"I"), //Close - space after
	"«"=>array("srt"=>"880","typ"=>"P"), //Open - space before
	"»"=>array("srt"=>"890","typ"=>"P"), //Close - space after
	"ϴ"=>array("srt"=>"920","typ"=>"I"), //Theta nigrum
	"☧"=>array("srt"=>"921","typ"=>"I"), //Christogram ⳩ ☧ Chi Rho
	"⳩"=>array("srt"=>"921","typ"=>"I"), //Christogram ⳩ Coptic Khi Ro
	"⸱"=>array("srt"=>"922","typ"=>"P"), //Word Sep Middle Dot
	"·"=>array("srt"=>"922","typ"=>"P"), //Middle Dot
	// Other Symbols 
	"+"=>array("srt"=>"900","typ"=>"O"), //Placeholder, not yet observed in data
	"-"=>array("srt"=>"910","typ"=>"I"), //Placeholder, not yet observed in data
	// Latin
	"A"=>array(
		"̂"=>array("srt"=>"101","typ"=>"I"),
		"srt"=>"100","typ"=>"I"),
	"Â"=>array("srt"=>"101","typ"=>"I"),
	"B"=>array("srt"=>"110","typ"=>"I"),
	"C"=>array("srt"=>"120","typ"=>"I"),
	"D"=>array("srt"=>"130","typ"=>"I"),
	"E"=>array("srt"=>"140","typ"=>"I"),
	"F"=>array("srt"=>"150","typ"=>"I"),
	"G"=>array("srt"=>"160","typ"=>"I"),
	"H"=>array(
		"̂"=>array("srt"=>"171","typ"=>"I"),
		"srt"=>"170","typ"=>"I"),
	"Ĥ"=>array("srt"=>"171","typ"=>"I"),
	"I"=>array(
		"̂"=>array("srt"=>"181","typ"=>"I"),
		"srt"=>"180","typ"=>"I"),
	"Î"=>array("srt"=>"181","typ"=>"I"),
	"Ī"=>array("srt"=>"187","typ"=>"I"),
	"J"=>array("srt"=>"190","typ"=>"I"),
	"K"=>array(
		"̂"=>array("srt"=>"201","typ"=>"I"),
		"srt"=>"200","typ"=>"I"),
	"L"=>array("srt"=>"210","typ"=>"I"),
	"M"=>array(
		"̂"=>array("srt"=>"221","typ"=>"I"),
		"srt"=>"220","typ"=>"I"),
	"N"=>array("srt"=>"230","typ"=>"I"),
	"O"=>array("srt"=>"240","typ"=>"I"),
	"P"=>array("srt"=>"250","typ"=>"I"),
	"Q"=>array("srt"=>"260","typ"=>"I"),
	"R"=>array("srt"=>"270","typ"=>"I"),
	"S"=>array("srt"=>"280","typ"=>"I"),
	"T"=>array("srt"=>"290","typ"=>"I"),
	"U"=>array("srt"=>"300","typ"=>"I"),
	"V"=>array("srt"=>"310","typ"=>"I"),
	"W"=>array("srt"=>"320","typ"=>"I"),
	"X"=>array("srt"=>"330","typ"=>"I"),
	"Y"=>array("srt"=>"340","typ"=>"I"),
	"Z"=>array("srt"=>"350","typ"=>"I"),
	"a"=>array(
	    "̣"=>array("srt"=>"106","csrt"=>"a","typ"=>"I"),
			"᷃"=>array("srt"=>"107","csrt"=>"r","typ"=>"I"),//use upsidedown tilde stands for r
      "̂"=>array("srt"=>"108","csrt"=>"a","typ"=>"I"),
      "srt"=>"105","csrt"=>"a","typ"=>"I"),
  "á"=>array("srt"=>"109","csrt"=>"a","typ"=>"I"),
	"ạ"=>array("srt"=>"106","csrt"=>"a","typ"=>"I"),
	"â"=>array("srt"=>"108","csrt"=>"a","typ"=>"I"),
	"b"=>array(
		"̣"=>array("srt"=>"116","csrt"=>"b","typ"=>"I"),
		"srt"=>"115","typ"=>"I"),
	"ḅ"=>array("srt"=>"116","csrt"=>"b","typ"=>"I"),
	"c"=>array(
		"̣"=>array("srt"=>"126","csrt"=>"c","typ"=>"I"),
		"͞"=>array("srt"=>"127","csrt"=>"i","typ"=>"I"),
		"srt"=>"125","typ"=>"I"),
	"ĉ"=>array("srt"=>"127","csrt"=>"c","typ"=>"I"),
	"d"=>array(
		"̣"=>array("srt"=>"136","csrt"=>"d","typ"=>"I"),
		"͞"=>array("srt"=>"137","csrt"=>"i","typ"=>"I"),
		"̲"=>array("srt"=>"136","csrt"=>"f","typ"=>"I"),
		"͟"=>array("srt"=>"136","csrt"=>"f","typ"=>"I"),
		"srt"=>"135","typ"=>"I"),
	"ḍ"=>array("srt"=>"136","csrt"=>"d","typ"=>"I"),
	"e"=>array(
		"̂"=>array("srt"=>"148","csrt"=>"e","typ"=>"I"),
		"᷃"=>array("srt"=>"147","csrt"=>"e","typ"=>"I"),//use upsidedown tilde
    "̣"=>array("srt"=>"146","csrt"=>"e","typ"=>"I"),
		"̲"=>array("srt"=>"146","csrt"=>"f","typ"=>"I"),
		"͟"=>array("srt"=>"146","csrt"=>"f","typ"=>"I"),
    "srt"=>"145","csrt"=>"e","typ"=>"I"),
	"ẹ"=>array("srt"=>"146","csrt"=>"e","typ"=>"I"),
	"é"=>array("srt"=>"149","csrt"=>"e","typ"=>"I"),
	"ê"=>array("srt"=>"148","csrt"=>"e","typ"=>"I"),
	"f"=>array(
		"̣"=>array("srt"=>"156","csrt"=>"f","typ"=>"I"),
		"̂"=>array("srt"=>"158","csrt"=>"e","typ"=>"I"),
		"̲"=>array("srt"=>"156","csrt"=>"f","typ"=>"I"),
		"͟"=>array("srt"=>"156","csrt"=>"f","typ"=>"I"),
		"srt"=>"155","typ"=>"I"),
	"g"=>array(
		"̣"=>array("srt"=>"166","csrt"=>"g","typ"=>"I"),
		"srt"=>"165","typ"=>"I"),
	"h"=>array(
		"̣"=>array("srt"=>"176","csrt"=>"h","typ"=>"I"),
		"srt"=>"175","typ"=>"I"),
	"ĥ"=>array("srt"=>"178","csrt"=>"h","typ"=>"I"),
	"ḥ"=>array("srt"=>"176","csrt"=>"h","typ"=>"I"),
	"i"=>array(
		"͞"=>array("srt"=>"187","csrt"=>"i","typ"=>"I"),
    "̣"=>array("srt"=>"186","csrt"=>"i","typ"=>"I"),
		"̲"=>array("srt"=>"186","csrt"=>"f","typ"=>"I"),
		"͟"=>array("srt"=>"186","csrt"=>"f","typ"=>"I"),
    "srt"=>"185","csrt"=>"i","typ"=>"I"),
	"ị"=>array("srt"=>"186","csrt"=>"i","typ"=>"I"),
	"í"=>array("srt"=>"189","csrt"=>"i","typ"=>"I"),
	"ì"=>array("srt"=>"189","csrt"=>"i","typ"=>"I"),
	"î"=>array("srt"=>"188","csrt"=>"i","typ"=>"I"),
	"ī"=>array("srt"=>"187","csrt"=>"i","typ"=>"I"),
	"j"=>array(
		"̣"=>array("srt"=>"196","csrt"=>"j","typ"=>"I"),
    "srt"=>"195","csrt"=>"j","typ"=>"I"),
	"ĵ"=>array("srt"=>"198","csrt"=>"j","typ"=>"I"),
	"j̣"=>array("srt"=>"196","csrt"=>"j","typ"=>"I"),
	"k"=>array(
		"̣"=>array("srt"=>"206","csrt"=>"k","typ"=>"I"),
    "srt"=>"205","csrt"=>"k","typ"=>"I"),
	"ḳ"=>array("srt"=>"206","csrt"=>"k","typ"=>"I"),
	"l"=>array(
		"̣"=>array("srt"=>"216","csrt"=>"l","typ"=>"I"),
		"͞"=>array("srt"=>"217","csrt"=>"i","typ"=>"I"),
		"̲"=>array("srt"=>"216","csrt"=>"f","typ"=>"I"),
		"͟"=>array("srt"=>"216","csrt"=>"f","typ"=>"I"),
    "srt"=>"215","csrt"=>"l","typ"=>"I"),
	"ḷ"=>array("srt"=>"216","csrt"=>"l","typ"=>"I"),
	"m"=>array(
		"̂"=>array("srt"=>"228","csrt"=>"m","typ"=>"I"),
		"̣"=>array("srt"=>"226","csrt"=>"m","typ"=>"I"),
		"͞"=>array("srt"=>"227","csrt"=>"i","typ"=>"I"),
		"̄"=>array("srt"=>"227","csrt"=>"m","typ"=>"I"),
		"srt"=>"225","csrt"=>"m","typ"=>"I"),
	"ṃ"=>array("srt"=>"226","csrt"=>"m","typ"=>"I"),
	"n"=>array(
		"̂"=>array("srt"=>"238","csrt"=>"n","typ"=>"I"),
    "̣"=>array("srt"=>"236","csrt"=>"n","typ"=>"I"),
		"srt"=>"235","csrt"=>"n","typ"=>"I"),
	"ṇ"=>array("srt"=>"236","csrt"=>"n","typ"=>"I"),
	"o"=>array(
		"̂"=>array("srt"=>"248","csrt"=>"o","typ"=>"I"),
		"̣"=>array("srt"=>"246","csrt"=>"o","typ"=>"I"),
		"srt"=>"245","csrt"=>"o","typ"=>"I"),
	"ó"=>array("srt"=>"249","csrt"=>"o","typ"=>"I"),
	"ọ"=>array("srt"=>"246","csrt"=>"o","typ"=>"I"),
	"ô"=>array("srt"=>"248","csrt"=>"o","typ"=>"I"),
	"p"=>array(
        "̣"=>array("srt"=>"256","csrt"=>"p","typ"=>"I"),
        "srt"=>"255","csrt"=>"p","typ"=>"I"),
	"q"=>array(
        "̣"=>array("srt"=>"266","csrt"=>"q","typ"=>"I"),
        "srt"=>"265","csrt"=>"q","typ"=>"I"),
	"q̣"=>array("srt"=>"266","csrt"=>"r","typ"=>"I"),
	"r"=>array(
        "̣"=>array("srt"=>"276","csrt"=>"r","typ"=>"I"),
				"̂"=>array("srt"=>"278","csrt"=>"m","typ"=>"I"),
				"srt"=>"275","csrt"=>"r","typ"=>"I"),
	"ṛ"=>array("srt"=>"276","csrt"=>"r","typ"=>"I"),
	"s"=>array(
		"̂"=>array("srt"=>"288","csrt"=>"s","typ"=>"I"),
		"̣"=>array("srt"=>"286","csrt"=>"s","typ"=>"I"),
		"̲"=>array("srt"=>"286","csrt"=>"s","typ"=>"I"),
		"͟"=>array("srt"=>"286","csrt"=>"s","typ"=>"I"),
		"srt"=>"285","csrt"=>"s","typ"=>"I"),
	"ŝ"=>array("srt"=>"288","csrt"=>"s","typ"=>"I"),
	"ṣ"=>array("srt"=>"286","csrt"=>"s","typ"=>"I"),
	"t"=>array(
		"̂"=>array("srt"=>"298","csrt"=>"n","typ"=>"I"),
		"̣"=>array("srt"=>"296","csrt"=>"t","typ"=>"I"),
		"srt"=>"295","csrt"=>"t","typ"=>"I"),
  "ṭ"=>array("srt"=>"296","csrt"=>"t","typ"=>"I"),
	"u"=>array(
        "̂"=>array("srt"=>"308","csrt"=>"u","typ"=>"I"),
        "̣"=>array("srt"=>"306","csrt"=>"u","typ"=>"I"),
				"̲"=>array("srt"=>"306","csrt"=>"u","typ"=>"I"),
				"͟"=>array("srt"=>"306","csrt"=>"u","typ"=>"I"),
				"srt"=>"305","csrt"=>"u","typ"=>"I"),
	"ú"=>array("srt"=>"309","csrt"=>"u","typ"=>"I"),
	"û"=>array("srt"=>"309","csrt"=>"u","typ"=>"I"),
	"ụ"=>array("srt"=>"306","csrt"=>"u","typ"=>"I"),
	"ⅎ"=>array("srt"=>"306","csrt"=>"u","typ"=>"I"),//claudiane letter
	"Ⅎ"=>array("srt"=>"306","csrt"=>"u","typ"=>"I"),//claudiane letter
	"ↄ"=>array("srt"=>"307","csrt"=>"u","typ"=>"I"),//claudiane letter
	"Ↄ"=>array("srt"=>"307","csrt"=>"u","typ"=>"I"),//claudiane letter
	"ⱶ"=>array("srt"=>"308","csrt"=>"u","typ"=>"I"),//claudiane letter
	"Ⱶ"=>array("srt"=>"308","csrt"=>"u","typ"=>"I"),//claudiane letter
	"v"=>array(
        "̂"=>array("srt"=>"318","csrt"=>"v","typ"=>"I"),
				"͞"=>array("srt"=>"317","csrt"=>"i","typ"=>"I"),
        "̣"=>array("srt"=>"316","csrt"=>"v","typ"=>"I"),
				"srt"=>"315","csrt"=>"v","typ"=>"I"),
  "ṿ"=>array("srt"=>"316","csrt"=>"v","typ"=>"I"),
	"w"=>array(
        ""=>array("srt"=>"329","csrt"=>"w","typ"=>"I"),
        "̣"=>array("srt"=>"326","csrt"=>"w","typ"=>"I"),
        "srt"=>"325","csrt"=>"w","typ"=>"I"),
	"ẉ"=>array("srt"=>"326","csrt"=>"w","typ"=>"I"),
	"x"=>array(
        "̣"=>array("srt"=>"336","csrt"=>"x","typ"=>"I"),
				"͞"=>array("srt"=>"337","csrt"=>"i","typ"=>"I"),
        "srt"=>"335","csrt"=>"x","typ"=>"I"),
	"y"=>array(
        "̣"=>array("srt"=>"346","csrt"=>"y","typ"=>"I"),
        "srt"=>"345","csrt"=>"y","typ"=>"I"),
	"ŷ"=>array("srt"=>"348","csrt"=>"y","typ"=>"I"),
	"ỵ"=>array("srt"=>"346","csrt"=>"y","typ"=>"I"),
	"z"=>array(
        "̣"=>array("srt"=>"356","csrt"=>"z","typ"=>"I"),
        "srt"=>"355","csrt"=>"z","typ"=>"I"),
  "ẓ"=>array("srt"=>"356","csrt"=>"z","typ"=>"I"),

// Greek
	"Ἀ"=>array("srt"=>"400","typ"=>"I"),
	"Α"=>array("srt"=>"400","typ"=>"I"),
	"Β"=>array("srt"=>"410","typ"=>"I"),
	"Γ"=>array("srt"=>"420","typ"=>"I"),
	"Δ"=>array("srt"=>"430","typ"=>"I"),
	"Ε"=>array("srt"=>"440","typ"=>"I"),
	"Ζ"=>array("srt"=>"450","typ"=>"I"),
	"Η"=>array("srt"=>"460","typ"=>"I"),
	"Θ"=>array("srt"=>"470","typ"=>"I"),
	"Ι"=>array("srt"=>"480","typ"=>"I"),
	"Κ"=>array("srt"=>"200","typ"=>"I"),
	"Λ"=>array("srt"=>"195","typ"=>"I"),
	"Μ"=>array("srt"=>"220","typ"=>"I"),
	"Ν"=>array("srt"=>"230","typ"=>"I"),
	"Ξ"=>array("srt"=>"225","typ"=>"I"),
	"Ο"=>array("srt"=>"240","typ"=>"I"),
	"Π"=>array("srt"=>"245","typ"=>"I"),
	"Ρ"=>array("srt"=>"250","typ"=>"I"),
	"Σ"=>array("srt"=>"265","typ"=>"I"),
	"Σ"=>array("srt"=>"275","typ"=>"I"),
	"Τ"=>array("srt"=>"285","typ"=>"I"),
	"Υ"=>array("srt"=>"340","typ"=>"I"),
	"Φ"=>array("srt"=>"305","typ"=>"I"),
	"Χ"=>array("srt"=>"330","typ"=>"I"),
	"Ψ"=>array("srt"=>"325","typ"=>"I"),
	"Ω"=>array("srt"=>"335","typ"=>"I"),
	"ἀ"=>array("srt"=>"405","typ"=>"I"),
	"α"=>array("srt"=>"405","typ"=>"I"),
	"β"=>array("srt"=>"415","typ"=>"I"),
	"γ"=>array("srt"=>"425","typ"=>"I"),
	"δ"=>array("srt"=>"435","typ"=>"I"),
	"ε"=>array("srt"=>"445","typ"=>"I"),
	"ἔ"=>array("srt"=>"445","typ"=>"I"),
	"ζ"=>array("srt"=>"455","typ"=>"I"),
	"η"=>array("srt"=>"465","typ"=>"I"),
	"θ"=>array("srt"=>"475","typ"=>"I"),
	"ι"=>array("srt"=>"485","typ"=>"I"),
	"ί"=>array("srt"=>"485","typ"=>"I"),
	"κ"=>array("srt"=>"495","typ"=>"I"),
	"λ"=>array("srt"=>"505","typ"=>"I"),
	"μ"=>array("srt"=>"515","typ"=>"I"),
	"ν"=>array("srt"=>"525","typ"=>"I"),
	"ξ"=>array("srt"=>"535","typ"=>"I"),
	"ο"=>array("srt"=>"545","typ"=>"I"),
	"π"=>array("srt"=>"555","typ"=>"I"),
	"ρ"=>array("srt"=>"565","typ"=>"I"),
	"ς"=>array("srt"=>"575","typ"=>"I"),
	"ϛ"=>array("srt"=>"575","typ"=>"I"),
	"σ"=>array("srt"=>"585","typ"=>"I"),
	"τ"=>array("srt"=>"595","typ"=>"I"),
	"υ"=>array("srt"=>"605","typ"=>"I"),
	"ῦ"=>array("srt"=>"605","typ"=>"I"),
	"φ"=>array("srt"=>"615","typ"=>"I"),
	"ό"=>array("srt"=>"615","typ"=>"I"),
	"χ"=>array("srt"=>"625","typ"=>"I"),
	"ψ"=>array("srt"=>"635","typ"=>"I"),
	"ω"=>array("srt"=>"645","typ"=>"I"),
	// Hebrew
	"א"=>array("srt"=>"365","typ"=>"I"), //ALEF
	"ב"=>array("srt"=>"365","typ"=>"I"), //BET
	"ג"=>array("srt"=>"365","typ"=>"I"), //GIMEL
	"ד"=>array("srt"=>"365","typ"=>"I"), //DALET
	"ה"=>array("srt"=>"365","typ"=>"I"), //HE
	"ו"=>array("srt"=>"365","typ"=>"I"), //VAV
	"ז"=>array("srt"=>"365","typ"=>"I"), //ZAYIN
	"ח"=>array("srt"=>"365","typ"=>"I"), //HET
	"ט"=>array("srt"=>"365","typ"=>"I"), //TET
	"י"=>array("srt"=>"365","typ"=>"I"), //YOD
	"ך"=>array("srt"=>"365","typ"=>"I"), //KAF FINAL
	"כ"=>array("srt"=>"365","typ"=>"I"), //KAF
	"ל"=>array("srt"=>"365","typ"=>"I"), //LAMED
	"ם"=>array("srt"=>"365","typ"=>"I"), //MEM FINAL
	"מ"=>array("srt"=>"365","typ"=>"I"), //MEM
	"ן"=>array("srt"=>"365","typ"=>"I"), //NUN FINAL
	"נ"=>array("srt"=>"365","typ"=>"I"), //NUN
	"ס"=>array("srt"=>"365","typ"=>"I"), //SAMEKH
	"ע"=>array("srt"=>"365","typ"=>"I"), //AYIN
	"ף"=>array("srt"=>"365","typ"=>"I"), //PE FINAL
	"פ"=>array("srt"=>"365","typ"=>"I"), //PE
	"ץ"=>array("srt"=>"365","typ"=>"I"), //TSADI FINAL
	"צ"=>array("srt"=>"365","typ"=>"I"), //TSADI
	"ק"=>array("srt"=>"365","typ"=>"I"), //QOF
	"ר"=>array("srt"=>"365","typ"=>"I"), //RESH
	"ש"=>array("srt"=>"365","typ"=>"I"), //SHIN
	"ת"=>array("srt"=>"365","typ"=>"I"), //TAV

	// Logographic signs
	// "☧"=>array("srt"=>"430","typ"=>"L") //chi rho (Christogram)
	// Functional symbols handled by parser
	// § [ ] "
);

/**
* getNextSegmentState - state engine for segmenting a stream of grapheme types
*
* segmentation use the following state transitions
* where  S = startSeg, C =Consonant, V = Vowel, VM = V modifier, P = Punctuation,
*        N = Number, E = Error, _ = missing C, . = missing V, A = Alphabetic and L = Logograph
* S(C)→C(C)→CC(V)→CCV(~VM)→S
* S(C)→C(C)→CC(V)→CCV(VM)→VM(~VM)→S
* S(C)→C(C)→CC(.)→CC.(~VM)→S
* S(C)→C(C)→CC(.)→CC.(VM)→CC.VM(~VM)→S
* S(C)→C(V)→CV(~VM)→S
* S(C)→C(V)→CV(VM)→VM(~VM)→S
* S(C)→C(.)→C.(VM)→VM(~VM)→S
* S(C)→C(.)→C.→S(~VM)
* S(V)→V(~VM)→S
* S(.)→.(~VM && ~V)→S
* S(.)→.(V)→V(~VM)→S
* S(.)→.(V)→V(VM)→VM→
* S(.)→.(VM)→VM(~VM)→S
* S(V)→V(VM)→VVM(~VM)→S
* S(P)→P(~VM)→S
* S(N)→N(~VM)→S
* S→*→.→E(.)
* S→*→VM→E(VM)
* S→E(VM)
* S→CC→E(VM|P|N|.|C)
*
* Flatten Transissions
* S(C)→C
* S(V)→V
* S(_)→C
* S(.)→V
* S(P)→P
* S(L)→L
* S(N)→N
* S(O)→O
* C(C)→C
* C(_)→C
* C(V)→V
* C(.)→V
* V(~M)→S
* V(VM)→VM
* L(VM)→E
* A(A)→A
* A(~A)→S
* L(L)→L
* L(~L)→S
* VM(~VM)→S
* P(~VM)→S
* N(~VM)→S
* O(~VM)→S
* VM(VM)→E
* P(VM)→E
* A(VM)→E
* L(VM)→E
* N(VM)→E
* O(VM)→E
* M(VM)→E
* S(VM)→E
* C(VM|L|A|P|N|O)→E
*
* @param string $curState indicates the current state of segmentation
* @param string $nextType indicates the type of the next grapheme in sequence
* @return string indicating the transitioned to state
*/
function getNextSegmentState($curState,$nextType) {
  switch ($curState) {
    case "S"://start
      if ($nextType == "M") return "E";
      else return $nextType;
      break;
    case "C"://consonant
      if ($nextType == "V" || $nextType == "C" || $nextType == "N") return $nextType;
      return "E";
      break;
    case "V"://vowel
      if ($nextType == "M") return "M";
      return "S";
      break;
    case "N"://Number
      if ($nextType == "M") {
        return "E";
      } else if ($nextType == "N") {//concat digits together
        return "N";
      }
      return "S";
      break;
    case "L"://Logograph
      if ($nextType == "M") {
        return "E";
      } else if ($nextType == "L") {//Allow combination of Logograph
        return "L";
      } else if ($nextType == "N") {//Allow combination of Logograph with number ending
        return "N";
      }
      return "S";
      break;
    case "A"://Alphabetic
      if ($nextType == "M") {
        return "E";
      }
      return "S";
      break;
    case "M"://vowel modifier
//      if ($nextType == "M") return "M";//allow multiple modifiers
      return $nextType;
      break;
    case "I"://IntraSyllable
      if ($nextType == "M") {
        return "E";
      }
      return $nextType;
      break;
    case "P"://Punctuation
    case "N"://Number
    case "O"://Other
      if ($nextType == "M") return "E";
      return "S";
      break;
    default:
      return "E";
  }
}

?>
