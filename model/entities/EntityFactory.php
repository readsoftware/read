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
  * Functions to create entities
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  * @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
  * @link        https://github.com/readsoftware
  * @version     1.0
  * @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
  * @package     READ Research Environment for Ancient Documents
  * @subpackage  Entity Classes
  */
  require_once (dirname(__FILE__) . '/EntityIterator.php');
  require_once (dirname(__FILE__) . '/Entity.php');
  require_once (dirname(__FILE__) . '/Annotation.php');
  require_once (dirname(__FILE__) . '/Attribution.php');
  require_once (dirname(__FILE__) . '/AttributionGroup.php');
  require_once (dirname(__FILE__) . '/Baseline.php');
  require_once (dirname(__FILE__) . '/Bibliography.php');
  require_once (dirname(__FILE__) . '/Collection.php');
  require_once (dirname(__FILE__) . '/Item.php');
  require_once (dirname(__FILE__) . '/Part.php');
  require_once (dirname(__FILE__) . '/Fragment.php');
  require_once (dirname(__FILE__) . '/Image.php');
  require_once (dirname(__FILE__) . '/Span.php');
  require_once (dirname(__FILE__) . '/Surface.php');
  require_once (dirname(__FILE__) . '/Text.php');
  require_once (dirname(__FILE__) . '/TextMetadata.php');
  require_once (dirname(__FILE__) . '/Segment.php');
  require_once (dirname(__FILE__) . '/Run.php');
  require_once (dirname(__FILE__) . '/Line.php');
  require_once (dirname(__FILE__) . '/SyllableCluster.php');
  require_once (dirname(__FILE__) . '/Grapheme.php');
  require_once (dirname(__FILE__) . '/Token.php');
  require_once (dirname(__FILE__) . '/Compound.php');
  require_once (dirname(__FILE__) . '/Lemma.php');
  require_once (dirname(__FILE__) . '/Inflection.php');
  require_once (dirname(__FILE__) . '/Term.php');
  require_once (dirname(__FILE__) . '/ProperNoun.php');
  require_once (dirname(__FILE__) . '/Catalog.php');
  require_once (dirname(__FILE__) . '/Sequence.php');
  require_once (dirname(__FILE__) . '/Link.php');
  require_once (dirname(__FILE__) . '/Edition.php');
  require_once (dirname(__FILE__) . '/Date.php');
  require_once (dirname(__FILE__) . '/Era.php');

//*******************************************************************
//*********************   ENTITYFACTORY CLASS   *****************************
//*******************************************************************
/**
  * EntityFactory class encapsulates the creation of entities
  *
  * <code>
  * require_once 'OrderedSet.php';
  * $globalIDs = array( "tok:5","cmp:15","seq:47","frg:25");
  *
  * $entitySet = new OrderedSet();
  * $entitySet->loadObjects($globalIDsArray);
  * $entity = $entitySet->current();
  * $key = $entitySet->key();
  * echo " set member type ".$object->getEntityType()." has key $key";
  * </code>
  *
  * @author      Stephen White  <stephenawhite57@gmail.com>
  */

  class EntityFactory {


    static public $error;

    /**
    * Create Entity From GlobalID
    *
    * @param $globalIDsArray is a postgresql array of strings of the form array("itm"=>array(1,3),"frg"=>array(3))
    * @return true|false  returns true if the current position has a loaded object, false otherwise
    */

    static public function createEntityFromGlobalID($globalID){
      $error = null;
      $globalID = str_replace(":","",$globalID);
      $prefix = substr($globalID,0,3);
      $id = substr($globalID,3);
      if (!$prefix || !$id || !is_numeric($id)){
        $error = "create error = invalid globalID $globalID -";
        return null;
      }
      return EntityFactory::createEntityFromPrefix($prefix,$id);
    }

    /**
    * Create Entity From Entity Prefix
    *
    * @param string $prefix is a 3 letter entity type identifier
    * @param int $id is an optional integer uniquely identifying an existing entity of type indicated by $prefix
    * @return Entity|null  returns true Entity was created, null otherwise
    */

    static public function createEntityFromPrefix($prefix,$id=null){
      $error = null;
      if (!$prefix ){
        $error = "create error = invalid prefix $prefix -";
        return null;
      }
      if (!in_array($prefix,Entity::$validPrefixes)){
        $error = "create error = invalid entity type $globalID -";
        return null;
      }
      switch ($prefix) {
        case "col":
          return new Collection($id);
        case "itm":
          return new Item($id);
        case "prt":
          return new Part($id);
        case "frg":
          return new Fragment($id);
        case "img":
          return new Image($id);
        case "spn":
          return new Span($id);
        case "srf":
          return new Surface($id);
        case "txt":
          return new Text($id);
        case "tmd":
          return new TextMetadata($id);
        case "bln":
          return new Baseline($id);
        case "seg":
          return new Segment($id);
        case "run":
          return new Run($id);
        case "lin":
          return new Line($id);
        case "scl":
          return new SyllableCluster($id);
        case "gra":
          return new Grapheme($id);
        case "tok":
          return new Token($id);
        case "cmp":
          return new Compound($id);
        case "lem":
          return new Lemma($id);
        case "inf":
          return new Inflection($id);
        case "trm":
          return new Term($id);
        case "prn":
          return new ProperNoun($id);
        case "cat":
          return new Catalog($id);
        case "seq":
          return new Sequence($id);
        case "lnk":
          return new Link($id);
        case "edn":
          return new Edition($id);
        case "bib":
          return new Bibliography($id);
        case "ano":
          return new Annotation($id);
        case "atb":
          return new Attribution($id);
        case "atg":
          return new AttributionGroup($id);
        case "dat":
          return new Date($id);
        case "era":
          return new Era($id);
        default:
          $error = "create error = unable to dispatch create for $globalID -";
          return null;
      }
    }

   }
?>
