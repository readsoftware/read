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
	// Punctuation and Intra-word Punctuation
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
	// Latin
	"A"=>array("srt"=>"100","typ"=>"I"),
	"B"=>array("srt"=>"110","typ"=>"I"),
	"C"=>array("srt"=>"120","typ"=>"I"),
	"D"=>array("srt"=>"130","typ"=>"I"),
	"E"=>array("srt"=>"140","typ"=>"I"),
	"F"=>array("srt"=>"150","typ"=>"I"),
	"G"=>array("srt"=>"160","typ"=>"I"),
	"H"=>array("srt"=>"170","typ"=>"I"),
	"I"=>array("srt"=>"180","typ"=>"I"),
	"J"=>array("srt"=>"190","typ"=>"I"),
	"K"=>array("srt"=>"200","typ"=>"I"),
	"L"=>array("srt"=>"210","typ"=>"I"),
	"M"=>array("srt"=>"220","typ"=>"I"),
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
		"̉"=>array("srt"=>"106","csrt"=>"a","typ"=>"I"),
		"srt"=>"105","csrt"=>"a","typ"=>"I"),
	"b"=>array(
		"̄"=>array("srt"=>"116","typ"=>"I"),
		"srt"=>"115","typ"=>"I"),
	"c"=>array("srt"=>"125","typ"=>"I"),
	"d"=>array("srt"=>"135","typ"=>"I"),
	"e"=>array(
    "̨"=>array("srt"=>"146","typ"=>"I"),
		"̉"=>array("srt"=>"147","typ"=>"I"),
    "srt"=>"145","typ"=>"I"),
	"ę"=>array("srt"=>"146","typ"=>"I"),
	"f"=>array("srt"=>"155","typ"=>"I"),
	"g"=>array("srt"=>"165","typ"=>"I"),
	"h"=>array("srt"=>"175","typ"=>"I"),
	"i"=>array("srt"=>"185","typ"=>"I"),
	"j"=>array("srt"=>"195","typ"=>"I"),
	"k"=>array("srt"=>"205","typ"=>"I"),
	"l"=>array(
		"̄"=>array("srt"=>"216","typ"=>"I"),
		"srt"=>"215","typ"=>"I"),
	"m"=>array("srt"=>"225","typ"=>"I"),
	"n"=>array("srt"=>"235","typ"=>"I"),
	"o"=>array("srt"=>"245","typ"=>"I"),
	"p"=>array("srt"=>"255","typ"=>"I"),
	"ꝓ"=>array("srt"=>"256","typ"=>"I"),
	"ꝑ"=>array("srt"=>"257","typ"=>"I"),
	"q"=>array("srt"=>"265","typ"=>"I"),
	"ꝗ"=>array("srt"=>"266","typ"=>"I"),
	"r"=>array("srt"=>"275","typ"=>"I"),
	"s"=>array("srt"=>"285","typ"=>"I"),
	"t"=>array(
		"̄"=>array("srt"=>"296","typ"=>"I"),
		"srt"=>"295","typ"=>"I"),
	"u"=>array(
		"̉"=>array("srt"=>"306","csrt"=>"u","typ"=>"I"),
		"srt"=>"305","typ"=>"I"),
	"v"=>array("srt"=>"315","typ"=>"I"),
	"w"=>array("srt"=>"325","typ"=>"I"),
	"x"=>array("srt"=>"335","typ"=>"I"),
	"y"=>array("srt"=>"345","typ"=>"I"),
	"z"=>array("srt"=>"355","typ"=>"I"),
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
	"Κ"=>array("srt"=>"490","typ"=>"I"),
	"Λ"=>array("srt"=>"500","typ"=>"I"),
	"Μ"=>array("srt"=>"510","typ"=>"I"),
	"Ν"=>array("srt"=>"520","typ"=>"I"),
	"Ξ"=>array("srt"=>"530","typ"=>"I"),
	"Ο"=>array("srt"=>"540","typ"=>"I"),
	"Π"=>array("srt"=>"550","typ"=>"I"),
	"Ρ"=>array("srt"=>"560","typ"=>"I"),
	"Σ"=>array("srt"=>"570","typ"=>"I"),
	"Σ"=>array("srt"=>"580","typ"=>"I"),
	"Τ"=>array("srt"=>"590","typ"=>"I"),
	"Υ"=>array("srt"=>"600","typ"=>"I"),
	"Φ"=>array("srt"=>"610","typ"=>"I"),
	"Χ"=>array("srt"=>"620","typ"=>"I"),
	"Ψ"=>array("srt"=>"630","typ"=>"I"),
	"Ω"=>array("srt"=>"640","typ"=>"I"),
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
	"א"=>array("srt"=>"650","typ"=>"I"), //ALEF
	"ב"=>array("srt"=>"650","typ"=>"I"), //BET
	"ג"=>array("srt"=>"650","typ"=>"I"), //GIMEL
	"ד"=>array("srt"=>"650","typ"=>"I"), //DALET
	"ה"=>array("srt"=>"650","typ"=>"I"), //HE
	"ו"=>array("srt"=>"650","typ"=>"I"), //VAV
	"ז"=>array("srt"=>"650","typ"=>"I"), //ZAYIN
	"ח"=>array("srt"=>"650","typ"=>"I"), //HET
	"ט"=>array("srt"=>"650","typ"=>"I"), //TET
	"י"=>array("srt"=>"650","typ"=>"I"), //YOD
	"ך"=>array("srt"=>"650","typ"=>"I"), //KAF FINAL
	"כ"=>array("srt"=>"650","typ"=>"I"), //KAF
	"ל"=>array("srt"=>"650","typ"=>"I"), //LAMED
	"ם"=>array("srt"=>"650","typ"=>"I"), //MEM FINAL
	"מ"=>array("srt"=>"650","typ"=>"I"), //MEM
	"ן"=>array("srt"=>"650","typ"=>"I"), //NUN FINAL
	"נ"=>array("srt"=>"650","typ"=>"I"), //NUN
	"ס"=>array("srt"=>"650","typ"=>"I"), //SAMEKH
	"ע"=>array("srt"=>"650","typ"=>"I"), //AYIN
	"ף"=>array("srt"=>"650","typ"=>"I"), //PE FINAL
	"פ"=>array("srt"=>"650","typ"=>"I"), //PE
	"ץ"=>array("srt"=>"650","typ"=>"I"), //TSADI FINAL
	"צ"=>array("srt"=>"650","typ"=>"I"), //TSADI
	"ק"=>array("srt"=>"650","typ"=>"I"), //QOF
	"ר"=>array("srt"=>"650","typ"=>"I"), //RESH
	"ש"=>array("srt"=>"650","typ"=>"I"), //SHIN
	"ת"=>array("srt"=>"650","typ"=>"I"), //TAV

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
