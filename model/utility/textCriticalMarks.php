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
  * Text Critical Marks Utility functions
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Utility Classes
  */
    /**
    * getNextTCMState - state engine for Text Critical Marks
    *
    * TCM State Machine Transitions
    * where  S = Start  U = Uncertain  R =Restoration  A = Addition  I = Insertion(scribal)  D = Deletion Sd = Scribal deletion E = Error
    * S( {{ )→Sd( }} )→S
    * S( [ )→U( ] )→S
    * S( (* )→R( ) )→S
    * S( ⟨* )→A( ⟩ )→S
    * S( { )→D( } )→S
    * S( ⟪ )→I( ⟫ )→S
    *
    * I( {{ )→ISd( }} )→I
    * I( [ )→IU( ] )→I
    * I( (* )→IR( ) )→I
    * I( ⟨* )→IA( ⟩ )→I
    * I( { )→ID( } )→I
    * I( ⟪ )→E
    * I( ⟫ )→S
    *
    * Sd( {{ )→E
    * Sd( [ )→SdU( ] )→Sd
    * Sd( (* )→SdR( ) )→Sd
    * Sd( ⟨* )→SdA( ⟩ )→Sd
    * Sd( { )→SdD( } )→Sd
    * Sd( ⟪ )→SdI( ⟫ )→Sd
    *
    * SdI( {{ )→E
    * SdI( [ )→SdIU( ] )→SdI
    * SdI( (* )→SdIR( ) )→SdI
    * SdI( ⟨* )→SdIA( ⟩ )→SdI
    * SdI( { )→SdID( } )→SdI
    * SdI( ⟪ )→E
    *
    * D( {{ )→DSd( }} )→D
    * D( [ )→DU( ] )→D
    * D( (* )→DR( ) )→D
    * D( ⟨* )→DA( ⟩ )→D
    * D( { )→E
    * D( ⟪ )→DI( ⟫ )→D
    *
    * DI( {{ )→DISd( }} )→DI
    * DI( [ )→DIU( ] )→DI
    * DI( (* )→DIR( ) )→DI
    * DI( ⟨* )→DIA( ⟩ )→DI
    * DI( { )→E
    * DI( ⟪ )→E
    *
    * @param string $curState indicates the current state of segmentation
    * @param string $textMark denoting a state transition in the input sequence
    * @return string indicating the transitioned to state
    */
    function getNextTCMState($curState,$textMark) {
      static $tcmStateTable = array(
          //start
          "S" => array("⟨*" => "A",
                        "{" => "D",
                        "⟪" => "I",
                        "(*" => "R",
                        "{{" => "Sd",
                        "[" => "U"),
          //Singular TCM States
          "A" => array( "⟩" => "S"),
          "D" => array( "(*" => "DR",
                       "[" => "DU",
                       "{" => "E",
                       "{{" => "DSd",
                       "}" => "S",
                       "⟪" => "DI",
                       "⟨*" => "DA"),
          "I" => array( "(*" => "IR",
                        "[" => "IU",
                        "{" => "ID",
                        "{{" => "ISd",
                        "⟪" => "E",
                        "⟨*" => "IA",
                        "⟫" => "S"),
          "R" => array( ")" => "S"),
          "Sd" => array( "(*" => "SdR",
                        "[" => "SdU",
                        "{" => "SdD",
                        "{{" => "E",
                        "}}" => "S",
                        "⟪" => "SdI",
                        "⟨*" => "SdA"),
          "U" => array( "]" => "S"),
          //Double TCM States
          "DA" => array( "⟩" => "D"),
          "DI" => array( "[" => "DIU",
                        "(*" => "DIR",
                        "{" => "E",
                        "{{" => "DISd",
                        "⟪" => "E",
                        "⟨*" => "DIA",
                        "⟫" => "D"),
          "DR" => array( ")" => "D"),
          "DSd" => array( "}}" => "D"),
          "DU" => array( "]" => "D"),
          "IA" => array( "⟩" => "I"),
          "ID" => array( "}" => "I"),
          "IR" => array( ")" => "I"),
          "ISd" => array( "}}" => "I"),
          "IU" => array( "]" => "I"),
          "SdA" => array( "⟩" => "Sd"),
          "SdD" => array( "}" => "Sd"),
          "SdI" => array( "(*" => "SdIR",
                          "[" => "SdIU",
                          "{" => "SdID",
                          "{{" => "E",
                          "⟪" => "E",
                          "⟨*" => "SdIA",
                          "⟫" => "Sd"),
          "SdR" => array( ")" => "Sd"),
          "SdU" => array( "]" => "Sd"),
          //Triple TCM States
          "DIA" => array( "⟩" => "DI"),
          "DIR" => array( ")" => "DI"),
          "DISd" => array( "}}" => "DI"),
          "DIU" => array( "]" => "DI"),
          "SdIA" => array( "⟩" => "SdI"),
          "SdID" => array( "}" => "SdI"),
          "SdIR" => array( ")" => "SdI"),
          "SdIU" => array( "]" => "SdI"));

      if (!array_key_exists($curState,$tcmStateTable) || !array_key_exists($textMark,$tcmStateTable[$curState])) {
        return "E";
      }
      return $tcmStateTable[$curState][$textMark];
    }

    /**
    * getTCMTransitionBrackets - lookup function
    *
    * @param string $curState indicates the current state
    * @param string $nextState denoting next state
    * @return string of brackets for transition
    */
    function getTCMTransitionBrackets($curState,$nextState,$postNpre = false) {
      static $tcmBrackets = array(
          //start
          "S" => array("A"  =>"⟨*",
                       "D" => "{",
                       "DA" => "{⟨*",
                       "DR" => "{(*",
                       "DU" => "{[",
                       "DSd" => "{{{",
                       "DI" =>  "{⟪",
                       "DIU" => "{⟪[",
                       "DIR" => "{⟪(*",
                       "DISd" => "{⟪{{",
                       "DIA" => "{⟪⟨*",
                       "I" => "⟪",
                       "IR"  => "⟪(*",
                       "IU"  => "⟪[",
                       "ID"  => "⟪{",
                       "ISd" => "⟪{{",
                       "IA" => "⟪⟨*",
                       "R" =>"(*",
                       "S" =>"",
                       "Sd" =>"{{",
                       "SdR" => "{{(*",
                       "SdU" => "{{[",
                       "SdD" => "{{{",
                       "SdA" => "{{⟨*",
                       "SdI" => "{{⟪",
                       "SdIR" => "{{⟪(*",
                       "SdIU" => "{{⟪[",
                       "SdID" => "{{⟪{",
                       "SdIA" =>"{{⟪⟨*",
                       "U" => "["),
          //Singular TCM States
          "A" => array( "S" => "⟩"),
          "D" => array("DR" => "(*",
                       "DU" => "[",
                       "DSd" => "{{",
                       "DA" => "⟨*",
                       "DI" => "⟪",
                       "DIU" => "⟪[",
                       "DIR" => "⟪(*",
                       "DISd" => "⟪{{",
                       "DIA" => "⟪⟨*",
                       "S" => "}"),
          "I" => array( "IR"  => "(*",
                        "IU"  => "[",
                        "ID"  => "{" ,
                        "ISd" => "{{",
                        "IA" => "⟨*",
                        "S" => "⟫"),
          "R" => array( "S" => ")"),
          "Sd" => array( "SdR" => "(*",
                         "SdU" => "[",
                         "SdD" => "{",
                         "SdA" => "⟨*",
                         "SdI" => "⟪",
                         "SdIR" => "⟪(*",
                         "SdIU" => "⟪[",
                         "SdID" => "⟪{",
                         "SdIA" =>"⟪⟨*",
                         "S"   => "}}"),
          "U" => array( "S" => "]"),
          //Double TCM States
          "DA" => array( "D" => "⟩",
                         "S" => "⟩}"),
          "DI" => array( "DIU" => "[",
                         "DIR" => "(*",
                         "DISd" => "{{",
                         "DIA" => "⟨*",
                         "D" => "⟫",
                         "S" => "⟫}"),
          "DR" => array("D" => ")",
                          "S" => ")}"),
          "DSd" => array( "D" => "}}",
                          "S" => "}}}"),
          "DU" => array("D" => "]",
                          "S" => "]}"),
          "IA" => array("I" => "⟩",
                          "S" => "⟩⟫"),
          "ID" => array("I" => "}",
                          "S" => "}⟫"),
          "IR" => array("I" => ")",
                          "IU" => ")[",
                          "S" => ")⟫"),
          "ISd" => array( "I" => "}}",
                          "S" => "}}⟫"),
          "IU" => array("I" => "]",
                          "IR" => "](*",
                          "S" => "]⟫"),
          "SdA" => array("Sd" => "⟩",
                          "S" => "⟩}}"),
          "SdD" => array("Sd" => "}",
                          "S" => "}}}"),
          "SdI" => array( "SdIR" => "(*",
                          "SdIU" => "[",
                          "SdID" => "{",
                          "SdIA" =>"⟨*",
                          "Sd" => "⟫",
                          "S" => "⟫}}"),
          "SdR" => array( "Sd" => ")",
                          "S" => ")}}"),
          "SdU" => array( "Sd" => "]",
                          "S" => "]}}"),
          //Triple TCM States
          "DIA" => array( "DI" => "⟩",
                          "D" => "⟩⟫",
                          "S" => "⟩⟫}"),
          "DIR" => array( "DI" => ")",
                          "D" => ")⟫",
                          "S" => ")⟫}"),
          "DISd" => array( "DI" => "}}",
                          "D" => "}}⟫",
                          "S" => "}}⟫}"),
          "DIU" => array( "DI" => "]",
                          "D" => "]⟫",
                          "S" => "]⟫}"),
          "SdIA" => array( "SdI" => "⟩",
                          "Sd" => "⟩⟫",
                          "S" => "⟩⟫}}"),
          "SdID" => array( "SdI" => "}",
                          "Sd" => "}⟫",
                          "S" => "}⟫}}"),
          "SdIR" => array( "SdI" => ")",
                          "Sd" => ")⟫",
                          "S" => ")⟫}}"),
          "SdIU" => array( "SdI" => "]",
                          "Sd" => "]⟫",
                          "S" => "]⟫}}"));
      if (!$curState){
        $curState = "S";
      }
      if (!$nextState){
        $nextState = "S";
      }
      if ($curState == $nextState){
        return ($postNpre?array("",""):"");
      }
      if (!array_key_exists($curState,$tcmBrackets) || !array_key_exists($nextState,$tcmBrackets[$curState])) {
        if(array_key_exists('S',$tcmBrackets[$curState]) && array_key_exists($nextState,$tcmBrackets['S'])) {
          $postbrackets = $tcmBrackets[$curState]['S'];
          $prebrackets = $tcmBrackets['S'][$nextState];
          return ($postNpre?array($postbrackets,$prebrackets):$postbrackets.$prebrackets);
        } else {
          return ($postNpre?array("",""):"");
        }
      }
      $brackets = $tcmBrackets[$curState][$nextState];
      if (!$postNpre) {
        return $brackets;
      } else {
        $postbrackets = $prebrackets = $matches = "";
        if (mb_ereg("[\]\}\)⟩⟫]+",$brackets,$matches)) {
          $postbrackets = $matches[0];
          if (strlen($postbrackets) < strlen($brackets)) {
            $prebrackets = substr($brackets,strlen($postbrackets));
          }
        } else {
          $prebrackets = $brackets;
        }
        return array($postbrackets,$prebrackets);
      }
    }
/**
* TCM sort code lookup
* bit flag encoding where bit order from high to low = U(32), A(16), R(8), D(4), I(2), Sd(1)
*/
    $_tcmSortCodeLookup = array(
      "A" => 16,
      "D" => 4,
      "DA" => 20,
      "DI" => 6,
      "DIA" => 22,
      "DIR" => 14,
      "DISd" => 7,
      "DIU" => 38,
      "DR" => 12,
      "DSd" => 5,
      "DU" => 36,
      "I" => 2,
      "IA" => 18,
      "ID" => 6,
      "IR" =>10,
      "ISd" =>3,
      "IU" => 34,
      "R" => 8,
      "S" => 0,
      "Sd" => 1,
      "SdA" => 17,
      "SdD" => 5,
      "SdI" => 3,
      "SdIA" => 19,
      "SdID" => 7,
      "SdIR" => 11,
      "SdIU" => 35,
      "SdR" => 9,
      "SdU" => 33,
      "U" => 32
    );

?>
