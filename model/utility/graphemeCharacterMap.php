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
"∙"=>array("srt"=>"804","ssrt"=>"804","typ"=>"I"),// 2219
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
    "|"=>array("srt"=>"870","ssrt"=>"870","typ"=>"P"),
    "srt"=>"860","ssrt"=>"860","typ"=>"P"),
"◯"=>array("srt"=>"821","ssrt"=>"821","typ"=>"I"),
":"=>array("srt"=>"803","ssrt"=>"803","typ"=>"P"),
"*"=>array("srt"=>"099","ssrt"=>"099","typ"=>"V"), // for sanskrit
"·"=>array("srt"=>"000","ssrt"=>"099","typ"=>"M"), // 00B7 virama {ASG: why both * and · for virama?
"."=>array("srt"=>"189","ssrt"=>"189","typ"=>"V"),
"_"=>array("srt"=>"599","ssrt"=>"599","typ"=>"C"),
"ʔ"=>array("srt"=>"195","ssrt"=>"195","typ"=>"C"),
"°"=>array("srt"=>"195","ssrt"=>"195","typ"=>"C"),
//"'"=>array("srt"=>"194","ssrt"=>"194","typ"=>"C"),// for vp2sk, need to check if this is for general sanskrit
"?"=>array("srt"=>"990","ssrt"=>"990","typ"=>"O"),
"+"=>array("srt"=>"953","ssrt"=>"953","typ"=>"O"),
"/"=>array(
    "/"=>array(
        "/"=>array("srt"=>"954","ssrt"=>"954","typ"=>"I")),
    "srt"=>"959","ssrt"=>"959","typ"=>"O"), // debug temp assiqnment remove after / clean up
"#"=>array("srt"=>"956","ssrt"=>"956","typ"=>"O"),
"…"=>array("srt"=>"955","ssrt"=>"955","typ"=>"O"),
"a"=>array(
    "͚"=>array("srt"=>"108","ssrt"=>"108","typ"=>"V",
          "i"=>array("srt"=>"208","ssrt"=>"218","typ"=>"V"),
          "u"=>array("srt"=>"228","ssrt"=>"238","typ"=>"V")),
    "̣"=>array("srt"=>"107","ssrt"=>"107","typ"=>"V"),
    "i"=>array("srt"=>"200","ssrt"=>"210","typ"=>"V"),
    "u"=>array("srt"=>"220","ssrt"=>"230","typ"=>"V"),
    "srt"=>"100","ssrt"=>"100","typ"=>"V"),
"á"=>array(
    "i"=>array("srt"=>"202","ssrt"=>"212","typ"=>"V"),
    "u"=>array("srt"=>"222","ssrt"=>"232","typ"=>"V"),
    "srt"=>"102","ssrt"=>"102","typ"=>"V"),
"à"=>array(
    "i"=>array("srt"=>"203","ssrt"=>"213","typ"=>"V"),
    "u"=>array("srt"=>"223","ssrt"=>"233","typ"=>"V"),
    "srt"=>"103","ssrt"=>"103","typ"=>"V"),
"ȧ"=>array("srt"=>"106","ssrt"=>"106","typ"=>"V"),
"â"=>array("srt"=>"104","ssrt"=>"104","typ"=>"V"),
"ā"=>array(
    "́"=>array("srt"=>"102","ssrt"=>"112","typ"=>"V"),
    "̀"=>array("srt"=>"103","ssrt"=>"113","typ"=>"V"),
    "̆"=>array("srt"=>"101","ssrt"=>"111","typ"=>"V"),
    "srt"=>"101","ssrt"=>"110","typ"=>"V"),
"ã"=>array(
    "i"=>array("srt"=>"204","ssrt"=>"214","typ"=>"V"),
    "u"=>array("srt"=>"224","ssrt"=>"234","typ"=>"V"),
    "srt"=>"104","ssrt"=>"104","typ"=>"V"),
"ǎ"=>array("srt"=>"105","ssrt"=>"105","typ"=>"V"),
"b"=>array(
    "͟"=>array("h"=>array("srt"=>"531","ssrt"=>"531","typ"=>"C")),
    "̄"=>array("srt"=>"522","ssrt"=>"522","typ"=>"C"),
    "h"=>array("srt"=>"530","ssrt"=>"530","typ"=>"C"),
    "srt"=>"520","ssrt"=>"520","typ"=>"C"),
"ḅ"=>array("srt"=>"521","ssrt"=>"521","typ"=>"C"),
"c"=>array(
    "̱"=>array("srt"=>"321","ssrt"=>"321","typ"=>"C"),
    "̄"=>array("srt"=>"322","ssrt"=>"322","typ"=>"C"),
    "̂"=>array("srt"=>"329","ssrt"=>"329","typ"=>"C"),
    "h"=>array("srt"=>"330","ssrt"=>"330","typ"=>"C"),
    "srt"=>"320","ssrt"=>"320","typ"=>"C"),
"ć"=>array("srt"=>"329","ssrt"=>"329","typ"=>"C"),
"d"=>array("srt"=>"470","ssrt"=>"470","typ"=>"C",
    "h"=>array("srt"=>"480","ssrt"=>"480","typ"=>"C")),
"ḏ"=>array("srt"=>"471","ssrt"=>"471","typ"=>"C"),
"ḍ"=>array("srt"=>"410","ssrt"=>"410","typ"=>"C",
    "h"=>array("srt"=>"420","ssrt"=>"420","typ"=>"C"),
    "̄"=>array("srt"=>"412","ssrt"=>"412","typ"=>"C"),
    "͟"=>array("h"=>array("srt"=>"421","ssrt"=>"421","typ"=>"C")),
    "̱"=>array("srt"=>"411","ssrt"=>"411","typ"=>"C"),
    "͞"=>array("h"=>array("srt"=>"422","ssrt"=>"422","typ"=>"C"))),
"e"=>array(
    "͚"=>array("srt"=>"208","ssrt"=>"208","typ"=>"V"),
    "̣"=>array("srt"=>"207","ssrt"=>"207","typ"=>"V"),
    "srt"=>"200","ssrt"=>"200","typ"=>"V"),
"é"=>array("srt"=>"202","ssrt"=>"202","typ"=>"V"),
"è"=>array("srt"=>"203","ssrt"=>"203","typ"=>"V"),
"ê"=>array("srt"=>"204","ssrt"=>"204","typ"=>"V"),
"ě"=>array("srt"=>"205","ssrt"=>"205","typ"=>"V"),
"ĕ"=>array("srt"=>"200","ssrt"=>"200","typ"=>"V"),
"ē"=>array(
    "̆"=>array("srt"=>"201","ssrt"=>"201","typ"=>"V"),
    "srt"=>"201","ssrt"=>"201","typ"=>"V"),
"ẽ"=>array("srt"=>"204","ssrt"=>"204","typ"=>"V"),
"ḗ"=>array("srt"=>"202","ssrt"=>"202","typ"=>"V"),
"ḕ"=>array("srt"=>"203","ssrt"=>"203","typ"=>"V"),
"g"=>array(
    "̱"=>array("srt"=>"291","ssrt"=>"291","typ"=>"C"),
    "h"=>array("srt"=>"300","ssrt"=>"300","typ"=>"C"),
    "srt"=>"290","ssrt"=>"290","typ"=>"C"),
"ḡ"=>array(
    "̱"=>array("srt"=>"293","ssrt"=>"293","typ"=>"C"),
    "srt"=>"292","ssrt"=>"292","typ"=>"C"),
"h"=>array(
    "̄"=>array("srt"=>"652","ssrt"=>"652","typ"=>"C"),
    "̮"=>array("srt"=>"252","ssrt"=>"252","typ"=>"M"),
    "srt"=>"650","ssrt"=>"650","typ"=>"C"),
"ḣ"=>array("srt"=>"654","ssrt"=>"654","typ"=>"C"),
"ḥ"=>array("srt"=>"250","ssrt"=>"250","typ"=>"M"),
"ẖ"=>array("srt"=>"251","ssrt"=>"251","typ"=>"M"),
"ḫ"=>array("srt"=>"252","ssrt"=>"252","typ"=>"M"),
"i"=>array(
    "͚"=>array("srt"=>"128","ssrt"=>"128","typ"=>"V"),
    "srt"=>"120","ssrt"=>"120","typ"=>"V"),
//"ⁱ"=>array("srt"=>"129","ssrt"=>"129","typ"=>"V"), // deprecated
"ï"=>array("srt"=>"120","ssrt"=>"120","typ"=>"V"),
"í"=>array("srt"=>"122","ssrt"=>"122","typ"=>"V"),
"ì"=>array("srt"=>"123","ssrt"=>"123","typ"=>"V"),
"î"=>array("srt"=>"124","ssrt"=>"124","typ"=>"V"),
"ǐ"=>array("srt"=>"125","ssrt"=>"125","typ"=>"V"),
"ī"=>array(
    "́"=>array("srt"=>"122","ssrt"=>"132","typ"=>"V"),
    "̀"=>array("srt"=>"123","ssrt"=>"133","typ"=>"V"),
    "̆"=>array("srt"=>"121","ssrt"=>"131","typ"=>"V"),
    "̃"=>array("srt"=>"124","ssrt"=>"134","typ"=>"V"),
    "srt"=>"121","ssrt"=>"130","typ"=>"V"),
"ĩ"=>array("srt"=>"124","ssrt"=>"124","typ"=>"V"),
"j"=>array(
    "̄"=>array("srt"=>"342","ssrt"=>"342","typ"=>"C"),
    "̱"=>array(
      "̄"=>array("srt"=>"343","ssrt"=>"343","typ"=>"C"),
      "srt"=>"341","ssrt"=>"341","typ"=>"C"),
    "h"=>array("srt"=>"350","ssrt"=>"350","typ"=>"C"),
    "srt"=>"340","ssrt"=>"340","typ"=>"C"),
"ĵ"=>array("srt"=>"349","ssrt"=>"349","typ"=>"C"),
"k"=>array( "̄"=>array("srt"=>"262","ssrt"=>"262","typ"=>"C"),
    "͟" => array( "h"=>array("srt"=>"281","ssrt"=>"281","typ"=>"C")),
    "h"=>array("srt"=>"280","ssrt"=>"280","typ"=>"C"),
    "srt"=>"260","ssrt"=>"260","typ"=>"C"),
"ḱ"=>array(
    "h"=>array("srt"=>"289","ssrt"=>"289","typ"=>"C"),
    "srt"=>"270","ssrt"=>"270","typ"=>"C"),
"ḵ"=>array("srt"=>"261","ssrt"=>"261","typ"=>"C"),
"l"=>array(
    "̥"=>array(
      "̄"=>array(
          "̆"=>array("srt"=>"181","ssrt"=>"191","typ"=>"V"),
          "́"=>array("srt"=>"182","ssrt"=>"192","typ"=>"V"),
          "srt"=>"181","ssrt"=>"191","typ"=>"V"),
      "́"=>array("srt"=>"182","ssrt"=>"182","typ"=>"V"),
      "̂"=>array("srt"=>"184","ssrt"=>"184","typ"=>"V"),
      "srt"=>"180","ssrt"=>"180","typ"=>"V"),
    "srt"=>"570","ssrt"=>"570","typ"=>"C"),
"ḻ"=>array("srt"=>"661","ssrt"=>"661","typ"=>"C"),
"ḷ"=>array("srt"=>"660","ssrt"=>"660","typ"=>"C",
    "·"=>array("ssrt"=>"247","typ"=>"M"),
    "h"=>array("srt"=>"670","ssrt"=>"670","typ"=>"C")),
"m"=>array(
    "̂"=>array("srt"=>"546","ssrt"=>"546","typ"=>"C"),
    "̄"=>array("srt"=>"542","ssrt"=>"542","typ"=>"C"),
    "̐"=>array("srt"=>"242","ssrt"=>"242","typ"=>"M"),// for sanskrit
//    "·"=>array("ssrt"=>"246","typ"=>"M"),// for vp2sk only
    "̥"=>array("srt"=>"549","ssrt"=>"549","typ"=>"C"),
    "̱"=>array("srt"=>"541","ssrt"=>"541","typ"=>"C"),
    "srt"=>"540","ssrt"=>"540","typ"=>"C"),
"ḿ"=>array("srt"=>"549","ssrt"=>"549","typ"=>"C"),
"ṁ"=>array("ssrt"=>"244","typ"=>"M"),
"ṃ"=>array("srt"=>"240","ssrt"=>"240","typ"=>"M"),
"n"=>array(
//    "·"=>array("ssrt"=>"245","typ"=>"M"),// for vp2sk only
    "̂"=>array("srt"=>"499","ssrt"=>"499","typ"=>"C"),
    "̄"=>array("srt"=>"492","ssrt"=>"492","typ"=>"C"),
    "̥"=>array("srt"=>"499","ssrt"=>"499","typ"=>"C"),
    "srt"=>"490","ssrt"=>"490","typ"=>"C"),
"ṉ"=>array("srt"=>"493","ssrt"=>"493","typ"=>"C"),
"ṅ"=>array("srt"=>"310","ssrt"=>"310","typ"=>"C"),
"ñ"=>array(
    "̄"=>array("srt"=>"362","ssrt"=>"362","typ"=>"C"),
    "srt"=>"360","ssrt"=>"360","typ"=>"C"),
"ṇ"=>array(
    "̄"=>array("srt"=>"432","ssrt"=>"432","typ"=>"C"),
    "srt"=>"430","ssrt"=>"430","typ"=>"C"),
"o"=>array(
    "͚"=>array("srt"=>"228","ssrt"=>"228","typ"=>"V"),
    "srt"=>"220","ssrt"=>"220","typ"=>"V"),
"ó"=>array("srt"=>"222","ssrt"=>"222","typ"=>"V"),
"ò"=>array("srt"=>"223","ssrt"=>"223","typ"=>"V"),
"ô"=>array("srt"=>"224","ssrt"=>"224","typ"=>"V"),
"ǒ"=>array("srt"=>"225","ssrt"=>"225","typ"=>"V"),
"ŏ"=>array("srt"=>"220","ssrt"=>"220","typ"=>"V"),
"ō"=>array(
    "̆"=>array("srt"=>"221","ssrt"=>"221","typ"=>"V"),
    "srt"=>"221","ssrt"=>"221","typ"=>"V"),
"õ"=>array("srt"=>"224","ssrt"=>"224","typ"=>"V"),
"ṓ"=>array("srt"=>"222","ssrt"=>"222","typ"=>"V"),
"ṑ"=>array("srt"=>"223","ssrt"=>"223","typ"=>"V"),
"p"=>array(
    "̄"=>array("srt"=>"502","ssrt"=>"502","typ"=>"C"),
    "͟"=>array("h"=>array("srt"=>"511","ssrt"=>"511","typ"=>"C")),
    "̱"=>array("srt"=>"501","ssrt"=>"501","typ"=>"C"),
    "h"=>array("srt"=>"510","ssrt"=>"510","typ"=>"C"),
    "srt"=>"500","ssrt"=>"500","typ"=>"C"),
"ṕ"=>array("srt"=>"691","ssrt"=>"691","typ"=>"C"),
"ṛ"=>array("ssrt"=>"160","typ"=>"V"),
"ṝ"=>array("ssrt"=>"170","typ"=>"V"),
"ṟ"=>array("srt"=>"561","ssrt"=>"561","typ"=>"C"),
"r"=>array(
    "̥"=>array(
        "srt"=>"160","ssrt"=>"160","typ"=>"V",
        "́"=>array("srt"=>"162","ssrt"=>"162","typ"=>"V"),
        "̀"=>array("srt"=>"163","ssrt"=>"163","typ"=>"V"),
        "̃"=>array("srt"=>"164","ssrt"=>"164","typ"=>"V"),
        "͚"=>array("srt"=>"168","ssrt"=>"168","typ"=>"V"),
        "̄"=>array("srt"=>"161","ssrt"=>"171","typ"=>"V",
            "̆"=>array("srt"=>"161","ssrt"=>"171","typ"=>"V"),
            "́"=>array("srt"=>"162","ssrt"=>"172","typ"=>"V"),
            "̃"=>array("srt"=>"164","ssrt"=>"174","typ"=>"V")),
        "̂"=>array("srt"=>"164","ssrt"=>"164","typ"=>"V"),
        "͡"=>array("i"=>array("srt"=>"167","ssrt"=>"167","typ"=>"V"))
        ),
    "̱"=>array("srt"=>"561","ssrt"=>"561","typ"=>"C"),
    "srt"=>"560","ssrt"=>"560","typ"=>"C"),
"s"=>array(
    "̂"=>array("srt"=>"629","ssrt"=>"629","typ"=>"C"),
    "̄"=>array("srt"=>"622","ssrt"=>"622","typ"=>"C"),
    "̮"=>array("srt"=>"623","ssrt"=>"623","typ"=>"C"),
    "̱"=>array("srt"=>"621","ssrt"=>"621","typ"=>"C"),
    "srt"=>"620","ssrt"=>"620","typ"=>"C"),
"ś"=>array(
    "̱"=>array(
      "̄"=>array("srt"=>"603","ssrt"=>"603","typ"=>"C"),
      "srt"=>"601","ssrt"=>"601","typ"=>"C"),
    "̄"=>array("srt"=>"602","ssrt"=>"602","typ"=>"C"),
    "͟"=>array("srt"=>"608","ssrt"=>"608","typ"=>"C"),
    "̂"=>array("srt"=>"609","ssrt"=>"609","typ"=>"C"),
    "srt"=>"600","ssrt"=>"600","typ"=>"C"),
"ṣ"=>array(
    "̂"=>array("srt"=>"619","ssrt"=>"619","typ"=>"C"),
    "̄"=>array("srt"=>"612","ssrt"=>"612","typ"=>"C"),
    "̱"=>array(
        "̄"=>array("srt"=>"613","ssrt"=>"613","typ"=>"C"),
        "srt"=>"611","ssrt"=>"611","typ"=>"C"),
    "srt"=>"610","ssrt"=>"610","typ"=>"C"),
"t"=>array(
//    "·"=>array("ssrt"=>"243","typ"=>"M"),// for vp2sk only
    "́"=>array("srt"=>"449","ssrt"=>"449","typ"=>"C"),
    "h"=>array(
        "srt"=>"450","ssrt"=>"450","typ"=>"C",
        "́"=>array("srt"=>"460","ssrt"=>"460","typ"=>"C")),
    "srt"=>"440","ssrt"=>"440","typ"=>"C"),
"ṯ"=>array("srt"=>"441","ssrt"=>"441","typ"=>"C"),
"ṭ"=>array(
    "́"=>array(
        "h"=>array("srt"=>"400","ssrt"=>"400","typ"=>"C"),
        "srt"=>"380","ssrt"=>"380","typ"=>"C"),
    "h"=>array("srt"=>"390","ssrt"=>"390","typ"=>"C"),
    "srt"=>"370","ssrt"=>"370","typ"=>"C"),
"u"=>array(
    "͚"=>array("srt"=>"148","ssrt"=>"148","typ"=>"V"),
    "srt"=>"140","ssrt"=>"140","typ"=>"V"),
"ü"=>array("srt"=>"140","ssrt"=>"140","typ"=>"V"),
"ú"=>array("srt"=>"142","ssrt"=>"142","typ"=>"V"),
"ù"=>array("srt"=>"143","ssrt"=>"143","typ"=>"V"),
"û"=>array("srt"=>"144","ssrt"=>"144","typ"=>"V"),
"ǔ"=>array("srt"=>"145","ssrt"=>"145","typ"=>"V"),
"ū"=>array(
    "̆"=>array("srt"=>"141","ssrt"=>"151","typ"=>"V"),
    "́"=>array("srt"=>"142","ssrt"=>"152","typ"=>"V"),
    "̀"=>array("srt"=>"143","ssrt"=>"153","typ"=>"V"),
    "̃"=>array("srt"=>"144","ssrt"=>"154","typ"=>"V"),
    "srt"=>"141","ssrt"=>"150","typ"=>"V"),
"ũ"=>array("srt"=>"144","ssrt"=>"144","typ"=>"V"),
//"ü"=>array(
//    "͚"=>array("srt"=>"148","ssrt"=>"148","typ"=>"V"),
//    "srt"=>"140","ssrt"=>"140","typ"=>"V"),
//"ǘ"=>array("srt"=>"142","ssrt"=>"142","typ"=>"V"),
//"ǜ"=>array("srt"=>"143","ssrt"=>"143","typ"=>"V"),
//"ǚ"=>array("srt"=>"144","ssrt"=>"144","typ"=>"V"),
//"ǖ"=>array("srt"=>"141","ssrt"=>"141","typ"=>"V"),
"v"=>array(
    "́"=>array("srt"=>"589","ssrt"=>"589","typ"=>"C"),
    "͟"=>array("h"=>array("srt"=>"588","ssrt"=>"588","typ"=>"C")),
    "̱"=>array("srt"=>"581","ssrt"=>"581","typ"=>"C"),
    "h"=>array("srt"=>"590","ssrt"=>"590","typ"=>"C"),
    "srt"=>"580","ssrt"=>"580","typ"=>"C"),
"y"=>array(
    "̱"=>array("srt"=>"551","ssrt"=>"551","typ"=>"C"),
    "srt"=>"550","ssrt"=>"550","typ"=>"C"),
"ý"=>array("srt"=>"692","ssrt"=>"692","typ"=>"C"),
"z"=>array("srt"=>"640","ssrt"=>"640","typ"=>"C"),
"ẕ"=>array("srt"=>"641","ssrt"=>"641","typ"=>"C"),
"ẓ"=>array("srt"=>"630","ssrt"=>"630","typ"=>"C"));

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
