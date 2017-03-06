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
//----------------------------------------------------------------------------//
//  XMLNode construction helpers
//----------------------------------------------------------------------------//
/**
 * makeXMLNode creates a xml node element.
 *
 * @param     string $name of the node
 * @param     array $attributes of string key-value pairs
 * @param     string $textContent to be the value of this node
 * @param     boolean $close (default true) indicating whether to close the node
 * @param     boolean $encodeContent (default true) indicating if the content should be run through htmlspecialchars
 * @return    string xml node
 */
function makeXMLNode($name, $attributes = null, $textContent = null, $close = true, $encodeContent = true) {
    $node = "<$name";
    if (is_array($attributes)) {
        foreach ($attributes as $attr => $value) {
            $node.= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
        }
    }
    if ($close && !$textContent) {
        $node.= '/>';
    } else {
        $node.= '>';
    }
    if ($textContent) {
        if ($encodeContent) {
            $node.= htmlspecialchars($textContent);
        } else {
            $node.= $textContent;
        }
        if ($close) $node.= "</$name>";
    }
    return $node . "\n";
    /*****DEBUG****///  error_log("in makeXMLNode node = $node");

}
/**
 * openXMLNode opens an xml $name element with optional attributes
 *
 * @param     string $name of the node
 * @param     array $attributes of string key-value pairs
 * @return    string xml node start
 * @uses      makeXMLNode
 */
function openXMLNode($name, $attributes = null) {
    return makeXMLNode($name, $attributes, null, false);
}
/**
 *  closes an xml $name element
 *
 * @param     string $name of the node
 * @return    string xml node end
 */
function closeXMLNode($name) {
    return "</$name>\n";
}
/**
 * openCDATA starts a CDATA node
 *
 * @return    string xml CDATA node start
 */
function openCDATA() {
    return "<![CDATA[\n";
}
/**
 * closeCDATA closes a CDATA node
 *
 * @return    string xml CDATA node end
 */
function closeCDATA() {
    return "]]>\n";
}
/**
 * startRML creates root node elements to begin an RML document
 *
 * @param     array $namespaces of string key-value pairs of the form 'xmlns[:prefix]' 'url" that are added to the root node
 * @param     boolean $includeXMLNode (default true) indicating whether to include the xml document node
 * @param     string $version (default 1.0) used to set the xml version of this document
 * @return    string xml root node start
 * @uses      makeXMLNode
 */
function startRML($namespaces = null, $includeXMLNode = true, $version = "1.0") {
    $ret = "";
    if ($includeXMLNode) {
      $ret .= "<?xml version='$version' encoding='UTF-8'?>\n";
    }
    $ret .= makeXMLNode("rml", $namespaces, null, false);
    return $ret;
}
/**
 * endRML returns the RML closing node
 *
 * @return    string xml root node end
 * @uses      closeXMLNode
 */
function endRML() {
    return closeXMLNode("rml");
}
?>
