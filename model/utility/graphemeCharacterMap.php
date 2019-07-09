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
"0"=>array("srt"=>"700","ssrt"=>"700","typ"=>"N"),
"½"=>array("srt"=>"705","ssrt"=>"705","typ"=>"N"),
"1"=>array(
    "srt"=>"710","ssrt"=>"710","typ"=>"N",
    "2"=>array( "srt"=>"762","ssrt"=>"712","typ"=>"N"),
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
"◦"=>array("srt"=>"810","ssrt"=>"810","typ"=>"P"),
"•"=>array("srt"=>"805","ssrt"=>"805","typ"=>"P"),
"∙"=>array("srt"=>"804","ssrt"=>"804","typ"=>"I"),
"☒"=>array("srt"=>"845","ssrt"=>"845","typ"=>"P"),
"☸"=>array("srt"=>"840","ssrt"=>"840","typ"=>"P"),
"❀"=>array("srt"=>"851","ssrt"=>"851","typ"=>"P"),
"⁝"=>array("srt"=>"806","ssrt"=>"806","typ"=>"P"),
"⏑"=>array("srt"=>"807","ssrt"=>"807","typ"=>"P"),
"⎼"=>array("srt"=>"808","ssrt"=>"808","typ"=>"P"),
"❉"=>array("srt"=>"850","ssrt"=>"850","typ"=>"P"),
"–"=>array("srt"=>"890","ssrt"=>"890","typ"=>"P"),
"—"=>array("srt"=>"890","ssrt"=>"890","typ"=>"P"),
"|"=>array(
    "|"=>array("srt"=>"870","ssrt"=>"870","typ"=>"I"),
    "srt"=>"860","ssrt"=>"860","typ"=>"I"),
"◯"=>array("srt"=>"821","ssrt"=>"821","typ"=>"I"),
//":"=>array("srt"=>"803","ssrt"=>"803","typ"=>"P"),
"*"=>array("srt"=>"099","ssrt"=>"099","typ"=>"V"), // for sanskrit
"·"=>array("srt"=>"000","ssrt"=>"099","typ"=>"M"), // virama {ASG: why both * and · for virama?
"."=>array("srt"=>"189","ssrt"=>"189","typ"=>"V"),
"_"=>array("srt"=>"599","ssrt"=>"599","typ"=>"C"),
"ʔ"=>array("srt"=>"195","ssrt"=>"250","typ"=>"C"),
"°"=>array("srt"=>"195","ssrt"=>"195","typ"=>"C"),
"’"=>array("srt"=>"194","ssrt"=>"194","typ"=>"C"),
"?"=>array("srt"=>"990","ssrt"=>"990","typ"=>"O"),
"+"=>array("srt"=>"953","ssrt"=>"953","typ"=>"O"),
"/"=>array(
    "/"=>array(
        "/"=>array("srt"=>"954","ssrt"=>"954","typ"=>"O")),
    "srt"=>"959","ssrt"=>"959","typ"=>"O"), // debug temp assiqnment remove after / clean up
"#"=>array("srt"=>"956","ssrt"=>"956","typ"=>"O"),
"…"=>array("srt"=>"955","ssrt"=>"955","typ"=>"O"),
"A"=>array(
  "K"=>array(
    "`"=>array(
      "B"=>array(
        "`"=>array(
          "A"=>array(
            "L"=>array("srt"=>"477","typ"=>"I"),
          "srt"=>"476","typ"=>"I"),
        "srt"=>"475","typ"=>"I"),
      "srt"=>"474","typ"=>"I"),
    "srt"=>"473","typ"=>"I"),
  "srt"=>"472","typ"=>"I"),
  "J"=>array(
    "A"=>array(
      "W"=>array("srt"=>"478","typ"=>"I"))),
  "srt"=>"471","typ"=>"I"),
"B"=>array(
  "'"=>array(
    "A"=>array(
      ":"=>array(
        "L"=>array(
          "A"=>array(
            "M"=>array("srt"=>"477","typ"=>"I"),
            "srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "N"=>array("srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "E"=>array(
      "N"=>array("srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"C"=>array(
  "H"=>array(
    "A"=>array(
      "N"=>array("srt"=>"474","typ"=>"I"),
    "srt"=>"473","typ"=>"I"),
    "I"=>array(
      "K"=>array(
        "C"=>array(
          "H"=>array(
            "A"=>array(
              "N"=>array("srt"=>"478","typ"=>"I"),
              "srt"=>"477","typ"=>"I"),
            "srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "U"=>array(
      "W"=>array(
        "E"=>array(
          "N"=>array("srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "W"=>array(
      "A"=>array(
        ":"=>array(
          "J"=>array("srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "E"=>array(
        ":"=>array(
          "N"=>array("srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"E"=>array(
  "K"=>array(
    "'"=>array("srt"=>"473","typ"=>"I"))),
  
"H"=>array(
  "A"=>array(
    "'"=>array("srt"=>"473","typ"=>"I"),
    ":"=>array(
      "B"=>array(
        "'"=>array("srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "I"=>array(
    "N"=>array(
      "A"=>array(
        "J"=>array("srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "U"=>array(
    ":"=>array(
      "N"=>array("srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "X"=>array(
      "L"=>array(
        "A"=>array(
          "J"=>array(
            "U"=>array(
              ":"=>array(
                "N"=>array("srt"=>"479","typ"=>"I"),
                "srt"=>"478","typ"=>"I"),
              "srt"=>"477","typ"=>"I"),
            "srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"I"=>array(
  "K"=>array(
    "`"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "X"=>array(
    "?"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "M"=>array(
    "I"=>array(
      "X"=>array("srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "T"=>array(
    "Z"=>array(
      "A"=>array(
        "M"=>array("srt"=>"475","typ"=>"I")))),
  "srt"=>"471","typ"=>"I"),
"J"=>array(
  "A"=>array(
    "L"=>array("srt"=>"473","typ"=>"I"),
    "N"=>array(
      "A"=>array(
        ":"=>array(
          "B"=>array(
            "'"=>array("srt"=>"477","typ"=>"I"))))))),
"K"=>array(
  "A"=>array(
    "B"=>array(
      "`"=>array(
        "A"=>array(
          "N"=>array("srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "M"=>array("srt"=>"473","typ"=>"I"),
    "W"=>array(
      "A"=>array(
        "K"=>array("srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
    "E"=>array(
      "L"=>array(
        "E"=>array(
          ":"=>array(
            "M"=>array("srt"=>"476","typ"=>"I"),
            "srt"=>"475","typ"=>"I"),
          "srt"=>"474","typ"=>"I"),
        "srt"=>"473","typ"=>"I"),
      "srt"=>"472","typ"=>"I"),
  "'"=>array(
    "A"=>array(
      "W"=>array(
        "I"=>array(
          ":"=>array(
            "L"=>array("srt"=>"477","typ"=>"I"),
            "srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      ":"=>array(
        "K"=>array(
          "'"=>array("srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "I"=>array(
      "N"=>array("srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "U"=>array(
      "H"=>array("srt"=>"474","typ"=>"I"),
      ":"=>array(
        "C"=>array(
          "H"=>array("srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"L"=>array(
  "O"=>array(
    "B"=>array(
      "'"=>array("srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "U"=>array(
    "K"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"M"=>array(
  "A"=>array(
    "N"=>array(
      "I"=>array(
        "K"=>array(
          "`"=>array("srt"=>"476","typ"=>"I"),
        "srt"=>"475","typ"=>"I"),
      "srt"=>"474","typ"=>"I"),
    "srt"=>"473","typ"=>"I"),
  "srt"=>"472","typ"=>"I"),
  "E"=>array(
    "N"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "U"=>array(
    "L"=>array(
      "U"=>array(
        "K"=>array("srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "I"=>array(
    "H"=>array("srt"=>"473","typ"=>"I"))
  ),
"N"=>array(
  "A"=>array(
    "L"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"O"=>array(
  ":"=>array(
    "N"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  ),
"P"=>array(
  "I"=>array(
    "K"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
  "S"=>array(
  "I"=>array(
    "H"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"T"=>array(
  "E"=>array(
    "'"=>array(
      "srt"=>"473","typ"=>"I")),
  "I"=>array(
    "'"=>array(
      "srt"=>"473","typ"=>"I")),
  "U"=>array(
    ":"=>array(
      "N"=>array("srt"=>"474","typ"=>"I"))),
  "Z"=>array(
    "'"=>array(
      "A"=>array(
        "K"=>array("srt"=>"475","typ"=>"I"),
        "M"=>array("srt"=>"475","typ"=>"I")))),
  "srt"=>"471","typ"=>"I"),
"U"=>array(
  "H"=>array("srt"=>"471","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),
"W"=>array(
  "A"=>array(
    "K"=>array("srt"=>"473","typ"=>"I"),
    ":"=>array(
      "J"=>array("srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "X"=>array(
      "A"=>array(
        "K"=>array("srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "E"=>array(
    "N"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "I"=>array(
    "N"=>array(
      "I"=>array(
        "K"=>array(
          "H"=>array(
            "A"=>array(
              ":"=>array(
                "B"=>array(
                  "'"=>array("srt"=>"4710","typ"=>"I"),
                    "srt"=>"479","typ"=>"I"),
                  "srt"=>"478","typ"=>"I"),
                "srt"=>"477","typ"=>"I"),
              "srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "'"=>array("srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),

"'"=>array(
  "L"=>array(
    "A"=>array(
      "M"=>array(
        "A"=>array(
          "T"=>array(
            "'"=>array("srt"=>"477","typ"=>"I"),
            "srt"=>"476","typ"=>"I"),
          "srt"=>"475","typ"=>"I"),
        "srt"=>"474","typ"=>"I"),
      "srt"=>"473","typ"=>"I"),
    "srt"=>"472","typ"=>"I"),
  "srt"=>"471","typ"=>"I"),


"a"=>array("srt"=>"100","ssrt"=>"100","typ"=>"V"),
"e"=>array("srt"=>"110","ssrt"=>"110","typ"=>"V"),
"i"=>array("srt"=>"120","ssrt"=>"120","typ"=>"V"),
"o"=>array("srt"=>"130","ssrt"=>"130","typ"=>"V"),
"u"=>array("srt"=>"140","ssrt"=>"140","typ"=>"V"),

"b"=>array(
    "'"=>array("srt"=>"300","ssrt"=>"520","typ"=>"C"),
    "srt"=>"300","ssrt"=>"520","typ"=>"C"),
"c"=>array(
  "h"=>array(
    "'"=>array("srt"=>"320","ssrt"=>"520","typ"=>"C"),
    "srt"=>"320","ssrt"=>"300","typ"=>"C"),
  "srt"=>"320","ssrt"=>"310","typ"=>"C"),
"d"=>array("srt"=>"330","ssrt"=>"470","typ"=>"C"),
"f"=>array("srt"=>"350","ssrt"=>"470","typ"=>"C"),
"g"=>array("srt"=>"370","ssrt"=>"470","typ"=>"C"),
"h"=>array("srt"=>"390","ssrt"=>"650","typ"=>"C"),
"j"=>array("srt"=>"410","ssrt"=>"650","typ"=>"C"),
"k"=>array(
    "`"=>array("srt"=>"440","ssrt"=>"281","typ"=>"C"),
    "srt"=>"430","ssrt"=>"260","typ"=>"C"),
"l"=>array("srt"=>"450","ssrt"=>"570","typ"=>"C"),
"m"=>array("srt"=>"470","ssrt"=>"540","typ"=>"C"),
"n"=>array("srt"=>"490","ssrt"=>"490","typ"=>"C"),
"p"=>array("srt"=>"510","ssrt"=>"500","typ"=>"C"),
"q"=>array("srt"=>"530","ssrt"=>"500","typ"=>"C"),
"r"=>array("srt"=>"550","ssrt"=>"560","typ"=>"C"),
"s"=>array("srt"=>"570","ssrt"=>"620","typ"=>"C"),
"t"=>array(
    "z"=>array(
      "'"=>array("srt"=>"601","ssrt"=>"281","typ"=>"C"),
      "srt"=>"600","ssrt"=>"450","typ"=>"C"),
    "srt"=>"590","ssrt"=>"440","typ"=>"C"),
"v"=>array("srt"=>"610","ssrt"=>"580","typ"=>"C"),
"w"=>array("srt"=>"630","ssrt"=>"580","typ"=>"C"),
"x"=>array("srt"=>"650","ssrt"=>"580","typ"=>"C"),
"y"=>array("srt"=>"670","ssrt"=>"550","typ"=>"C"),
"z"=>array("srt"=>"690","ssrt"=>"640","typ"=>"C"),
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
