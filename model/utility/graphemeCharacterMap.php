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
	// Punctuation
	","=>array("srt"=>"800","typ"=>"P"),
	"."=>array("srt"=>"810","typ"=>"P"),
	";"=>array("srt"=>"820","typ"=>"P"),
	":"=>array("srt"=>"830","typ"=>"P"),
	"!"=>array("srt"=>"840","typ"=>"P"),
	"?"=>array("srt"=>"850","typ"=>"P"),
	"("=>array("srt"=>"860","typ"=>"P"), //Open - space before
	")"=>array("srt"=>"870","typ"=>"P"), //Close - space after
	"«"=>array("srt"=>"880","typ"=>"P"), //Open - space before
	"»"=>array("srt"=>"890","typ"=>"P"), //Close - space after
	// Other Symbols
	"+"=>array("srt"=>"900","typ"=>"O"), //Placeholder, not yet observed in data
	// Alphabetic signs
	"A"=>array("srt"=>"100","typ"=>"A"),
	"B"=>array("srt"=>"110","typ"=>"A"),
	"C"=>array("srt"=>"120","typ"=>"A"),
	"D"=>array("srt"=>"130","typ"=>"A"),
	"E"=>array("srt"=>"140","typ"=>"A"),
	"F"=>array("srt"=>"150","typ"=>"A"),
	"G"=>array("srt"=>"160","typ"=>"A"),
	"H"=>array("srt"=>"170","typ"=>"A"),
	"I"=>array("srt"=>"180","typ"=>"A"),
	"J"=>array("srt"=>"190","typ"=>"A"),
	"K"=>array("srt"=>"200","typ"=>"A"),
	"L"=>array("srt"=>"210","typ"=>"A"),
	"M"=>array("srt"=>"220","typ"=>"A"),
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
	"a"=>array("srt"=>"105","typ"=>"A"),
	"b"=>array("srt"=>"115","typ"=>"A"),
	"c"=>array("srt"=>"125","typ"=>"A"),
	"d"=>array("srt"=>"135","typ"=>"A"),
	"e"=>array("srt"=>"145","typ"=>"A"),
	"f"=>array("srt"=>"155","typ"=>"A"),
	"g"=>array("srt"=>"165","typ"=>"A"),
	"h"=>array("srt"=>"175","typ"=>"A"),
	"i"=>array("srt"=>"185","typ"=>"A"),
	"j"=>array("srt"=>"195","typ"=>"A"),
	"k"=>array("srt"=>"205","typ"=>"A"),
	"l"=>array("srt"=>"215","typ"=>"A"),
	"m"=>array("srt"=>"225","typ"=>"A"),
	"n"=>array("srt"=>"235","typ"=>"A"),
	"o"=>array("srt"=>"245","typ"=>"A"),
	"p"=>array("srt"=>"255","typ"=>"A"),
	"q"=>array("srt"=>"265","typ"=>"A"),
	"r"=>array("srt"=>"275","typ"=>"A"),
	"s"=>array("srt"=>"285","typ"=>"A"),
	"t"=>array("srt"=>"295","typ"=>"A"),
	"u"=>array("srt"=>"305","typ"=>"A"),
	"v"=>array("srt"=>"315","typ"=>"A"),
	"w"=>array("srt"=>"325","typ"=>"A"),
	"x"=>array("srt"=>"335","typ"=>"A"),
	"y"=>array("srt"=>"345","typ"=>"A"),
	"z"=>array("srt"=>"355","typ"=>"A"),
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
*        D = Digit, E = Error and . = missing C or V
* S(C)→C(C)→CC(V)→CCV(~VM)→S
* S(C)→C(C)→CC(V)→CCV(VM)→VM(~VM)→S
* S(C)→C(C)→CC(.)→CC.(~VM)→S
* S(C)→C(C)→CC(.)→CC.(VM)→VM(~VM)→S
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
* S(D)→D(~VM)→S
* S→*→.→E(.)
* S→*→VM→E(VM)
* S→E(VM)
* S→CC→E(VM|P|D|.|C)
*
* Flatten Transissions
* S(C)→C
* S(V)→V
* S(.)→V
* S(P)→P
* S(D)→D
* S(O)→O
* C(C)→C
* C(_)→C
* C(V)→V
* C(.)→V
* V(~M)→S
* V(M)→M
* M(~M)→S
* P(~M)→S
* D(~M)→S
* O(~M)→S
* M(M)→E
* P(M)→E
* D(M)→E
* O(M)→E
* M(M)→E
* S(M)→E
* C(M|P|D|O)→E
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
      if ($nextType == "V" || $nextType == "C") return $nextType;
      return "E";
      break;
    case "V"://vowel
      if ($nextType == "M") return "M";
      return "S";
      break;
    case "M"://vowel modifier
//      if ($nextType == "M") return "M";//allow multiple modifiers
      return $nextType;
      break;
    case "I"://IntraSyllable
    case "P"://Punctuation
    case "N"://Digit
    case "O"://Other
      if ($nextType == "M") return "E";
      return "S";
      break;
    default:
      return "E";
  }
}

?>
