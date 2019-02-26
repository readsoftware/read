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
* @copyright   Stephen White  <stephenawhite57@gmail.com>
* @link        https://github.com/stevewh/readlatin
* @version     0.1
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
"0"=>array("srt"=>"700","ssrt"=>"700","typ"=>"N"),
"½"=>array("srt"=>"705","ssrt"=>"705","typ"=>"N"),
"1"=>array(
    "srt"=>"710","ssrt"=>"710","typ"=>"N",
    "0"=>array( "srt"=>"760","ssrt"=>"760","typ"=>"N",
        "0"=>array( "srt"=>"780","ssrt"=>"780","typ"=>"N",
            "0"=>array("srt"=>"790","ssrt"=>"790","typ"=>"N")))),
"2"=>array("srt"=>"720","ssrt"=>"720","typ"=>"N",
    "0"=>array("srt"=>"770","ssrt"=>"770","typ"=>"N")),
"3"=>array("srt"=>"730","ssrt"=>"730","typ"=>"N",
    "0"=>array("srt"=>"773","ssrt"=>"773","typ"=>"N")),
"4"=>array("srt"=>"740","ssrt"=>"740","typ"=>"N",
    "0"=>array("srt"=>"774","ssrt"=>"774","typ"=>"N")),
"5"=>array("srt"=>"755","ssrt"=>"755","typ"=>"N",
    "0"=>array("srt"=>"775","ssrt"=>"775","typ"=>"N")),
"6"=>array("srt"=>"756","ssrt"=>"756","typ"=>"N",
    "0"=>array("srt"=>"776","ssrt"=>"776","typ"=>"N")),
"7"=>array("srt"=>"757","ssrt"=>"757","typ"=>"N",
    "0"=>array("srt"=>"777","ssrt"=>"777","typ"=>"N")),
"8"=>array("srt"=>"758","ssrt"=>"758","typ"=>"N",
    "0"=>array("srt"=>"778","ssrt"=>"778","typ"=>"N")),
"9"=>array("srt"=>"759","ssrt"=>"759","typ"=>"N",
    "0"=>array("srt"=>"779","ssrt"=>"779","typ"=>"N")),
"‧"=>array("srt"=>"800","ssrt"=>"800","typ"=>"P"),
"×"=>array("srt"=>"801","ssrt"=>"801","typ"=>"P"),
"∈"=>array("srt"=>"830","ssrt"=>"830","typ"=>"P"),
"⌇"=>array("srt"=>"880","ssrt"=>"880","typ"=>"P"),
"◊"=>array("srt"=>"885","ssrt"=>"885","typ"=>"I"),
"◈"=>array("srt"=>"885","ssrt"=>"885","typ"=>"I"),//bark obstruction =>space
"○"=>array("srt"=>"820","ssrt"=>"820","typ"=>"P"),
"⊗"=>array("srt"=>"822","ssrt"=>"822","typ"=>"P"),
"◎"=>array("srt"=>"823","ssrt"=>"823","typ"=>"P"),
"◦"=>array("srt"=>"810","ssrt"=>"810","typ"=>"P"),
"•"=>array("srt"=>"805","ssrt"=>"805","typ"=>"P"),
"∙"=>array("srt"=>"804","ssrt"=>"804","typ"=>"I"),//
"☒"=>array("srt"=>"845","ssrt"=>"845","typ"=>"P"),
"☸"=>array("srt"=>"840","ssrt"=>"840","typ"=>"P"),
"❀"=>array("srt"=>"851","ssrt"=>"851","typ"=>"P"),
"⁝"=>array("srt"=>"806","ssrt"=>"806","typ"=>"P"),
"⏑"=>array("srt"=>"807","ssrt"=>"807","typ"=>"P"),
"⎼"=>array("srt"=>"808","ssrt"=>"808","typ"=>"P"),
","=>array("srt"=>"850","ssrt"=>"850","typ"=>"P"),
"–"=>array("srt"=>"890","ssrt"=>"890","typ"=>"P"),
"—"=>array("srt"=>"890","ssrt"=>"890","typ"=>"P"),
"|"=>array(
    "|"=>array("srt"=>"870","ssrt"=>"870","typ"=>"I"),
    "srt"=>"860","ssrt"=>"860","typ"=>"I"),
"◯"=>array("srt"=>"821","ssrt"=>"821","typ"=>"I"),
":"=>array("srt"=>"803","ssrt"=>"803","typ"=>"P"),
"*"=>array("srt"=>"099","ssrt"=>"099","typ"=>"I"),
"·"=>array("srt"=>"000","ssrt"=>"099","typ"=>"I"),
"."=>array("srt"=>"189","ssrt"=>"189","typ"=>"I"),
"°"=>array("srt"=>"195","ssrt"=>"195","typ"=>"I"),
"?"=>array("srt"=>"990","ssrt"=>"990","typ"=>"O"),
"+"=>array("srt"=>"953","ssrt"=>"953","typ"=>"O"),
"-"=>array(
    "-"=>array(
        "-"=>array("srt"=>"954","ssrt"=>"954","typ"=>"O")),
    "srt"=>"950","ssrt"=>"950","typ"=>"O"), // debug temp assiqnment remove after / clean up
"#"=>array("srt"=>"956","ssrt"=>"956","typ"=>"O"),
"…"=>array("srt"=>"955","ssrt"=>"955","typ"=>"O"),
";’"=>array("srt"=>"851","ssrt"=>"851","typ"=>"p"),
"a"=>array("srt"=>"410","csrt"=>"a","typ"=>"I"),
"á"=>array("srt"=>"410","csrt"=>"a","typ"=>"I"),
"â"=>array("srt"=>"410","csrt"=>"a","typ"=>"I"),
"ạ"=>array("srt"=>"410","csrt"=>"a","typ"=>"I"),
"b"=>array("srt"=>"420","csrt"=>"b","typ"=>"I"),
"ḅ"=>array("srt"=>"420","csrt"=>"b","typ"=>"I"),
"c"=>array("srt"=>"430","csrt"=>"c","typ"=>"I"),
"ĉ"=>array("srt"=>"430","csrt"=>"c","typ"=>"I"),
"c̣"=>array("srt"=>"430","csrt"=>"c","typ"=>"I"),
"d"=>array("srt"=>"440","csrt"=>"d","typ"=>"I"),
"ḍ"=>array("srt"=>"440","csrt"=>"d","typ"=>"I"),
"e"=>array(
    "̂"=>array("srt"=>"450","csrt"=>"e","typ"=>"I"),
    "̣"=>array("srt"=>"450","csrt"=>"e","typ"=>"I"),
    "srt"=>"450","csrt"=>"e","typ"=>"I"),
"é"=>array("srt"=>"450","csrt"=>"e","typ"=>"I"),
"ê"=>array("srt"=>"450","csrt"=>"e","typ"=>"I"),
"f"=>array("srt"=>"460","csrt"=>"f","typ"=>"I"),
"f̣"=>array("srt"=>"460","csrt"=>"f","typ"=>"I"),
"g"=>array("srt"=>"470","csrt"=>"g","typ"=>"I"),
"ĝ"=>array("srt"=>"470","csrt"=>"g","typ"=>"I"),
"g̣"=>array("srt"=>"470","csrt"=>"g","typ"=>"I"),
"h"=>array("srt"=>"480","csrt"=>"h","typ"=>"I"),
"ĥ"=>array("srt"=>"480","csrt"=>"h","typ"=>"I"),
"ḥ"=>array("srt"=>"480","csrt"=>"h","typ"=>"I"),
"i"=>array(
    "͞"=>array("srt"=>"530","csrt"=>"m","typ"=>"I"),
    "srt"=>"490","csrt"=>"i","typ"=>"I"),
"í"=>array("srt"=>"490","csrt"=>"i","typ"=>"I"),
"î"=>array("srt"=>"490","csrt"=>"i","typ"=>"I"),
"ī"=>array("srt"=>"490","csrt"=>"i","typ"=>"I"),
"ị"=>array("srt"=>"490","csrt"=>"i","typ"=>"I"),
"j"=>array("srt"=>"500","csrt"=>"j","typ"=>"I"),
"ĵ"=>array("srt"=>"500","csrt"=>"j","typ"=>"I"),
"j̣"=>array("srt"=>"500","csrt"=>"j","typ"=>"I"),
"k"=>array("srt"=>"510","csrt"=>"k","typ"=>"I"),
"ḳ"=>array("srt"=>"510","csrt"=>"k","typ"=>"I"),
"l"=>array("srt"=>"520","csrt"=>"l","typ"=>"I"),
"ḷ"=>array("srt"=>"520","csrt"=>"l","typ"=>"I"),
"m"=>array(
    "̂"=>array("srt"=>"530","csrt"=>"m","typ"=>"I"),
    "̣"=>array("srt"=>"530","csrt"=>"m","typ"=>"I"),
    "srt"=>"530","csrt"=>"m","typ"=>"I"),
"n"=>array("srt"=>"540","csrt"=>"n","typ"=>"I"),
"ṇ"=>array("srt"=>"540","csrt"=>"n","typ"=>"I"),
"o"=>array(
    "̂"=>array("srt"=>"550","csrt"=>"o","typ"=>"I"),
    "̣"=>array("srt"=>"550","csrt"=>"o","typ"=>"I"),
    "srt"=>"550","csrt"=>"o","typ"=>"I"),
"ó"=>array("srt"=>"550","csrt"=>"o","typ"=>"I"),
"ô"=>array("srt"=>"550","csrt"=>"o","typ"=>"I"),
"p"=>array("srt"=>"560","csrt"=>"p","typ"=>"I"),
"p̣"=>array("srt"=>"560","csrt"=>"p","typ"=>"I"),
"q"=>array("srt"=>"570","csrt"=>"q","typ"=>"I"),
"q̣"=>array("srt"=>"570","csrt"=>"q","typ"=>"I"),
"r"=>array("srt"=>"580","csrt"=>"r","typ"=>"I"),
"ṛ"=>array("srt"=>"580","csrt"=>"r","typ"=>"I"),
"s"=>array("srt"=>"590","csrt"=>"s","typ"=>"I"),
"ŝ"=>array("srt"=>"590","csrt"=>"s","typ"=>"I"),
"ṣ"=>array("srt"=>"590","csrt"=>"s","typ"=>"I"),
"t"=>array("srt"=>"600","csrt"=>"t","typ"=>"I"),
"ṭ"=>array("srt"=>"600","csrt"=>"t","typ"=>"I"),
"u"=>array(
    "̂"=>array("srt"=>"610","csrt"=>"u","typ"=>"I"),
    "̣"=>array("srt"=>"610","csrt"=>"u","typ"=>"I"),
    "srt"=>"610","csrt"=>"u","typ"=>"I"),
"ú"=>array("srt"=>"610","csrt"=>"u","typ"=>"I"),
"û"=>array("srt"=>"610","csrt"=>"u","typ"=>"I"),
"v"=>array(
    "̂"=>array("srt"=>"620","csrt"=>"v","typ"=>"I"),
    "̣"=>array("srt"=>"620","csrt"=>"v","typ"=>"I"),
    "srt"=>"620","csrt"=>"v","typ"=>"I"),
"w"=>array(
    ""=>array("srt"=>"630","csrt"=>"w","typ"=>"I"),
    "̣"=>array("srt"=>"630","csrt"=>"w","typ"=>"I"),
    "srt"=>"630","csrt"=>"w","typ"=>"I"),
"x"=>array("srt"=>"640","csrt"=>"x","typ"=>"I"),
"x̣"=>array("srt"=>"640","csrt"=>"x","typ"=>"I"),
"y"=>array("srt"=>"650","csrt"=>"y","typ"=>"I"),
"ŷ"=>array("srt"=>"650","csrt"=>"y","typ"=>"I"),
"ỵ"=>array("srt"=>"650","csrt"=>"y","typ"=>"I"),
"z"=>array("srt"=>"660","csrt"=>"z","typ"=>"I"),
"ẓ"=>array("srt"=>"660","csrt"=>"z","typ"=>"I"));

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
* S(C)→C(.)→C.(VM)→VM(~VM)→Si
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
