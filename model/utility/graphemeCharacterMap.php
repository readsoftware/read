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
if (function_exists('iconv') && PHP_VERSION_ID < 50600)
{
  // These are settings that can be set inside code
  iconv_set_encoding("internal_encoding", "UTF-8");
  iconv_set_encoding("output_encoding", "UTF-8");
  iconv_set_encoding("input_encoding", "UTF-8");
}
else if (PHP_VERSION_ID >= 50600)
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
	"ʼ"=>array("srt"=>"805","typ"=>"I"),
	"’"=>array("srt"=>"805","typ"=>"I"),
	"᾽"=>array("srt"=>"805","typ"=>"P"),
	"."=>array("srt"=>"810","typ"=>"I"),
	";"=>array("srt"=>"820","typ"=>"P"),
	":"=>array("srt"=>"830","typ"=>"I"),
	"!"=>array("srt"=>"840","typ"=>"P"),
	"?"=>array("srt"=>"850","typ"=>"P"),
//	"["=>array("srt"=>"860","typ"=>"I"), //Open - space before
//	"]"=>array("srt"=>"870","typ"=>"I"), //Close - space after
//	"{"=>array("srt"=>"860","typ"=>"I"), //Open - space before
//	"}"=>array("srt"=>"870","typ"=>"I"), //Close - space after
//	"("=>array("srt"=>"860","typ"=>"I"), //Open - space before
//	")"=>array("srt"=>"870","typ"=>"I"), //Close - space after
//	"«"=>array("srt"=>"880","typ"=>"P"), //Open - space before
//	"»"=>array("srt"=>"890","typ"=>"P"), //Close - space after
	"ϴ"=>array("srt"=>"920","typ"=>"I"), //Theta nigrum
	"☧"=>array("srt"=>"921","typ"=>"I"), //Christogram ⳩ ☧ Chi Rho
	"⳩"=>array("srt"=>"921","typ"=>"I"), //Christogram ⳩ Coptic Khi Ro
	"⸱"=>array("srt"=>"922","typ"=>"P"), //Word Sep Middle Dot
	"·"=>array("srt"=>"922","typ"=>"P"), //Middle Dot
	"˙"=>array("srt"=>"923","typ"=>"P"), //Above Dot
	"͂"=>array("srt"=>"924","typ"=>"I"), //Greek perispomeni like tilde
	"͜"=>array("srt"=>"924","typ"=>"I"), //Breve below
	"҆"=>array("srt"=>"924","typ"=>"I"), //PSili Pneumata
	"̈́"=>array("srt"=>"925","typ"=>"I"), //Dialytika tonos
	"‾"=>array("srt"=>"925","typ"=>"P"), //Overline Greek BOL
	// Other Symbols 
	"+"=>array("srt"=>"900","typ"=>"O"), //Placeholder, not yet observed in data
	"-"=>array("srt"=>"910","typ"=>"I"), //Placeholder, not yet observed in data
	// Latin
	"A"=>array(
		"̂"=>array("srt"=>"101","typ"=>"A"),
		"srt"=>"100","typ"=>"A"),
	"Â"=>array("srt"=>"101","typ"=>"A"),
	"B"=>array("srt"=>"110","typ"=>"A"),
	"C"=>array("srt"=>"120","typ"=>"A"),
	"D"=>array("srt"=>"130","typ"=>"A"),
	"E"=>array("srt"=>"140","typ"=>"A"),
	"F"=>array("srt"=>"150","typ"=>"A"),
	"G"=>array("srt"=>"160","typ"=>"A"),
	"H"=>array(
		"̂"=>array("srt"=>"171","typ"=>"A"),
		"srt"=>"170","typ"=>"A"),
	"Ĥ"=>array("srt"=>"171","typ"=>"A"),
	"I"=>array(
		"̂"=>array("srt"=>"181","typ"=>"A"),
		"srt"=>"180","typ"=>"A"),
	"Î"=>array("srt"=>"181","typ"=>"A"),
	"Ī"=>array("srt"=>"187","typ"=>"A"),
	"J"=>array("srt"=>"190","typ"=>"A"),
	"K"=>array(
		"̂"=>array("srt"=>"201","typ"=>"A"),
		"srt"=>"200","typ"=>"A"),
	"L"=>array("srt"=>"210","typ"=>"A"),
	"M"=>array(
		"̂"=>array("srt"=>"221","typ"=>"A"),
		"srt"=>"220","typ"=>"A"),
	"N"=>array("srt"=>"230","typ"=>"A"),
	"O"=>array("srt"=>"240","typ"=>"A"),
	"P"=>array("srt"=>"250","typ"=>"A"),
	"Q"=>array("srt"=>"260","typ"=>"A"),
	"R"=>array("srt"=>"270","typ"=>"A"),
	"S"=>array("srt"=>"280","typ"=>"A"),
	"T"=>array("srt"=>"290","typ"=>"A"),
	"U"=>array("srt"=>"300","typ"=>"A"),
	"V"=>array("srt"=>"310","typ"=>"A"),
	"W"=>array("srt"=>"320","typ"=>"A"),
	"X"=>array("srt"=>"330","typ"=>"A"),
	"Y"=>array("srt"=>"340","typ"=>"A"),
	"Z"=>array("srt"=>"350","typ"=>"A"),
	"a"=>array(
	    "̣"=>array("srt"=>"106","csrt"=>"a","typ"=>"A"),
			"᷃"=>array("srt"=>"107","csrt"=>"r","typ"=>"A"),//use upsidedown tilde stands for r
      "̂"=>array("srt"=>"108","csrt"=>"a","typ"=>"A"),
      "srt"=>"105","csrt"=>"a","typ"=>"A"),
  "á"=>array("srt"=>"109","csrt"=>"a","typ"=>"A"),
  "à"=>array("srt"=>"109","csrt"=>"a","typ"=>"A"),
  "ạ"=>array("srt"=>"106","csrt"=>"a","typ"=>"A"),
	"â"=>array("srt"=>"108","csrt"=>"a","typ"=>"A"),
	"b"=>array(
		"̣"=>array("srt"=>"116","csrt"=>"b","typ"=>"A"),
		"srt"=>"115","typ"=>"A"),
	"ḅ"=>array("srt"=>"116","csrt"=>"b","typ"=>"A"),
	"c"=>array(
		"̣"=>array("srt"=>"126","csrt"=>"c","typ"=>"A"),
		"͞"=>array("srt"=>"127","csrt"=>"c","typ"=>"A"),
		"srt"=>"125","typ"=>"A"),
	"ĉ"=>array("srt"=>"127","csrt"=>"c","typ"=>"A"),
	"d"=>array(
		"̣"=>array("srt"=>"136","csrt"=>"d","typ"=>"A"),
		"͞"=>array("srt"=>"137","csrt"=>"d","typ"=>"A"),
		"̲"=>array("srt"=>"136","csrt"=>"d","typ"=>"A"),
		"͟"=>array("srt"=>"136","csrt"=>"d","typ"=>"A"),
		"srt"=>"135","typ"=>"A"),
	"ḍ"=>array("srt"=>"136","csrt"=>"d","typ"=>"A"),
	"e"=>array(
		"̂"=>array("srt"=>"148","csrt"=>"e","typ"=>"A"),
		"᷃"=>array("srt"=>"147","csrt"=>"e","typ"=>"A"),//use upsidedown tilde
    "̣"=>array("srt"=>"146","csrt"=>"e","typ"=>"A"),
		"̲"=>array("srt"=>"146","csrt"=>"e","typ"=>"A"),
		"͟"=>array("srt"=>"146","csrt"=>"e","typ"=>"A"),
    "srt"=>"145","csrt"=>"e","typ"=>"A"),
	"ẹ"=>array("srt"=>"146","csrt"=>"e","typ"=>"A"),
	"é"=>array("srt"=>"149","csrt"=>"e","typ"=>"A"),
	"ê"=>array("srt"=>"148","csrt"=>"e","typ"=>"A"),
	"f"=>array(
		"̣"=>array("srt"=>"156","csrt"=>"f","typ"=>"A"),
		"̂"=>array("srt"=>"158","csrt"=>"fe","typ"=>"A"),
		"̲"=>array("srt"=>"156","csrt"=>"f","typ"=>"A"),
		"͟"=>array("srt"=>"156","csrt"=>"f","typ"=>"A"),
		"srt"=>"155","typ"=>"A"),
	"g"=>array(
		"̣"=>array("srt"=>"166","csrt"=>"g","typ"=>"A"),
		"srt"=>"165","typ"=>"A"),
	"h"=>array(
		"̣"=>array("srt"=>"176","csrt"=>"h","typ"=>"A"),
		"srt"=>"175","typ"=>"A"),
	"ĥ"=>array("srt"=>"178","csrt"=>"h","typ"=>"A"),
	"ḥ"=>array("srt"=>"176","csrt"=>"h","typ"=>"A"),
	"i"=>array(
		"͞"=>array("srt"=>"187","csrt"=>"i","typ"=>"A"),
    "̣"=>array("srt"=>"186","csrt"=>"i","typ"=>"A"),
		"̲"=>array("srt"=>"186","csrt"=>"i","typ"=>"A"),
		"͟"=>array("srt"=>"186","csrt"=>"i","typ"=>"A"),
    "srt"=>"185","csrt"=>"i","typ"=>"A"),
	"ị"=>array("srt"=>"186","csrt"=>"i","typ"=>"A"),
	"í"=>array("srt"=>"189","csrt"=>"i","typ"=>"A"),
	"ì"=>array("srt"=>"189","csrt"=>"i","typ"=>"A"),
	"î"=>array("srt"=>"188","csrt"=>"i","typ"=>"A"),
	"ī"=>array("srt"=>"187","csrt"=>"i","typ"=>"A"),
	"j"=>array(
		"̣"=>array("srt"=>"196","csrt"=>"j","typ"=>"A"),
    "srt"=>"195","csrt"=>"j","typ"=>"A"),
	"ĵ"=>array("srt"=>"198","csrt"=>"j","typ"=>"A"),
	"j̣"=>array("srt"=>"196","csrt"=>"j","typ"=>"A"),
	"k"=>array(
		"̣"=>array("srt"=>"206","csrt"=>"k","typ"=>"A"),
    "srt"=>"205","csrt"=>"k","typ"=>"A"),
	"ḳ"=>array("srt"=>"206","csrt"=>"k","typ"=>"A"),
	"l"=>array(
		"̣"=>array("srt"=>"216","csrt"=>"l","typ"=>"A"),
		"͞"=>array("srt"=>"217","csrt"=>"i","typ"=>"A"),
		"̲"=>array("srt"=>"216","csrt"=>"f","typ"=>"A"),
		"͟"=>array("srt"=>"216","csrt"=>"f","typ"=>"A"),
    "srt"=>"215","csrt"=>"l","typ"=>"A"),
	"ḷ"=>array("srt"=>"216","csrt"=>"l","typ"=>"A"),
	"m"=>array(
		"̂"=>array("srt"=>"228","csrt"=>"m","typ"=>"A"),
		"̣"=>array("srt"=>"226","csrt"=>"m","typ"=>"A"),
		"͞"=>array("srt"=>"227","csrt"=>"i","typ"=>"A"),
		"̄"=>array("srt"=>"227","csrt"=>"m","typ"=>"A"),
		"srt"=>"225","csrt"=>"m","typ"=>"A"),
	"ṃ"=>array("srt"=>"226","csrt"=>"m","typ"=>"A"),
	"n"=>array(
		"̂"=>array("srt"=>"238","csrt"=>"n","typ"=>"A"),
    "̣"=>array("srt"=>"236","csrt"=>"n","typ"=>"A"),
		"srt"=>"235","csrt"=>"n","typ"=>"A"),
	"ṇ"=>array("srt"=>"236","csrt"=>"n","typ"=>"A"),
	"o"=>array(
		"̂"=>array("srt"=>"248","csrt"=>"o","typ"=>"A"),
		"̣"=>array("srt"=>"246","csrt"=>"o","typ"=>"A"),
		"srt"=>"245","csrt"=>"o","typ"=>"A"),
	"ó"=>array("srt"=>"249","csrt"=>"o","typ"=>"A"),
	"ọ"=>array("srt"=>"246","csrt"=>"o","typ"=>"A"),
	"ô"=>array("srt"=>"248","csrt"=>"o","typ"=>"A"),
	"p"=>array(
        "̣"=>array("srt"=>"256","csrt"=>"p","typ"=>"A"),
        "srt"=>"255","csrt"=>"p","typ"=>"A"),
	"q"=>array(
        "̣"=>array("srt"=>"266","csrt"=>"q","typ"=>"A"),
        "srt"=>"265","csrt"=>"q","typ"=>"A"),
	"q̣"=>array("srt"=>"266","csrt"=>"r","typ"=>"A"),
	"r"=>array(
        "̣"=>array("srt"=>"276","csrt"=>"r","typ"=>"A"),
				"̂"=>array("srt"=>"278","csrt"=>"m","typ"=>"A"),
				"srt"=>"275","csrt"=>"r","typ"=>"A"),
	"ṛ"=>array("srt"=>"276","csrt"=>"r","typ"=>"A"),
	"s"=>array(
		"̂"=>array("srt"=>"288","csrt"=>"s","typ"=>"A"),
		"̣"=>array("srt"=>"286","csrt"=>"s","typ"=>"A"),
		"̲"=>array("srt"=>"286","csrt"=>"s","typ"=>"A"),
		"͟"=>array("srt"=>"286","csrt"=>"s","typ"=>"A"),
		"srt"=>"285","csrt"=>"s","typ"=>"A"),
	"ŝ"=>array("srt"=>"288","csrt"=>"s","typ"=>"A"),
	"ṣ"=>array("srt"=>"286","csrt"=>"s","typ"=>"A"),
	"t"=>array(
		"̂"=>array("srt"=>"298","csrt"=>"n","typ"=>"A"),
		"̣"=>array("srt"=>"296","csrt"=>"t","typ"=>"A"),
		"srt"=>"295","csrt"=>"t","typ"=>"A"),
  "ṭ"=>array("srt"=>"296","csrt"=>"t","typ"=>"A"),
	"u"=>array(
        "̂"=>array("srt"=>"308","csrt"=>"u","typ"=>"A"),
        "̣"=>array("srt"=>"306","csrt"=>"u","typ"=>"A"),
				"̲"=>array("srt"=>"306","csrt"=>"u","typ"=>"A"),
				"͟"=>array("srt"=>"306","csrt"=>"u","typ"=>"A"),
				"srt"=>"305","csrt"=>"u","typ"=>"A"),
	"ú"=>array("srt"=>"309","csrt"=>"u","typ"=>"A"),
	"û"=>array("srt"=>"309","csrt"=>"u","typ"=>"A"),
	"ụ"=>array("srt"=>"306","csrt"=>"u","typ"=>"A"),
	"ⅎ"=>array("srt"=>"306","csrt"=>"u","typ"=>"A"),//claudiane letter
	"Ⅎ"=>array("srt"=>"306","csrt"=>"u","typ"=>"A"),//claudiane letter
	"ↄ"=>array("srt"=>"307","csrt"=>"u","typ"=>"A"),//claudiane letter
	"Ↄ"=>array("srt"=>"307","csrt"=>"u","typ"=>"A"),//claudiane letter
	"ⱶ"=>array("srt"=>"308","csrt"=>"u","typ"=>"A"),//claudiane letter
	"Ⱶ"=>array("srt"=>"308","csrt"=>"u","typ"=>"A"),//claudiane letter
	"v"=>array(
        "̂"=>array("srt"=>"318","csrt"=>"v","typ"=>"A"),
				"͞"=>array("srt"=>"317","csrt"=>"i","typ"=>"A"),
        "̣"=>array("srt"=>"316","csrt"=>"v","typ"=>"A"),
				"srt"=>"315","csrt"=>"v","typ"=>"A"),
  "ṿ"=>array("srt"=>"316","csrt"=>"v","typ"=>"A"),
	"w"=>array(
        ""=>array("srt"=>"329","csrt"=>"w","typ"=>"A"),
        "̣"=>array("srt"=>"326","csrt"=>"w","typ"=>"A"),
        "srt"=>"325","csrt"=>"w","typ"=>"A"),
	"ẉ"=>array("srt"=>"326","csrt"=>"w","typ"=>"A"),
	"x"=>array(
        "̣"=>array("srt"=>"336","csrt"=>"x","typ"=>"A"),
				"͞"=>array("srt"=>"337","csrt"=>"i","typ"=>"A"),
        "srt"=>"335","csrt"=>"x","typ"=>"A"),
	"y"=>array(
        "̣"=>array("srt"=>"346","csrt"=>"y","typ"=>"A"),
        "srt"=>"345","csrt"=>"y","typ"=>"A"),
	"ŷ"=>array("srt"=>"348","csrt"=>"y","typ"=>"A"),
	"ỵ"=>array("srt"=>"346","csrt"=>"y","typ"=>"A"),
	"z"=>array(
        "̣"=>array("srt"=>"356","csrt"=>"z","typ"=>"A"),
        "srt"=>"355","csrt"=>"z","typ"=>"A"),
  "ẓ"=>array("srt"=>"356","csrt"=>"z","typ"=>"A"),

// Greek
"Ἀ"=>array("srt"=>"400","typ"=>"A"),
"Ἁ"=>array("srt"=>"400","typ"=>"A"),
"Ἂ"=>array("srt"=>"400","typ"=>"A"),
"Ἃ"=>array("srt"=>"400","typ"=>"A"),
"Ἄ"=>array("srt"=>"400","typ"=>"A"),
"Ἅ"=>array("srt"=>"400","typ"=>"A"),
"Ἆ"=>array("srt"=>"400","typ"=>"A"),
"Ἇ"=>array("srt"=>"400","typ"=>"A"),
"ᾈ"=>array("srt"=>"400","typ"=>"A"),
"ᾉ"=>array("srt"=>"400","typ"=>"A"),
"ᾊ"=>array("srt"=>"400","typ"=>"A"),
"ᾋ"=>array("srt"=>"400","typ"=>"A"),
"ᾌ"=>array("srt"=>"400","typ"=>"A"),
"ᾍ"=>array("srt"=>"400","typ"=>"A"),
"ᾎ"=>array("srt"=>"400","typ"=>"A"),
"ᾏ"=>array("srt"=>"400","typ"=>"A"),
"Ᾰ"=>array("srt"=>"400","typ"=>"A"),
"Ᾱ"=>array("srt"=>"400","typ"=>"A"),
"Ὰ"=>array("srt"=>"400","typ"=>"A"),
"Ά"=>array("srt"=>"400","typ"=>"A"),
"ᾼ"=>array("srt"=>"400","typ"=>"A"),
"Α"=>array("srt"=>"400","typ"=>"A"),
"Β"=>array("srt"=>"410","typ"=>"A"),
"Γ"=>array("srt"=>"420","typ"=>"A"),
"Δ"=>array("srt"=>"430","typ"=>"A"),
"Ε"=>array("srt"=>"440","typ"=>"A"),
"Ἐ"=>array("srt"=>"440","typ"=>"A"),
"Ἑ"=>array("srt"=>"440","typ"=>"A"),
"Ἒ"=>array("srt"=>"440","typ"=>"A"),
"Ἓ"=>array("srt"=>"440","typ"=>"A"),
"Ἔ"=>array("srt"=>"440","typ"=>"A"),
"Ἕ"=>array("srt"=>"440","typ"=>"A"),
"Ὲ"=>array("srt"=>"440","typ"=>"A"),
"Έ"=>array("srt"=>"440","typ"=>"A"),
"Ζ"=>array("srt"=>"450","typ"=>"A"),
"Η"=>array("srt"=>"460","typ"=>"A"),
"Ἠ"=>array("srt"=>"460","typ"=>"A"),
"Ἡ"=>array("srt"=>"460","typ"=>"A"),
"Ὴ"=>array("srt"=>"460","typ"=>"A"),
"Ἢ"=>array("srt"=>"460","typ"=>"A"),
"Ἣ"=>array("srt"=>"460","typ"=>"A"),
"Ἤ"=>array("srt"=>"460","typ"=>"A"),
"Ἥ"=>array("srt"=>"460","typ"=>"A"),
"Ἦ"=>array("srt"=>"460","typ"=>"A"),
"Ἧ"=>array("srt"=>"460","typ"=>"A"),
"ᾘ"=>array("srt"=>"460","typ"=>"A"),
"ᾙ"=>array("srt"=>"460","typ"=>"A"),
"ᾚ"=>array("srt"=>"460","typ"=>"A"),
"ᾛ"=>array("srt"=>"460","typ"=>"A"),
"ᾜ"=>array("srt"=>"460","typ"=>"A"),
"ᾝ"=>array("srt"=>"460","typ"=>"A"),
"ᾞ"=>array("srt"=>"460","typ"=>"A"),
"ᾟ"=>array("srt"=>"460","typ"=>"A"),
"Ὴ"=>array("srt"=>"460","typ"=>"A"),
"Ή"=>array("srt"=>"460","typ"=>"A"),
"ῌ"=>array("srt"=>"460","typ"=>"A"),
"Θ"=>array("srt"=>"470","typ"=>"A"),
"Ι"=>array("srt"=>"480","typ"=>"A"),
"Ɩ"=>array("srt"=>"480","typ"=>"A"),//latin IOTA
"Ἰ"=>array("srt"=>"480","typ"=>"A"),
"Ἱ"=>array("srt"=>"480","typ"=>"A"),
"Ἲ"=>array("srt"=>"480","typ"=>"A"),
"Ἳ"=>array("srt"=>"480","typ"=>"A"),
"Ἴ"=>array("srt"=>"480","typ"=>"A"),
"Ἵ"=>array("srt"=>"480","typ"=>"A"),
"Ἶ"=>array("srt"=>"480","typ"=>"A"),
"Ἷ"=>array("srt"=>"480","typ"=>"A"),
"Ϊ"=>array("srt"=>"480","typ"=>"A"),
"Ῐ"=>array("srt"=>"480","typ"=>"A"),
"Ῑ"=>array("srt"=>"480","typ"=>"A"),
"Ὶ"=>array("srt"=>"480","typ"=>"A"),
"Ί"=>array("srt"=>"480","typ"=>"A"),
"Κ"=>array("srt"=>"490","typ"=>"A"),
"Λ"=>array("srt"=>"500","typ"=>"A"),
"Μ"=>array("srt"=>"510","typ"=>"A"),
"Ν"=>array("srt"=>"520","typ"=>"A"),
"Ξ"=>array("srt"=>"530","typ"=>"A"),
"Ο"=>array("srt"=>"540","typ"=>"A"),
"Ὀ"=>array("srt"=>"540","typ"=>"A"),
"Ὁ"=>array("srt"=>"540","typ"=>"A"),
"Ὂ"=>array("srt"=>"540","typ"=>"A"),
"Ὃ"=>array("srt"=>"540","typ"=>"A"),
"Ὄ"=>array("srt"=>"540","typ"=>"A"),
"Ὅ"=>array("srt"=>"540","typ"=>"A"),
"Ὸ"=>array("srt"=>"540","typ"=>"A"),
"Ό"=>array("srt"=>"540","typ"=>"A"),
"Π"=>array("srt"=>"550","typ"=>"A"),
"Ρ"=>array("srt"=>"560","typ"=>"A"),
"Ῥ"=>array("srt"=>"560","typ"=>"A"),
"Σ"=>array("srt"=>"570","typ"=>"A"),
"Ϲ"=>array("srt"=>"580","typ"=>"A"), #Lunate Sigma
"Τ"=>array("srt"=>"590","typ"=>"A"),
"Υ"=>array("srt"=>"600","typ"=>"A"),
"Ὑ"=>array("srt"=>"600","typ"=>"A"),
"Ὓ"=>array("srt"=>"600","typ"=>"A"),
"Ὕ"=>array("srt"=>"600","typ"=>"A"),
"Ὗ"=>array("srt"=>"600","typ"=>"A"),
"Ῠ"=>array("srt"=>"600","typ"=>"A"),
"Ῡ"=>array("srt"=>"600","typ"=>"A"),
"Ὺ"=>array("srt"=>"600","typ"=>"A"),
"Ύ"=>array("srt"=>"600","typ"=>"A"),
"Ϋ"=>array("srt"=>"600","typ"=>"A"),
"Φ"=>array("srt"=>"610","typ"=>"A"),
"Χ"=>array("srt"=>"620","typ"=>"A"),
"Ψ"=>array("srt"=>"630","typ"=>"A"),
"Ω"=>array("srt"=>"640","typ"=>"A"),
"Ὠ"=>array("srt"=>"640","typ"=>"A"),
"Ὡ"=>array("srt"=>"640","typ"=>"A"),
"Ὢ"=>array("srt"=>"640","typ"=>"A"),
"Ὣ"=>array("srt"=>"640","typ"=>"A"),
"Ὤ"=>array("srt"=>"640","typ"=>"A"),
"Ὥ"=>array("srt"=>"640","typ"=>"A"),
"Ὦ"=>array("srt"=>"640","typ"=>"A"),
"Ὧ"=>array("srt"=>"640","typ"=>"A"),
"ᾨ"=>array("srt"=>"640","typ"=>"A"),
"ᾩ"=>array("srt"=>"640","typ"=>"A"),
"ᾪ"=>array("srt"=>"640","typ"=>"A"),
"ᾫ"=>array("srt"=>"640","typ"=>"A"),
"ᾬ"=>array("srt"=>"640","typ"=>"A"),
"ᾭ"=>array("srt"=>"640","typ"=>"A"),
"ᾮ"=>array("srt"=>"640","typ"=>"A"),
"ᾯ"=>array("srt"=>"640","typ"=>"A"),
"Ὼ"=>array("srt"=>"640","typ"=>"A"),
"Ώ"=>array("srt"=>"640","typ"=>"A"),
"ῼ"=>array("srt"=>"640","typ"=>"A"),
"ἀ"=>array("srt"=>"405","typ"=>"A"),
"ἁ"=>array("srt"=>"405","typ"=>"A"),
"ἂ"=>array("srt"=>"405","typ"=>"A"),
"ἃ"=>array("srt"=>"405","typ"=>"A"),
"ἄ"=>array("srt"=>"405","typ"=>"A"),
"ἅ"=>array("srt"=>"405","typ"=>"A"),
"ἆ"=>array("srt"=>"405","typ"=>"A"),
"ἇ"=>array("srt"=>"405","typ"=>"A"),
"ὰ"=>array("srt"=>"405","typ"=>"A"),
"ά"=>array("srt"=>"405","typ"=>"A"),
"ά"=>array("srt"=>"405","typ"=>"A"),
"ᾀ"=>array("srt"=>"405","typ"=>"A"),
"ᾁ"=>array("srt"=>"405","typ"=>"A"),
"ᾂ"=>array("srt"=>"405","typ"=>"A"),
"ᾃ"=>array("srt"=>"405","typ"=>"A"),
"ᾄ"=>array("srt"=>"405","typ"=>"A"),
"ᾅ"=>array("srt"=>"405","typ"=>"A"),
"ᾆ"=>array("srt"=>"405","typ"=>"A"),
"ᾇ"=>array("srt"=>"405","typ"=>"A"),
"ᾰ"=>array("srt"=>"405","typ"=>"A"),
"ᾱ"=>array("srt"=>"405","typ"=>"A"),
"ᾲ"=>array("srt"=>"405","typ"=>"A"),
"ᾳ"=>array("srt"=>"405","typ"=>"A"),
"ᾴ"=>array("srt"=>"405","typ"=>"A"),
"ᾶ"=>array("srt"=>"405","typ"=>"A"),
"ᾷ"=>array("srt"=>"405","typ"=>"A"),  
"α"=>array("srt"=>"405","typ"=>"A"),
"β"=>array("srt"=>"415","typ"=>"A"),
"γ"=>array("srt"=>"425","typ"=>"A"),
"δ"=>array("srt"=>"435","typ"=>"A"),
"ε"=>array("srt"=>"445","typ"=>"A"),
"ἔ"=>array("srt"=>"445","typ"=>"A"),
"ἐ"=>array("srt"=>"445","typ"=>"A"),
"ἑ"=>array("srt"=>"445","typ"=>"A"),
"ἒ"=>array("srt"=>"445","typ"=>"A"),
"ἓ"=>array("srt"=>"445","typ"=>"A"),
"ἕ"=>array("srt"=>"445","typ"=>"A"),
"ὲ"=>array("srt"=>"445","typ"=>"A"),
"έ"=>array("srt"=>"445","typ"=>"A"),
"έ"=>array("srt"=>"445","typ"=>"A"),
"ζ"=>array("srt"=>"455","typ"=>"A"),
"η"=>array("srt"=>"465","typ"=>"A"),
"ἠ"=>array("srt"=>"465","typ"=>"A"),
"ἡ"=>array("srt"=>"465","typ"=>"A"),
"ἢ"=>array("srt"=>"465","typ"=>"A"),
"ἣ"=>array("srt"=>"465","typ"=>"A"),
"ἤ"=>array("srt"=>"465","typ"=>"A"),
"ἥ"=>array("srt"=>"465","typ"=>"A"),
"ἦ"=>array("srt"=>"465","typ"=>"A"),
"ἧ"=>array("srt"=>"465","typ"=>"A"),
"ὴ"=>array("srt"=>"465","typ"=>"A"),
"ή"=>array("srt"=>"465","typ"=>"A"),
"ή"=>array("srt"=>"465","typ"=>"A"),
"ᾐ"=>array("srt"=>"465","typ"=>"A"),
"ᾑ"=>array("srt"=>"465","typ"=>"A"),
"ᾒ"=>array("srt"=>"465","typ"=>"A"),
"ᾓ"=>array("srt"=>"465","typ"=>"A"),
"ᾔ"=>array("srt"=>"465","typ"=>"A"),
"ᾕ"=>array("srt"=>"465","typ"=>"A"),
"ᾖ"=>array("srt"=>"465","typ"=>"A"),
"ᾗ"=>array("srt"=>"465","typ"=>"A"),
"ῂ"=>array("srt"=>"465","typ"=>"A"),
"ῃ"=>array("srt"=>"465","typ"=>"A"),
"ῄ"=>array("srt"=>"465","typ"=>"A"),
"ῆ"=>array("srt"=>"465","typ"=>"A"),
"ῇ"=>array("srt"=>"465","typ"=>"A"),  
"θ"=>array("srt"=>"475","typ"=>"A"),
"ɩ"=>array("srt"=>"485","typ"=>"A"),
"ι"=>array("srt"=>"485","typ"=>"A"),
"ί"=>array("srt"=>"485","typ"=>"A"),
"ἰ"=>array("srt"=>"485","typ"=>"A"),
"ἱ"=>array("srt"=>"485","typ"=>"A"),
"ἲ"=>array("srt"=>"485","typ"=>"A"),
"ἳ"=>array("srt"=>"485","typ"=>"A"),
"ἴ"=>array("srt"=>"485","typ"=>"A"),
"ἵ"=>array("srt"=>"485","typ"=>"A"),
"ἶ"=>array("srt"=>"485","typ"=>"A"),
"ἷ"=>array("srt"=>"485","typ"=>"A"),
"ὶ"=>array("srt"=>"485","typ"=>"A"),
"ῐ"=>array("srt"=>"485","typ"=>"A"),
"ῑ"=>array("srt"=>"485","typ"=>"A"),
"ῒ"=>array("srt"=>"485","typ"=>"A"),
"ΐ"=>array("srt"=>"485","typ"=>"A"),
"ΐ"=>array("srt"=>"485","typ"=>"A"),
"ϊ"=>array("srt"=>"485","typ"=>"A"),
"ῖ"=>array("srt"=>"485","typ"=>"A"),
"ῗ"=>array("srt"=>"485","typ"=>"A"),
"κ"=>array("srt"=>"495","typ"=>"A"),
"λ"=>array("srt"=>"505","typ"=>"A"),
"μ"=>array("srt"=>"515","typ"=>"A"),
"ν"=>array("srt"=>"525","typ"=>"A"),
"ξ"=>array("srt"=>"535","typ"=>"A"),
"ο"=>array("srt"=>"545","typ"=>"A"),
"ὀ"=>array("srt"=>"545","typ"=>"A"),
"ὁ"=>array("srt"=>"545","typ"=>"A"),
"ὂ"=>array("srt"=>"545","typ"=>"A"),
"ὃ"=>array("srt"=>"545","typ"=>"A"),
"ὄ"=>array("srt"=>"545","typ"=>"A"),
"ὅ"=>array("srt"=>"545","typ"=>"A"),
"ὸ"=>array("srt"=>"545","typ"=>"A"),
"ό"=>array("srt"=>"545","typ"=>"A"),
"π"=>array("srt"=>"555","typ"=>"A"),
"ρ"=>array("srt"=>"565","typ"=>"A"),
"ῤ"=>array("srt"=>"565","typ"=>"A"),
"ῥ"=>array("srt"=>"565","typ"=>"A"),
"ς"=>array("srt"=>"575","typ"=>"A"),
"ϛ"=>array("srt"=>"575","typ"=>"A"),
"σ"=>array("srt"=>"585","typ"=>"A"),
"ϲ"=>array("srt"=>"585","typ"=>"A"), # may be sorting is 575
"τ"=>array("srt"=>"595","typ"=>"A"),
"υ"=>array("srt"=>"605","typ"=>"A"),
"ῦ"=>array("srt"=>"605","typ"=>"A"),
"ὐ"=>array("srt"=>"605","typ"=>"A"),
"ὑ"=>array("srt"=>"605","typ"=>"A"),
"ὒ"=>array("srt"=>"605","typ"=>"A"),
"ὓ"=>array("srt"=>"605","typ"=>"A"),
"ὔ"=>array("srt"=>"605","typ"=>"A"),
"ὕ"=>array("srt"=>"605","typ"=>"A"),
"ὖ"=>array("srt"=>"605","typ"=>"A"),
"ὗ"=>array("srt"=>"605","typ"=>"A"),
"ὺ"=>array("srt"=>"605","typ"=>"A"),
"ύ"=>array("srt"=>"605","typ"=>"A"),
"ύ"=>array("srt"=>"605","typ"=>"A"),
"ῠ"=>array("srt"=>"605","typ"=>"A"),
"ῡ"=>array("srt"=>"605","typ"=>"A"),
"ῢ"=>array("srt"=>"605","typ"=>"A"),
"ΰ"=>array("srt"=>"605","typ"=>"A"),
"ΰ"=>array("srt"=>"605","typ"=>"A"),
"ῧ"=>array("srt"=>"605","typ"=>"A"),
"ϋ"=>array("srt"=>"605","typ"=>"A"),
"φ"=>array("srt"=>"615","typ"=>"A"),
"ό"=>array("srt"=>"615","typ"=>"A"),
"χ"=>array("srt"=>"625","typ"=>"A"),
"ψ"=>array("srt"=>"635","typ"=>"A"),
"ω"=>array("srt"=>"645","typ"=>"A"),
"ὠ"=>array("srt"=>"645","typ"=>"A"),
"ὡ"=>array("srt"=>"645","typ"=>"A"),
"ὢ"=>array("srt"=>"645","typ"=>"A"),
"ὣ"=>array("srt"=>"645","typ"=>"A"),
"ὤ"=>array("srt"=>"645","typ"=>"A"),
"ὥ"=>array("srt"=>"645","typ"=>"A"),
"ὦ"=>array("srt"=>"645","typ"=>"A"),
"ὧ"=>array("srt"=>"645","typ"=>"A"),
"ὼ"=>array("srt"=>"645","typ"=>"A"),
"ώ"=>array("srt"=>"645","typ"=>"A"),
"ώ"=>array("srt"=>"645","typ"=>"A"),
"ᾠ"=>array("srt"=>"645","typ"=>"A"),
"ᾡ"=>array("srt"=>"645","typ"=>"A"),
"ᾢ"=>array("srt"=>"645","typ"=>"A"),
"ᾣ"=>array("srt"=>"645","typ"=>"A"),
"ᾤ"=>array("srt"=>"645","typ"=>"A"),
"ᾥ"=>array("srt"=>"645","typ"=>"A"),
"ᾦ"=>array("srt"=>"645","typ"=>"A"),
"ᾧ"=>array("srt"=>"645","typ"=>"A"),
"ῲ"=>array("srt"=>"645","typ"=>"A"),
"ῳ"=>array("srt"=>"645","typ"=>"A"),
"ῴ"=>array("srt"=>"645","typ"=>"A"),
"ῶ"=>array("srt"=>"645","typ"=>"A"),
"ῷ"=>array("srt"=>"645","typ"=>"A"),
"ὠ"=>array("srt"=>"645","typ"=>"A"),

//coptic
"ⲁ"=>array(
  "͞"=>array("srt"=>"406","typ"=>"A"),
  "̄"=>array("srt"=>"406","typ"=>"A"),
  "̅"=>array("srt"=>"406","typ"=>"A"),
  "srt"=>"406","typ"=>"A"),
"ⲃ"=>array(
  "͞"=>array("srt"=>"416","typ"=>"A"),
  "̄"=>array("srt"=>"416","typ"=>"A"),
  "̅"=>array("srt"=>"416","typ"=>"A"),
  "srt"=>"416","typ"=>"A"),
"ⲅ"=>array(
  "͞"=>array("srt"=>"426","typ"=>"A"),
  "̄"=>array("srt"=>"426","typ"=>"A"),
  "̅"=>array("srt"=>"426","typ"=>"A"),
  "srt"=>"426","typ"=>"A"),
"ⲇ"=>array(
  "͞"=>array("srt"=>"436","typ"=>"A"),
  "̄"=>array("srt"=>"436","typ"=>"A"),
  "̅"=>array("srt"=>"436","typ"=>"A"),
  "srt"=>"436","typ"=>"A"),
"ⲉ"=>array(
  "͞"=>array("srt"=>"446","typ"=>"A"),
  "̄"=>array("srt"=>"446","typ"=>"A"),
  "̅"=>array("srt"=>"446","typ"=>"A"),
  "srt"=>"446","typ"=>"A"),
"ⲋ"=>array(
  "͞"=>array("srt"=>"447","typ"=>"A"),
  "̄"=>array("srt"=>"447","typ"=>"A"),
  "̅"=>array("srt"=>"447","typ"=>"A"),
  "srt"=>"447","typ"=>"A"),
"ⲍ"=>array(
  "͞"=>array("srt"=>"456","typ"=>"A"),
  "̄"=>array("srt"=>"456","typ"=>"A"),
  "̅"=>array("srt"=>"456","typ"=>"A"),
  "srt"=>"456","typ"=>"A"),
"ⲏ"=>array(
  "͞"=>array("srt"=>"466","typ"=>"A"),
  "̄"=>array("srt"=>"466","typ"=>"A"),
  "̅"=>array("srt"=>"466","typ"=>"A"),
  "srt"=>"466","typ"=>"A"),
"ⲑ"=>array(
  "͞"=>array("srt"=>"476","typ"=>"A"),
  "̄"=>array("srt"=>"476","typ"=>"A"),
  "̅"=>array("srt"=>"476","typ"=>"A"),
  "srt"=>"476","typ"=>"A"),
"ⲓ"=>array(
  "͞"=>array("srt"=>"486","typ"=>"A"),
  "̄"=>array("srt"=>"486","typ"=>"A"),
  "̅"=>array("srt"=>"486","typ"=>"A"),
  "srt"=>"486","typ"=>"A"),
"ⲕ"=>array(
  "͞"=>array("srt"=>"496","typ"=>"A"),
  "̄"=>array("srt"=>"496","typ"=>"A"),
  "̅"=>array("srt"=>"496","typ"=>"A"),
  "srt"=>"496","typ"=>"A"),
"ⲗ"=>array(
  "͞"=>array("srt"=>"506","typ"=>"A"),
  "̄"=>array("srt"=>"506","typ"=>"A"),
  "̅"=>array("srt"=>"506","typ"=>"A"),
  "srt"=>"506","typ"=>"A"),
"ⲙ"=>array(
  "͞"=>array("srt"=>"516","typ"=>"A"),
  "̄"=>array("srt"=>"516","typ"=>"A"),
  "̅"=>array("srt"=>"516","typ"=>"A"),
  "srt"=>"516","typ"=>"A"),
"ⲛ"=>array(
  "͞"=>array("srt"=>"526","typ"=>"A"),
  "̄"=>array("srt"=>"526","typ"=>"A"),
  "̅"=>array("srt"=>"526","typ"=>"A"),
  "srt"=>"526","typ"=>"A"),
"ⲝ"=>array(
  "͞"=>array("srt"=>"536","typ"=>"A"),
  "̄"=>array("srt"=>"536","typ"=>"A"),
  "̅"=>array("srt"=>"536","typ"=>"A"),
  "srt"=>"536","typ"=>"A"),
"ⲟ"=>array(
  "͞"=>array("srt"=>"546","typ"=>"A"),
  "̄"=>array("srt"=>"546","typ"=>"A"),
  "̅"=>array("srt"=>"546","typ"=>"A"),
  "srt"=>"546","typ"=>"A"),
"ⲡ"=>array(
  "͞"=>array("srt"=>"556","typ"=>"A"),
  "̄"=>array("srt"=>"556","typ"=>"A"),
  "̅"=>array("srt"=>"556","typ"=>"A"),
  "srt"=>"556","typ"=>"A"),
"ⲣ"=>array(
  "͞"=>array("srt"=>"566","typ"=>"A"),
  "̄"=>array("srt"=>"566","typ"=>"A"),
  "̅"=>array("srt"=>"566","typ"=>"A"),
  "srt"=>"566","typ"=>"A"),
"ⲥ"=>array(
  "͞"=>array("srt"=>"576","typ"=>"A"),
  "̄"=>array("srt"=>"576","typ"=>"A"),
  "̅"=>array("srt"=>"576","typ"=>"A"),
  "srt"=>"576","typ"=>"A"),
"ⲧ"=>array(
  "͞"=>array("srt"=>"596","typ"=>"A"),
  "̄"=>array("srt"=>"596","typ"=>"A"),
  "̅"=>array("srt"=>"596","typ"=>"A"),
  "srt"=>"596","typ"=>"A"),
"ⲩ"=>array(
  "͞"=>array("srt"=>"606","typ"=>"A"),
  "̄"=>array("srt"=>"606","typ"=>"A"),
  "̅"=>array("srt"=>"606","typ"=>"A"),
  "srt"=>"606","typ"=>"A"),
"ⲫ"=>array(
  "͞"=>array("srt"=>"616","typ"=>"A"),
  "̄"=>array("srt"=>"616","typ"=>"A"),
  "̅"=>array("srt"=>"616","typ"=>"A"),
  "srt"=>"616","typ"=>"A"),
"ⲭ"=>array(
  "͞"=>array("srt"=>"626","typ"=>"A"),
  "̄"=>array("srt"=>"626","typ"=>"A"),
  "̅"=>array("srt"=>"626","typ"=>"A"),
  "srt"=>"626","typ"=>"A"),
"ⲯ"=>array(
  "͞"=>array("srt"=>"636","typ"=>"A"),
  "̄"=>array("srt"=>"636","typ"=>"A"),
  "̅"=>array("srt"=>"636","typ"=>"A"),
  "srt"=>"636","typ"=>"A"),
"ⲱ"=>array(
  "͞"=>array("srt"=>"646","typ"=>"A"),
  "̄"=>array("srt"=>"646","typ"=>"A"),
  "̅"=>array("srt"=>"646","typ"=>"A"),
  "srt"=>"646","typ"=>"A"),
"ϣ"=>array(
  "͞"=>array("srt"=>"650","typ"=>"A"),
  "̄"=>array("srt"=>"650","typ"=>"A"),
  "̅"=>array("srt"=>"650","typ"=>"A"),
  "srt"=>"650","typ"=>"A"),
"ϥ"=>array(
  "͞"=>array("srt"=>"655","typ"=>"A"),
  "̄"=>array("srt"=>"655","typ"=>"A"),
  "̅"=>array("srt"=>"655","typ"=>"A"),
  "srt"=>"655","typ"=>"A"),
"ϧ"=>array(
  "͞"=>array("srt"=>"660","typ"=>"A"),
  "̄"=>array("srt"=>"660","typ"=>"A"),
  "̅"=>array("srt"=>"660","typ"=>"A"),
  "srt"=>"660","typ"=>"A"),
"ϩ"=>array(
  "͞"=>array("srt"=>"670","typ"=>"A"),
  "̄"=>array("srt"=>"670","typ"=>"A"),
  "̅"=>array("srt"=>"670","typ"=>"A"),
  "srt"=>"670","typ"=>"A"),
"ϫ"=>array(
  "͞"=>array("srt"=>"680","typ"=>"A"),
  "̄"=>array("srt"=>"680","typ"=>"A"),
  "̅"=>array("srt"=>"680","typ"=>"A"),
  "srt"=>"680","typ"=>"A"),
"ϭ"=>array(
  "͞"=>array("srt"=>"685","typ"=>"A"),
  "̄"=>array("srt"=>"685","typ"=>"A"),
  "̅"=>array("srt"=>"685","typ"=>"A"),
  "srt"=>"685","typ"=>"A"),
"ϯ"=>array(
  "͞"=>array("srt"=>"690","typ"=>"A"),
  "̄"=>array("srt"=>"690","typ"=>"A"),
  "̅"=>array("srt"=>"690","typ"=>"A"),
  "srt"=>"690","typ"=>"A"),
"ⳁ"=>array(
  "͞"=>array("srt"=>"695","typ"=>"A"),
  "̄"=>array("srt"=>"695","typ"=>"A"),
  "̅"=>array("srt"=>"695","typ"=>"A"),
  "srt"=>"695","typ"=>"A"),

// Hebrew
	"א"=>array("srt"=>"365","typ"=>"A"), //ALEF
	"ב"=>array("srt"=>"365","typ"=>"A"), //BET
	"ג"=>array("srt"=>"365","typ"=>"A"), //GIMEL
	"ד"=>array("srt"=>"365","typ"=>"A"), //DALET
	"ה"=>array("srt"=>"365","typ"=>"A"), //HE
	"ו"=>array("srt"=>"365","typ"=>"A"), //VAV
	"ז"=>array("srt"=>"365","typ"=>"A"), //ZAYIN
	"ח"=>array("srt"=>"365","typ"=>"A"), //HET
	"ט"=>array("srt"=>"365","typ"=>"A"), //TET
	"י"=>array("srt"=>"365","typ"=>"A"), //YOD
	"ך"=>array("srt"=>"365","typ"=>"A"), //KAF FINAL
	"כ"=>array("srt"=>"365","typ"=>"A"), //KAF
	"ל"=>array("srt"=>"365","typ"=>"A"), //LAMED
	"ם"=>array("srt"=>"365","typ"=>"A"), //MEM FINAL
	"מ"=>array("srt"=>"365","typ"=>"A"), //MEM
	"ן"=>array("srt"=>"365","typ"=>"A"), //NUN FINAL
	"נ"=>array("srt"=>"365","typ"=>"A"), //NUN
	"ס"=>array("srt"=>"365","typ"=>"A"), //SAMEKH
	"ע"=>array("srt"=>"365","typ"=>"A"), //AYIN
	"ף"=>array("srt"=>"365","typ"=>"A"), //PE FINAL
	"פ"=>array("srt"=>"365","typ"=>"A"), //PE
	"ץ"=>array("srt"=>"365","typ"=>"A"), //TSADI FINAL
	"צ"=>array("srt"=>"365","typ"=>"A"), //TSADI
	"ק"=>array("srt"=>"365","typ"=>"A"), //QOF
	"ר"=>array("srt"=>"365","typ"=>"A"), //RESH
	"ש"=>array("srt"=>"365","typ"=>"A"), //SHIN
	"ת"=>array("srt"=>"365","typ"=>"A"), //TAV

// Arabic
"ا"=>array(
  "ٔ"=>array("srt"=>"110","typ"=>"A"),
  "ٕ"=>array("srt"=>"120","typ"=>"A"),
  "ٓ"=>array("srt"=>"130","typ"=>"A"),
  "srt"=>"100","typ"=>"A"),
"أ"=>array(
  "َ"=>array("srt"=>"111","typ"=>"A"),
  "ُ"=>array("srt"=>"112","typ"=>"A"),
  "srt"=>"110","typ"=>"A"),
"إ"=>array(
  "ِ"=>array("srt"=>"121","typ"=>"A"),
  "srt"=>"120","typ"=>"A"),
"آ"=>array("srt"=>"130","typ"=>"A"),
"ب"=>array("srt"=>"140","typ"=>"A"),
"ت"=>array(
  "َ"=>array(
    "ّ"=>array("srt"=>"151","typ"=>"A")),
  "ّ"=>array(
    "َ"=>array("srt"=>"151","typ"=>"A")),
  "srt"=>"150","typ"=>"A"),
"ث"=>array("srt"=>"160","typ"=>"A"),
"پ"=>array("srt"=>"170","typ"=>"A"),
"ﭘ"=>array("srt"=>"170","typ"=>"A"),
"ٮ"=>array("srt"=>"180","typ"=>"A"),
"ج"=>array("srt"=>"190","typ"=>"A"),
"ح"=>array("srt"=>"200","typ"=>"A"),
"خ"=>array("srt"=>"210","typ"=>"A"),
"د"=>array("srt"=>"220","typ"=>"A"),
"ذ"=>array("srt"=>"230","typ"=>"A"),
"ر"=>array("srt"=>"240","typ"=>"A"),
"ز"=>array("srt"=>"250","typ"=>"A"),
"س"=>array("srt"=>"260","typ"=>"A"),
"ش"=>array("srt"=>"270","typ"=>"A"),
"ص"=>array("srt"=>"280","typ"=>"A"),
"ض"=>array("srt"=>"290","typ"=>"A"),
"ط"=>array("srt"=>"300","typ"=>"A"),
"ظ"=>array("srt"=>"310","typ"=>"A"),
"ع"=>array("srt"=>"320","typ"=>"A"),
"غ"=>array("srt"=>"330","typ"=>"A"),
"ڤ"=>array("srt"=>"340","typ"=>"A"),
"ف"=>array("srt"=>"350","typ"=>"A"),
"ق"=>array("srt"=>"360","typ"=>"A"),
"ڡ"=>array("srt"=>"370","typ"=>"A"),
"ك"=>array("srt"=>"380","typ"=>"A"),
"ل"=>array("srt"=>"390","typ"=>"A"),
"م"=>array("srt"=>"400","typ"=>"A"),
"ن"=>array("srt"=>"410","typ"=>"A"),
"ه"=>array("srt"=>"420","typ"=>"A"),
"و"=>array(
  "ٔ"=>array("srt"=>"440","typ"=>"A"),
  "srt"=>"430","typ"=>"A"),
"ؤ"=>array("srt"=>"440","typ"=>"A"),
"ى"=>array(
  "ٔ"=>array("srt"=>"470","typ"=>"A"),
  "srt"=>"450","typ"=>"A"),
"ي"=>array("srt"=>"460","typ"=>"A"),
"ئ"=>array("srt"=>"470","typ"=>"A"),
"ے"=>array("srt"=>"480","typ"=>"A"),
"ـ"=>array("srt"=>"911","typ"=>"A"),
"ء"=>array("srt"=>"930","typ"=>"A"),
"٠"=>array("srt"=>"701","typ"=>"N"),
"١"=>array("srt"=>"711","typ"=>"N"),
"٢"=>array("srt"=>"721","typ"=>"N"),
"٣"=>array("srt"=>"731","typ"=>"N"),
"٤"=>array("srt"=>"741","typ"=>"N"),
"٥"=>array("srt"=>"751","typ"=>"N"),
"٦"=>array("srt"=>"761","typ"=>"N"),
"٧"=>array("srt"=>"771","typ"=>"N"),
"٨"=>array("srt"=>"781","typ"=>"N"),
"٩"=>array("srt"=>"791","typ"=>"N"),
"ً"=>array("srt"=>"365","typ"=>"D"),
"ٌ"=>array("srt"=>"365","typ"=>"D"),
"ٍ"=>array("srt"=>"365","typ"=>"D"),
"َ"=>array("srt"=>"365","typ"=>"D"),
"ُ"=>array("srt"=>"365","typ"=>"D"),
"ِ"=>array("srt"=>"365","typ"=>"D"),
"ّ"=>array("srt"=>"365","typ"=>"D"),
"ْ"=>array("srt"=>"365","typ"=>"D"),
"ٓ"=>array("srt"=>"365","typ"=>"D"),
"ٔ"=>array("srt"=>"365","typ"=>"D"),
"ٕ"=>array("srt"=>"365","typ"=>"D"),
"ٖ"=>array("srt"=>"365","typ"=>"D"),

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
* S(C)→C(V)→CV(VM)→CVVM(~VM)→S
* S(C)→C(.)→C.(VM)→C.VM(~VM)→S
* S(C)→C(.)→C.(~VM)→S
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
* A(M)→E
* A(~M)→S
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
