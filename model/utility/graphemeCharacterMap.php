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
$jsonGCMString = '{"a": {"srt": 100, "typ": "V", ":": {"srt": 105, "typ": "V"}}, "b": {"\'": {"srt": 110, "typ": "C"}}, "c": {"h": {"srt": 120, "typ": "C", "\'": {"srt": 125, "typ": "C"}}}, "e": {"srt": 130, "typ": "V", ":": {"srt": 135, "typ": "V"}}, "h": {"srt": 140, "typ": "C"}, "i": {"srt": 150, "typ": "V", ":": {"srt": 155, "typ": "V"}}, "j": {"srt": 160, "typ": "C"}, "k": {"srt": 170, "typ": "C", "\'": {"srt": 175, "typ": "C"}}, "l": {"srt": 180, "typ": "C"}, "m": {"srt": 190, "typ": "C"}, "n": {"srt": 200, "typ": "C"}, "o": {"srt": 210, "typ": "V", ":": {"srt": 215, "typ": "V"}}, "p": {"srt": 220, "typ": "C", "\'": {"srt": 225, "typ": "C"}}, "s": {"srt": 230, "typ": "C"}, "t": {"srt": 240, "typ": "C", "\'": {"srt": 245, "typ": "C"}, "z": {"srt": 250, "typ": "C", "\'": {"srt": 255, "typ": "C"}}}, "u": {"srt": 260, "typ": "V", ":": {"srt": 265, "typ": "V"}}, "w": {"srt": 270, "typ": "C"}, "x": {"srt": 280, "typ": "C"}, "y": {"srt": 290, "typ": "C"}, "0": {"srt": 700, "typ": "N"}, "1": {"srt": 710, "typ": "N"}, "2": {"srt": 720, "typ": "N"}, "3": {"srt": 730, "typ": "N"}, "4": {"srt": 740, "typ": "N"}, "5": {"srt": 750, "typ": "N"}, "6": {"srt": 760, "typ": "N"}, "7": {"srt": 770, "typ": "N"}, "8": {"srt": 780, "typ": "N"}, "9": {"srt": 790, "typ": "N"}, "½": {"srt": 705, "typ": "N"}, "·": {"srt": 1, "typ": "M"}, ":": {"srt": 10, "typ": "M"}, "\'": {"srt": 11, "typ": "M"}, ".": {"srt": 89, "typ": "V"}, "’": {"srt": 94, "typ": "C"}, "ʔ": {"srt": 95, "typ": "C"}, "°": {"srt": 95, "typ": "C"}, "*": {"srt": 99, "typ": "V"}, "_": {"srt": 599, "typ": "C"}, "‧": {"srt": 800, "typ": "P"}, "×": {"srt": 801, "typ": "P"}, "∙": {"srt": 804, "typ": "I"}, "•": {"srt": 805, "typ": "P"}, "⁝": {"srt": 806, "typ": "P"}, "⏑": {"srt": 807, "typ": "P"}, "⎼": {"srt": 808, "typ": "P"}, "◦": {"srt": 810, "typ": "P"}, "○": {"srt": 820, "typ": "P"}, "◯": {"srt": 821, "typ": "I"}, "∈": {"srt": 830, "typ": "P"}, "☸": {"srt": 840, "typ": "P"}, "☒": {"srt": 845, "typ": "P"}, "❉": {"srt": 850, "typ": "P"}, "❀": {"srt": 851, "typ": "P"}, "|": {"srt": 860, "typ": "P", "|": {"srt": 870, "typ": "P"}}, "⌇": {"srt": 880, "typ": "P"}, "◊": {"srt": 885, "typ": "I"}, "◈": {"srt": 885, "typ": "I"}, "–": {"srt": 890, "typ": "P"}, "—": {"srt": 890, "typ": "P"}, "?": {"srt": 950, "typ": "O"}, "+": {"srt": 953, "typ": "O"}, "/": {"/": {"/": {"srt": 954, "typ": "O"}}, "srt": 959, "typ": "O"}, "…": {"srt": 955, "typ": "O"}, "#": {"srt": 956, "typ": "O"}, "A": {"B": {"\'": {"A": {"K": {"srt": 300, "typ": "L"}}}}, "H": {"I": {":": {"N": {"srt": 301, "typ": "L"}}}}, "J": {"srt": 302, "typ": "L", "A": {"N": {"srt": 303, "typ": "L"}, "W": {"srt": 304, "typ": "L"}}}, ":": {"K": {"srt": 305, "typ": "L", "\'": {"srt": 307, "typ": "L"}, "A": {"B": {"\'": {"srt": 308, "typ": "L"}}, "N": {"srt": 310, "typ": "L"}}}, "N": {"srt": 315, "typ": "L"}, "T": {"srt": 318, "typ": "L"}}, "K": {"\'": {"srt": 306, "typ": "L", "A": {":": {"B": {"\'": {"srt": 311, "typ": "L"}}}}, "B": {"\'": {"A": {":": {"L": {"srt": 312, "typ": "L"}}}}}}, "A": {"N": {"srt": 309, "typ": "L"}}}, "L": {"srt": 313, "typ": "L"}, "N": {"srt": 314, "typ": "L", '.
                  '"U": {"M": {"srt": 316, "typ": "L"}}}, "T": {"srt": 317, "typ": "L"}}, "B": {"\'": {"A": {":": {"H": {"srt": 319, "typ": "L"}, "K": {"srt": 321, "typ": "L"}, "L": {"A": {"M": {"srt": 322, "typ": "L"}}}, "X": {"srt": 325, "typ": "L"}}, "J": {"srt": 320, "typ": "L"}, "L": {"U": {":": {"N": {"srt": 323, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 324, "typ": "L"}}}}}}}}}}, "T": {"Z": {"srt": 326, "typ": "L", "\'": {"srt": 327, "typ": "L"}}}}, "E": {":": {"H": {"srt": 328, "typ": "L"}}, "N": {"srt": 329, "typ": "L"}}, "I": {"H": {"srt": 330, "typ": "L"}, "X": {"srt": 331, "typ": "L"}}, "O": {"L": {"A": {"Y": {"srt": 332, "typ": "L"}}, "O": {"N": {"srt": 333, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 334, "typ": "L"}}}}}}}}}}, "U": {":": {"L": {"srt": 335, "typ": "L"}}, "L": {"U": {"C": {"H": {"srt": 336, "typ": "L"}}, "K": {"srt": 337, "typ": "L"}}}}}}, "C": {"H": {"A": {"\'": {"srt": 338, "typ": "L"}, "B": {"\'": {"srt": 339, "typ": "L"}}, "K": {"srt": 340, "typ": "L"}, ":": {"K": {"srt": 341, "typ": "L"}}, "M": {"srt": 342, "typ": "L"}, "N": {"srt": 343, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 344, "typ": "L"}}}}}}}, "P": {"A": {":": {"T": {"srt": 345, "typ": "L"}}}}, "Y": {"srt": 346, "typ": "L"}}, "E": {"L": {"srt": 347, "typ": "L"}}, "I": {"J": {"srt": 348, "typ": "L"}, ":": {"K": {"srt": 349, "typ": "L"}}, "K": {"C": {"H": {"A": {"N": {"srt": 350, "typ": "L"}}}}}, "T": {"srt": 351, "typ": "L", "A": {"M": {"srt": 352, "typ": "L"}}}}, "O": {"K": {"srt": 353, "typ": "L"}}, "U": {"K": {"srt": 354, "typ": "L"}, "M": {"srt": 355, "typ": "L"}, "W": {"A": {":": {"J": {"srt": 356, "typ": "L"}}}, "E": {"N": {"srt": 357, "typ": "L"}}}}, "\'": {"A": {":": {"B": {"\'": {"srt": 358, "typ": "L"}}, "J": {"srt": 361, "typ": "L"}}, "H": {"srt": 359, "typ": "L", "O": {"M": {"srt": 360, "typ": "L"}}}, "K": {"srt": 362, "typ": "L"}, "M": {"srt": 363, "typ": "L", "A": {"K": {"srt": 364, "typ": "L"}}}}, "E": {":": {"N": {"srt": 365, "typ": "L"}}}, "I": {"C": {"H": {"\'": {"srt": 366, "typ": "L"}}}}, "O": {"K": {"srt": 367, "typ": "L"}}, "U": {"L": {"srt": 368, "typ": "L"}}}}}, "E": {":": {"B": {"\'": {"srt": 369, "typ": "L"}}, "K": {"\'": {"srt": 371, "typ": "L"}}, "M": {"srt": 374, "typ": "L"}}, "K": {"\'": {"srt": 370, "typ": "L"}}, "L": {"srt": 372, "typ": "L", "K": {"\'": {"I": {"N": {"srt": 373, "typ": "L"}}}}}, "T": {"Z": {"\'": {"N": {"A": {"B": {"\'": {"srt": 375, "typ": "L"}}}}}}}}, "H": {"A": {"\'": {"srt": 376, "typ": "L"}, ":": {"B": {"\'": {"srt": 377, "typ": "L"}}, "L": {"srt": 378, "typ": "L"}}, "L": {"A": {"W": {"srt": 379, "typ": "L"}}}}, "I": {"N": {"A": {":": {"J": {"srt": 380, "typ": "L"}}}}, "X": {"srt": 381, "typ": "L"}}, "O": {"\'": {"srt": 382, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 383, "typ": "L"}}}}}}}}, "U": {":": {"J": {"srt": 384, "typ": "L"}, "N": {"srt": 389, "typ": "L"}}, "K": {"srt": 385, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 386, "typ": "L"}}}}}}}, "L": {"srt": 387, "typ": "L"}, "N": {"srt": 388, "typ": "L"}, "T": {"srt": 390, "typ": "L"}, "X": {"srt": 391, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 392, "typ": "L"}}}}}}}}}, "I": {"\'": {"srt": 393, "typ": "L"}, "B": {"\'": {"?": {"srt": 394, "typ": "L"}, "A": {":": {"C": {"H": {"srt": 395, "typ": "L"}}}}}}, "C": {"H": {"srt": 396, "typ": "L", "I": {"L": {"srt": 397, "typ": "L"}}}}, ":": {"C": {"H": {"\'": {"A": {"K": {"srt": 398, "typ": "L"}}}}}, "K": {"\'": {"srt": 400, "typ": "L"}}}, "K": {"\'": {"srt": 399, "typ": "L"}}, "L": {"srt": 401, "typ": "L"}, "M": {"I": {"X": {"srt": 402, "typ": "L"}}}, "P": {"srt": 403, "typ": "L"}, "T": {"Z": {"\'": {"A": {":": {"T": {"srt": 404, "typ": "L"}}, "T": {"srt": 406, "typ": "L"}}}, '.
                  '"A": {"M": {"srt": 405, "typ": "L"}}}}, "X": {"srt": 407, "typ": "L", "I": {"K": {"srt": 408, "typ": "L"}}}}, "J": {"A": {"L": {"srt": 409, "typ": "L"}, "N": {"srt": 410, "typ": "L", "A": {"B": {"\'": {"srt": 411, "typ": "L"}}}}, "T": {"Z": {"\'": {"srt": 412, "typ": "L", "O": {":": {"M": {"srt": 413, "typ": "L"}}}}}}}, "E": {"L": {"srt": 414, "typ": "L"}}, "O": {":": {"L": {"srt": 415, "typ": "L"}}, "P": {"srt": 416, "typ": "L"}, "Y": {"srt": 417, "typ": "L"}}, "U": {"\'": {"srt": 418, "typ": "L"}, "B": {"\'": {"srt": 419, "typ": "L"}}, ":": {"B": {"\'": {"srt": 420, "typ": "L"}}, "N": {"srt": 424, "typ": "L"}}, "K": {"U": {"B": {"\'": {"srt": 421, "typ": "L"}}}}, "L": {"srt": 422, "typ": "L"}, "N": {"srt": 423, "typ": "L"}}}, "K": {"A": {"\'": {"srt": 425, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 426, "typ": "L"}}}}}}}, "B": {"\'": {"srt": 427, "typ": "L", "A": {"N": {"srt": 428, "typ": "L"}}, "K": {"O": {"H": {"srt": 429, "typ": "L"}}}}}, ":": {"J": {"srt": 430, "typ": "L"}, "N": {"srt": 436, "typ": "L"}}, "L": {"srt": 431, "typ": "L", "O": {"M": {"srt": 432, "typ": "L"}}, "T": {"E": {"\'": {"srt": 433, "typ": "L"}}}}, "M": {"srt": 434, "typ": "L"}, "N": {"srt": 435, "typ": "L", "K": {"A": {"Y": {"srt": 437, "typ": "L"}}}, "L": {"A": {"J": {"U": {":": {"N": {"srt": 438, "typ": "L"}}}}}}}, "W": {"A": {"K": {"srt": 439, "typ": "L"}}}, "Y": {"srt": 440, "typ": "L"}}, "E": {"J": {"srt": 441, "typ": "L"}, "L": {"srt": 442, "typ": "L", "E": {":": {"M": {"srt": 443, "typ": "L"}}}}}, "I": {"B": {"\'": {"srt": 444, "typ": "L"}}, ":": {"M": {"srt": 445, "typ": "L"}}, "M": {"I": {"srt": 446, "typ": "L"}}, "S": {"I": {"N": {"srt": 447, "typ": "L"}}}}, "O": {"H": {"srt": 448, "typ": "L"}, ":": {"H": {"A": {"W": {"srt": 449, "typ": "L"}}}, "J": {"srt": 450, "typ": "L"}, "K": {"srt": 451, "typ": "L"}}, "K": {"A": {":": {"J": {"srt": 452, "typ": "L"}}, "N": {"srt": 453, "typ": "L"}}}}, "U": {"C": {"H": {"srt": 454, "typ": "L"}}, "H": {"K": {"A": {"Y": {"srt": 455, "typ": "L"}}}}, "M": {"srt": 456, "typ": "L"}, "T": {"Z": {"srt": 457, "typ": "L"}}, ":": {"T": {"Z": {"srt": 458, "typ": "L"}}}, "Y": {"srt": 459, "typ": "L"}}, "\'": {"A": {"\'": {"srt": 460, "typ": "L"}, "B": {"\'": {"srt": 461, "typ": "L", "A": {"\'": {"srt": 462, "typ": "L"}}}}, "H": {"srt": 463, "typ": "L"}, ":": {"K": {"\'": {"srt": 464, "typ": "L"}}}, "L": {"srt": 465, "typ": "L"}, "N": {"srt": 466, "typ": "L", "K": {"\'": {"I": {"N": {"srt": 467, "typ": "L"}}}}}, "T": {"srt": 468, "typ": "L"}, "W": {"I": {":": {"L": {"srt": 469, "typ": "L"}}}}, "Y": {"srt": 470, "typ": "L"}}, "E": {"K": {"\'": {"E": {"N": {"srt": 471, "typ": "L"}}}}, "W": {"srt": 472, "typ": "L"}}, "I": {"K": {"\'": {"srt": 473, "typ": "L"}}, "N": {"srt": 474, "typ": "L", "I": {"C": {"H": {"srt": 475, "typ": "L"}}}, "T": {"U": {"N": {"srt": 476, "typ": "L"}}}}, "X": {"srt": 477, "typ": "L"}}, "O": {":": {"B": {"\'": {"srt": 478, "typ": "L"}}}}, "U": {"C": {"H": {"srt": 479, "typ": "L"}}, "H": {"srt": 480, "typ": "L"}, "K": {"\'": {"srt": 481, "typ": "L", "U": {"M": {"srt": 482, "typ": "L"}}}}, ":": {"L": {"srt": 483, "typ": "L"}}}}}, '.
                  '"L": {"A": {"J": {"srt": 484, "typ": "L", "C": {"H": {"A": {"\'": {"srt": 485, "typ": "L"}}}}, "U": {":": {"N": {"srt": 486, "typ": "L"}}}}, "K": {"srt": 487, "typ": "L", "A": {"M": {"srt": 488, "typ": "L"}}}, "M": {"srt": 489, "typ": "L", "A": {"T": {"srt": 490, "typ": "L"}}}}, "E": {":": {"M": {"srt": 491, "typ": "L"}}}, "O": {":": {"B": {"\'": {"srt": 492, "typ": "L"}}, "T": {"srt": 494, "typ": "L"}}, "K": {"\'": {"srt": 493, "typ": "L"}}}}, "M": {"A": {"K": {"srt": 495, "typ": "L"}, ":": {"K": {"srt": 496, "typ": "L"}, "N": {"srt": 499, "typ": "L"}, "S": {"srt": 501, "typ": "L"}, "X": {"srt": 503, "typ": "L"}, "Y": {"srt": 505, "typ": "L"}}, "M": {"srt": 497, "typ": "L"}, "N": {"srt": 498, "typ": "L", "I": {"K": {"\'": {"srt": 500, "typ": "L"}}}}, "T": {"srt": 502, "typ": "L"}, "Y": {"srt": 504, "typ": "L"}}, "E": {"N": {"srt": 506, "typ": "L"}}, "I": {"H": {"I": {":": {"N": {"srt": 507, "typ": "L"}}}}, "X": {"srt": 508, "typ": "L"}}, "O": {"\'": {"srt": 509, "typ": "L"}}, "U": {"K": {"srt": 510, "typ": "L"}, ":": {"K": {"srt": 511, "typ": "L"}, "T": {"srt": 513, "typ": "L"}}, "L": {"U": {"K": {"srt": 512, "typ": "L"}}}, "W": {"A": {":": {"N": {"srt": 514, "typ": "L"}}}}, "Y": {"srt": 515, "typ": "L", "A": {"L": {"srt": 516, "typ": "L"}}}}}, "N": {"A": {"\'": {"srt": 517, "typ": "L"}, ":": {"B": {"\'": {"srt": 518, "typ": "L"}}, "H": {"srt": 520, "typ": "L"}, "K": {"srt": 521, "typ": "L"}, "M": {"srt": 523, "typ": "L"}}, "H": {"srt": 519, "typ": "L"}, "L": {"srt": 522, "typ": "L"}}, "E": {"H": {"srt": 524, "typ": "L"}}, "I": {"K": {"srt": 525, "typ": "L", "T": {"E": {"\'": {"srt": 526, "typ": "L"}}}}}, "O": {"H": {"srt": 527, "typ": "L", "O": {"L": {"srt": 528, "typ": "L"}}}}, "U": {"K": {"srt": 529, "typ": "L"}, ":": {"N": {"srt": 530, "typ": "L"}}}}, "O": {"C": {"H": {"srt": 531, "typ": "L", "K": {"\'": {"I": {"N": {"srt": 533, "typ": "L"}}}}}}, ":": {"C": {"H": {"srt": 532, "typ": "L"}}, "K": {"srt": 534, "typ": "L"}, "L": {"srt": 536, "typ": "L"}, "M": {"srt": 537, "typ": "L"}, "N": {"srt": 538, "typ": "L"}, "X": {"srt": 541, "typ": "L"}}, "K": {"\'": {"I": {"N": {"srt": 535, "typ": "L"}}}}, "T": {"O": {":": {"T": {"srt": 539, "typ": "L"}}}}, "X": {"L": {"A": {"J": {"U": {":": {"N": {"srt": 540, "typ": "L"}}}}}}}}, "P": {"A": {"\'": {"srt": 542, "typ": "L"}, "C": {"H": {"srt": 543, "typ": "L"}}, "K": {"A": {"L": {"srt": 544, "typ": "L"}}}, "L": {"A": {"W": {"srt": 545, "typ": "L"}}}, "S": {"srt": 546, "typ": "L"}, "T": {"srt": 547, "typ": "L"}, ":": {"T": {"srt": 548, "typ": "L"}, "X": {"srt": 549, "typ": "L", "I": {"L": {"srt": 550, "typ": "L"}}}}}, "E": {":": {"K": {"\'": {"srt": 551, "typ": "L"}}}, "T": {"srt": 552, "typ": "L"}}, "I": {"H": {"srt": 553, "typ": "L"}, "K": {"srt": 554, "typ": "L"}, ":": {"T": {"srt": 555, "typ": "L"}}}, "O": {"L": {"srt": 556, "typ": "L"}, ":": {"P": {"srt": 557, "typ": "L"}}}, "U": {"H": {"srt": 558, "typ": "L"}, ":": {"K": {"srt": 559, "typ": "L"}, "T": {"Z": {"\'": {"srt": 561, "typ": "L"}}}}, "L": {"srt": 560, "typ": "L"}}}, "S": {"A": {"\'": {"srt": 562, "typ": "L"}, "J": {"srt": 563, "typ": "L"}, "K": {"srt": 564, "typ": "L"}, '.
                  '":": {"K": {"srt": 565, "typ": "L"}}}, "E": {"L": {"srt": 566, "typ": "L"}}, "I": {"B": {"\'": {"I": {"K": {"srt": 567, "typ": "L", "T": {"E": {"\'": {"srt": 568, "typ": "L"}}}}}}}, "H": {"srt": 569, "typ": "L", "O": {":": {"M": {"srt": 570, "typ": "L"}}}}, "P": {"srt": 571, "typ": "L"}, "Y": {"srt": 572, "typ": "L", "A": {"N": {"srt": 573, "typ": "L"}}}}, "U": {"H": {"U": {"Y": {"srt": 574, "typ": "L"}}}, "M": {"srt": 575, "typ": "L"}, ":": {"T": {"Z": {"\'": {"srt": 576, "typ": "L"}}}}}}, "T": {"A": {":": {"H": {"O": {"L": {"srt": 577, "typ": "L"}}}, "K": {"srt": 579, "typ": "L"}, "N": {"srt": 581, "typ": "L"}}, "J": {"srt": 578, "typ": "L"}, "L": {"srt": 580, "typ": "L"}, "Y": {"srt": 582, "typ": "L"}}, "E": {"\'": {"srt": 583, "typ": "L"}, "L": {"E": {"S": {"srt": 584, "typ": "L"}}}}, "I": {"\'": {"srt": 585, "typ": "L"}, "L": {"srt": 586, "typ": "L"}}, "O": {":": {"K": {"\'": {"srt": 587, "typ": "L"}}}}, "U": {":": {"N": {"srt": 588, "typ": "L"}}, "P": {"srt": 589, "typ": "L"}}, "\'": {"A": {"B": {"\'": {"srt": 590, "typ": "L"}}}, "O": {"L": {"O": {"K": {"srt": 591, "typ": "L"}}}}, "U": {"L": {"srt": 592, "typ": "L"}}}, "Z": {"A": {"K": {"srt": 593, "typ": "L"}}, "I": {"H": {"srt": 594, "typ": "L"}}, "U": {"\'": {"srt": 595, "typ": "L"}, "K": {"srt": 596, "typ": "L"}, "L": {"srt": 597, "typ": "L"}, "T": {"Z": {"srt": 598, "typ": "L"}}}, "\'": {"A": {"K": {"srt": 599, "typ": "L"}, "M": {"srt": 600, "typ": "L"}, "P": {"srt": 601, "typ": "L"}}, "I": {"\'": {"srt": 602, "typ": "L"}, "K": {"I": {":": {"N": {"srt": 603, "typ": "L"}}}}}, "O": {"N": {"O": {":": {"T": {"srt": 604, "typ": "L"}}}}}, "U": {"L": {"srt": 605, "typ": "L"}, "N": {"U": {":": {"N": {"srt": 606, "typ": "L"}}}}, "T": {"Z": {"\'": {"I": {"H": {"srt": 607, "typ": "L"}}}}}}}}}, "U": {"H": {"srt": 608, "typ": "L"}, "K": {"\'": {"srt": 609, "typ": "L"}}, "N": {"srt": 610, "typ": "L", "E": {"N": {"srt": 612, "typ": "L"}}}, ":": {"N": {"srt": 611, "typ": "L"}}, "S": {"I": {":": {"J": {"srt": 613, "typ": "L"}}}}, "T": {"srt": 614, "typ": "L"}, "X": {"srt": 615, "typ": "L"}}, "W": {"A": {"\'": {"srt": 616, "typ": "L"}, ":": {"J": {"srt": 617, "typ": "L"}}, "K": {"srt": 618, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 619, "typ": "L"}}}}}}}, "L": {"srt": 620, "typ": "L"}, "W": {"srt": 621, "typ": "L"}, "X": {"A": {"K": {"srt": 622, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 623, "typ": "L"}}}}}}}}}, "Y": {"srt": 624, "typ": "L", "I": {"S": {"srt": 625, "typ": "L"}}}}, "E": {"\'": {"srt": 626, "typ": "L"}}, "I": {"\'": {"srt": 627, "typ": "L"}, ":": {"N": {"srt": 628, "typ": "L"}}, "N": {"A": {":": {"K": {"srt": 629, "typ": "L"}}, "L": {"srt": 630, "typ": "L"}}, "I": {"K": {"srt": 631, "typ": "L", "H": {"A": {":": {"B": {"\'": {"srt": 632, "typ": "L"}}}}}}}}, "T": {"Z": {"srt": 633, "typ": "L", "\'": {"srt": 634, "typ": "L"}}}}, "O": {":": {"L": {"srt": 635, "typ": "L"}}}, "U": {"K": {"srt": 636, "typ": "L", "L": {"A": {"J": {"U": {":": {"N": {"srt": 637, "typ": "L"}}}}}}}, "T": {"srt": 638, "typ": "L"}}}, "X": {"A": {"M": {"A": {"N": {"srt": 639, "typ": "L"}}}}, "I": {"B": {"\'": {"srt": 640, "typ": "L"}}, "W": {"srt": 641, "typ": "L"}}, "O": {":": {"K": {"srt": 642, "typ": "L"}}}, "U": {"K": {"U": {"B": {"\'": {"srt": 643, "typ": "L"}}}}, "L": {"srt": 644, "typ": "L"}}}, "Y": {"A": {"J": {"srt": 645, "typ": "L"}, ":": {"N": {"srt": 646, "typ": "L"}}, "T": {"I": {"K": {"srt": 647, "typ": "L"}}}, "X": {"srt": 648, "typ": "L", "U": {":": {"N": {"srt": 649, "typ": "L"}}}}}, "O": {":": {"K": {"srt": 650, "typ": "L"}, "N": {"srt": 651, "typ": "L"}, "T": {"Z": {"srt": 652, "typ": "L"}}}, "P": {"srt": 653, "typ": "L", "A": {":": {"T": {"srt": 654, "typ": "L"}}}}}, "U": {"K": {"srt": 655, "typ": "L"}}}}';
$graphemeCharacterMap = json_decode($jsonGCMString,true,30);


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
