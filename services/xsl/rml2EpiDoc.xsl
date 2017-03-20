<?xml version="1.0" encoding="UTF-8"?>
<!--
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
-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:exslt="http://exslt.org/common"
    extension-element-prefixes="exslt"
    xmlns:fn="http://www.w3.org/2005/xpath-functions"
    version="1.0">
    <xsl:output method="xml" version="1.0" omit-xml-declaration="no" encoding="UTF-8"  indent="yes"/>
    <!-- setup lookup table for linebreak detection -->
    <xsl:variable name="lineBreaks" >
            <xsl:for-each  select="/rml/entities/sequence[Type/link/@value = 'LinePhysical']">
                <xsl:variable name="graID" select="(/rml/entities/syllable[@id =(current()/Components/link)[1]/@id]/Graphemes/link)[1]/@id"/>
                <lbNode>
                    <xsl:attribute name="ord"><xsl:value-of select="position()"/></xsl:attribute>
                    <graID><xsl:value-of select="$graID"/></graID>
                    <sclID><xsl:value-of select="(current()/Components/link)[1]/@id"/></sclID>
                    <tokID><xsl:value-of select="/rml/entities/token[Graphemes/link/@id = $graID]/@id"/></tokID>
                    <lineN><xsl:value-of select="current()/DisplayLabel"/></lineN>
                    <seqID><xsl:value-of select="current()/@id"/></seqID>
                </lbNode>
            </xsl:for-each>
    </xsl:variable>
    <xsl:variable name="LBs" select="exslt:node-set($lineBreaks)/lbNode"/>

    <!-- runs through the physical line sequences finding the id of the last grapheme of the last syllable in the line -->
    <!-- captures this grapheme id with the physical lines label in a structure -->
    <xsl:template name="getLineBreaks">
        <xsl:param name="sequences"></xsl:param>
        <xsl:for-each select="$sequences">
                <xsl:variable name="graID" select="(/rml/entities/syllable[@id =(current()/Components/link)[1]/@id]/Graphemes/link)[1]/@id"/>
        <lbNode>
            <xsl:attribute name="ord"><xsl:value-of select="position()"/></xsl:attribute>
            <graID><xsl:value-of select="$graID"/></graID>
            <sclID><xsl:value-of select="(current()/Components/link)[1]/@id"/></sclID>
            <tokID><xsl:value-of select="/rml/entities/token[Graphemes/link/@id = $graID]/@id"/></tokID>
            <lineN><xsl:value-of select="current()/DisplayLabel"/></lineN>
            <seqID><xsl:value-of select="current()/@id"/></seqID>
        </lbNode>
        </xsl:for-each>
    </xsl:template>
    <xsl:variable name="text" select="/rml/entities/text"/>

    <xsl:template match="/">
        <TEI xmlns="http://www.tei-c.org/ns/1.0">
            <teiHeader>
                <fileDesc>
                    <titleStmt>
                        <title>
                            <xsl:value-of select="$text/Title"/>
                        </title>
                        <respStmt>
                            <resp>editor</resp>
                            <persName ref="#SB">
                                <forename>Stefan</forename>
                                <surname>Baums</surname>
                            </persName>
                        </respStmt>
                        <respStmt>
                            <resp>editor</resp>
                            <persName ref="#AG">
                                <forename>Arlo</forename>
                                <surname>Griffiths</surname>
                            </persName>
                        </respStmt>
                        <respStmt>
                            <resp>editor</resp>
                            <persName ref="#IS">
                                <forename>Ingo</forename>
                                <surname>Strauch</surname>
                            </persName>
                        </respStmt>
                        <respStmt>
                            <resp>editor</resp>
                            <persName ref="#VT">
                                <forename>Vincent</forename>
                                <surname>Tournier</surname>
                            </persName>
                        </respStmt>
                    </titleStmt>
                    <publicationStmt>
                        <authority>Early Inscriptions of Āndhradeśa</authority>
                        <idno type="filename"><xsl:value-of select="$text/Inv"/>.xml</idno>
                        <availability>
                            <!--licence target="http://creativecommons.org/licenses/by/3.0/"-->
                                <p>Copyright (c) 2017 by Stefan Baums, Arlo Griffiths, Ingo Strauch
                                    and Vincent Tournier.</p>
                                <p>This work is licensed under the Creative Commons Attribution 3.0
                                    Unported License. To view a copy of this license, visit
                                    http://creativecommons.org/licenses/by/3.0/ or send a letter to
                                    Creative Commons, 444 Castro Street, Suite 900, Mountain View,
                                    California, 94041, USA.</p>
                            <!--/licence-->
                        </availability>
                    </publicationStmt>
                    <sourceDesc>
                        <msDesc>
                            <msIdentifier>
                                <institution>EFEO</institution>
                                <idno xml:id="inv-eiad">EIAD <xsl:value-of select="substring($text/Inv,5)"/></idno>
                            </msIdentifier>
                            <msContents>
                                <msItem>
                                    <textLang xml:lang="en"><!-- fill in mainLang="pra-Brah" or mainLang="san-Brah" within <textLang> and supply corresponding descriptuve phrase. --></textLang>
                                </msItem>
                            </msContents>
                            <physDesc>
                                <objectDesc>
                                    <supportDesc>
                                        <support>
                                            <p><!--objectType--><!-- fill me in --><!--/objectType-->
                                                  <material><!-- fill me in --></material>
                                                  <dimensions unit="cm">
                                                  <!--[@unit must be verified]-->
                                                  <!-- to be structured with <height>, <width>, <depth>, etc.] -->
                                                </dimensions></p>
                                        </support>
                                    </supportDesc>
                                    <layoutDesc>
                                        <layout><!--Typically, something like this: <layout writtenLines="8">
                                        <p>Eight lines covering four facets of the pillar.</p>
                                        </layout>--></layout>
                                    </layoutDesc>
                                </objectDesc>
                                <handDesc>
                                    <handNote><!-- description of letters, possibly including <height>letter-heights</height> --></handNote>
                                </handDesc>
                                <decoDesc>
                                    <decoNote><!-- description of decoration or iconography --></decoNote>
                                </decoDesc>
                            </physDesc>
                            <history>
                                <origin>
                                    <origPlace><!-- fill me in --></origPlace>
                                    <origDate>
                                        <!--See http://www.stoa.org/epidoc/gl/latest/supp-historigdate.html-->
                                    </origDate>
                                </origin>
                                <!--provenance type="found"
                                    --><!-- Findspot and circumstances/context --><!--/provenance-->
                                <!--provenance type="observed"
                                --><!-- Modern location(s) (if different from repository, above); is used to encode information about subsequent modern observation --><!--/provenance-->
                                <!--provenance type="not-observed"
                                --><!-- information about a specific, unsuccessful attempt to locate an object in a presumed or previously recorded location --><!--/provenance-->
                                <!--provenance type="transferred"
                                --><!-- information about documentable modern relocations of the text-bearing object --><!--/provenance-->
                            </history>
                            <additional>
                                <surrogates><!-- fill me in; use <bibl> for published facsimiles --></surrogates>
                            </additional>
                        </msDesc>
                    </sourceDesc>
                    <!--xsl:if test="/rml/entities/attribution[@id=$text/attributions/link/@id]/title">
                    <sourceDesc>
                        <xsl:for-each select="/rml/entities/attribution[@id=$text/attributions/link/@id]/title">
                        <p>
                            <xsl:value-of select="."  disable-output-escaping="yes" />
                        </p>
                        </xsl:for-each>
                    </sourceDesc>
                    </xsl:if-->
                </fileDesc>
            </teiHeader>
            <text xml:lang="pra-Brah">
                <body>
                    <!--div type="physical"-->
                        <xsl:apply-templates select="/rml/entities/edition"/>
                    <!--/div-->
                </body>
            </text>
        </TEI>
    </xsl:template>

    <xsl:template match="edition" >
        <!-- edition representing a reading of the text possibly by a different author -->
        <!-- output Edition div -->
        <div>
        <xsl:attribute name="xml:id">
            <xsl:value-of select="concat('edn',@id)"/>
        </xsl:attribute>
        <xsl:attribute name="type">edition</xsl:attribute>
        <xsl:attribute name="xml:lang">pra-Brah</xsl:attribute>
        <xsl:attribute name="xml:space">preserve</xsl:attribute>

        <!-- process subcomponents of line -->
        <xsl:for-each select="Sequences/link">
            <xsl:call-template name="linkexpander">
                <xsl:with-param name="link" select="current()"/>
            </xsl:call-template>
        </xsl:for-each>
        </div>
        <!-- close any line level nodes still open -->
    </xsl:template>

    <xsl:template match="sequence" mode="dispatch">
        <!-- sequence dispatcher -->
        <xsl:variable name="type" select="Type/link/@value"/>

        <xsl:choose>
            <xsl:when test="$type = 'Analysis'"><xsl:apply-templates select="current()" mode="Analysis"/></xsl:when>
            <xsl:when test="$type = 'Text'"><!--xsl:apply-templates select="current()" mode="Text"/--></xsl:when>
            <xsl:when test="$type = 'TextPhysical'"><!--xsl:apply-templates select="current()" mode="TextPhysical"/--></xsl:when>
            <xsl:when test="$type = 'TextDivision'"><!--xsl:apply-templates select="current()" mode="TextDivision"/--></xsl:when>
            <xsl:when test="$type = 'LinePhysical'"><!--xsl:apply-templates select="current()" mode="LinePhysical"/--></xsl:when>
            <xsl:when test="$type = 'Paragraph'"><xsl:apply-templates select="current()" mode="Paragraph"/></xsl:when>
            <xsl:when test="$type = 'Verse'"><xsl:apply-templates select="current()" mode="Verse"/></xsl:when>
            <xsl:when test="$type = 'Stanza'"><xsl:apply-templates select="current()" mode="Verse"/></xsl:when>
            <xsl:when test="$type = 'Pāda'"><xsl:apply-templates select="current()" mode="Pāda"/></xsl:when>
            <xsl:otherwise><xsl:apply-templates select="current()" mode="textpart"/></xsl:otherwise>
        </xsl:choose>

        <!-- close any line level nodes still open -->
    </xsl:template>

    <xsl:template match="sequence" mode="Pāda">
        <!-- sequence entity representing the set of entities that make up a paragraph of the text for the given edition -->
        <!-- output Paragraph level node -->
        <xsl:variable name="headerValue" select="DisplayLabel"/>
        <xsl:variable name="nValue" select="SuperScript"/>
        <l>
            <xsl:attribute name="xml:id">
                <xsl:value-of select="concat('seq',@id)"/>
            </xsl:attribute>
            <xsl:if test="string-length($headerValue) > 0">
                <xsl:attribute name="met">
                    <xsl:value-of select="$headerValue"/>
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="string-length($nValue) > 0">
                <xsl:attribute name="n">
                    <xsl:value-of select="$nValue"/>
                </xsl:attribute>
            </xsl:if>

            <!-- process subcomponents of sequence -->
            <xsl:for-each select="Components/link">
                <xsl:call-template name="linkexpander">
                    <xsl:with-param name="link" select="current()"/>
                </xsl:call-template>
            </xsl:for-each>
            <!-- close any line level nodes still open -->
        </l>
    </xsl:template>

    <xsl:template match="sequence" mode="Verse">
        <!-- sequence entity representing the set of entities that make up a paragraph of the text for the given edition -->
        <!-- output Paragraph level node -->
        <xsl:variable name="headerValue" select="DisplayLabel"/>
        <xsl:variable name="nValue" select="SuperScript"/>
        <lg>
            <xsl:attribute name="xml:id">
                <xsl:value-of select="concat('seq',@id)"/>
            </xsl:attribute>
            <xsl:if test="string-length($headerValue) > 0">
                <xsl:attribute name="met">
                    <xsl:value-of select="$headerValue"/>
                </xsl:attribute>
            </xsl:if>
            <xsl:if test="string-length($nValue) > 0">
                <xsl:attribute name="n">
                    <xsl:value-of select="$nValue"/>
                </xsl:attribute>
            </xsl:if>

            <!-- process subcomponents of sequence -->
            <xsl:for-each select="Components/link">
                <xsl:call-template name="linkexpander">
                    <xsl:with-param name="link" select="current()"/>
                </xsl:call-template>
            </xsl:for-each>
            <!-- close any line level nodes still open -->
        </lg>
    </xsl:template>

    <xsl:template match="sequence" mode="Paragraph">
        <!-- sequence entity representing the set of entities that make up a paragraph of the text for the given edition -->
        <!-- output Paragraph level node -->
        <xsl:variable name="headerValue" select="DisplayLabel"/>
        <xsl:variable name="nValue" select="SuperScript"/>
        <!--xsl:if test="string-length($headerValue) > 0">
            <head><xsl:value-of select="$headerValue"/></head>
        </xsl:if-->
        <p >
            <xsl:attribute name="xml:id">
                <xsl:value-of select="concat('seq',@id)"/>
            </xsl:attribute>
            <xsl:if test="string-length($nValue) > 0">
                <xsl:attribute name="n">
                    <xsl:value-of select="$nValue"/>
                </xsl:attribute>
            </xsl:if>

            <!-- process subcomponents of sequence -->
            <xsl:for-each select="Components/link">
                <xsl:call-template name="linkexpander">
                    <xsl:with-param name="link" select="current()"/>
                </xsl:call-template>
            </xsl:for-each>
            <!-- close any line level nodes still open -->
        </p>
    </xsl:template>

    <xsl:template match="sequence" mode="Analysis">
        <!-- process subcomponents of sequence -->
        <xsl:for-each select="Components/link">
            <xsl:call-template name="linkexpander">
                <xsl:with-param name="link" select="current()"/>
            </xsl:call-template>
        </xsl:for-each>
        <!-- close any line level nodes still open -->
    </xsl:template>

    <xsl:template match="sequence" mode="textpart">
        <!-- sequence entity representing the set of entities that make up a structural component of the text for the given edition -->
        <!-- output Structural level nodes -->
        <xsl:variable name="headerValue" select="DisplayLabel"/>
        <xsl:variable name="nValue" select="SuperScript"/>
        <xsl:if test="count(Components/link) > 0">
          <ab type="textpart">
              <xsl:attribute name="xml:id">
                  <xsl:value-of select="concat('seq',@id)"/>
              </xsl:attribute>
              <xsl:attribute name="subtype">
                  <xsl:value-of select="Type/link/@value"/>
              </xsl:attribute>
              <xsl:if test="string-length($nValue) > 0">
                  <xsl:attribute name="n">
                      <xsl:value-of select="$nValue"/>
                  </xsl:attribute>
              </xsl:if>

              <!-- process subcomponents of sequence -->
              <xsl:for-each select="Components/link">
                  <xsl:call-template name="linkexpander">
                      <xsl:with-param name="link" select="current()"/>
                  </xsl:call-template>
              </xsl:for-each>
              <!-- close any line level nodes still open -->
          </ab>
        </xsl:if>
    </xsl:template>

    <xsl:template match="sequence" mode="line">
        <!-- sequence entity representing the set of word entities that can correlate to physical lines for the text of the given edition -->
        <!-- output word nodes with embedded lb - line breaks -->
            <!-- process subcomponents of token set -->
            <xsl:for-each select="Components/link">
                <xsl:choose>
                    <xsl:when test="current()/@entity = 'sequence'">
                        <xsl:apply-templates select="/rml/entities/sequence[@id = current()/@id]" mode="structure"/>
                    </xsl:when>
                    <xsl:when test="current()/@entity = 'compound'">
                        <!-- TODO add condition in here to id ///.../// for <gap reason="lost" extent="unknown"/>  -->
                        <xsl:apply-templates select="/rml/entities/compound[@id = current()/@id]" mode="physical"/>
                    </xsl:when>
                    <xsl:when test="current()/@entity = 'token'">
                        <xsl:variable name="tok" select="/rml/entities/token[@id = current()/@id]"/>
                        <xsl:choose>
                            <xsl:when test="$tok/DisplayValue = '+'">
                                <gap reason="lost" extent="1" unit="akṣara"/>
                            </xsl:when>
                            <xsl:when test="$tok/DisplayValue = '?'">
                                <gap reason="illegible" extent="1" unit="akṣara"/>
                            </xsl:when>
                            <xsl:when test="$tok/DisplayValue = '///'">
                                <xsl:if test="position()=1 or position()=last()"><gap reason="lost" extent="unknown"/></xsl:if>
                            </xsl:when>
                            <xsl:when test="$tok/DisplayValue = '...'">
                                <gap extent="unknown"/>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:apply-templates select="$tok" mode="physical"/>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:when>
                 </xsl:choose>
            </xsl:for-each>
            <!-- close any line level nodes still open -->
    </xsl:template>

    <xsl:template match="compound" mode="physical">
        <!-- output line level nodes -->
        <w>
            <xsl:attribute name="xml:id">
                <xsl:value-of select="concat('cmp',@id)"/>
            </xsl:attribute>
            <!-- process subcomponents of line -->
            <xsl:for-each select="CompoundComponents/link">
                <xsl:choose>
                    <xsl:when test="current()/@entity = 'compound'">
                        <xsl:apply-templates select="/rml/entities/compound[@id = current()/@id]" mode="physical"/>
                    </xsl:when>
                    <xsl:when test="current()/@entity = 'token'">
                        <xsl:variable name="tok" select="/rml/entities/token[@id = current()/@id]"/>
                        <xsl:choose>
                            <xsl:when test="$tok/DisplayValue = '?'">
                                <gap reason="illegible" extent="1" unit="akṣara"/>
                            </xsl:when>
                            <xsl:when test="$tok/DisplayValue = '+'">
                                <gap reason="lost" extent="1" unit="akṣara"/>
                            </xsl:when>
                            <xsl:when test="$tok/DisplayValue = '///'"/>
                            <xsl:when test="$tok/DisplayValue = '...'">
                                <gap extent="unknown"/>
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:apply-templates select="$tok" mode="physical"/>
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:when>
                </xsl:choose>
            </xsl:for-each>
            <!-- close any line level nodes still open -->
        </w>
    </xsl:template>

    <xsl:template match="token" mode="physical">
        <xsl:choose>
            <xsl:when test="DisplayValue = '‧'"><pc>‧</pc></xsl:when>
            <xsl:when test="DisplayValue = '×'"><pc>×</pc></xsl:when>
            <xsl:when test="DisplayValue = '∈'"><pc>∈</pc></xsl:when>
            <xsl:when test="DisplayValue = '⌇'"><pc>⌇</pc></xsl:when>
            <xsl:when test="DisplayValue = '○'"><pc>○</pc></xsl:when>
            <xsl:when test="DisplayValue = '◦'"><pc>◦</pc></xsl:when>
            <xsl:when test="DisplayValue = '•'"><pc>•</pc></xsl:when>
            <xsl:when test="DisplayValue = '·'"><pc>·</pc></xsl:when>
            <xsl:when test="DisplayValue = '∙'"><pc>∙</pc></xsl:when>
            <xsl:when test="DisplayValue = '☒'"><pc>☒</pc></xsl:when>
            <xsl:when test="DisplayValue = '☸'"><pc>☸</pc></xsl:when>
            <xsl:when test="DisplayValue = '❀'"><pc>❀</pc></xsl:when>
            <xsl:when test="DisplayValue = '❉'"><pc>❉</pc></xsl:when>
            <xsl:when test="DisplayValue = '–'"><pc>–</pc></xsl:when>
            <xsl:when test="DisplayValue = '—'"><pc>—</pc></xsl:when>
            <xsl:when test="DisplayValue = '|'"><pc>|</pc></xsl:when>
            <xsl:when test="DisplayValue = ':'"><pc>:</pc></xsl:when>
            <xsl:when test="DisplayValue = '+'">
                <gap reason="lost" extent="1" unit="akṣara"/>
            </xsl:when>
            <xsl:when test="DisplayValue = '?'">
                <gap reason="illegible" extent="1" unit="akṣara"/>
            </xsl:when>
            <xsl:when test="DisplayValue = '///'">
                <xsl:if test="position()=1 or position()=last()"><gap reason="lost" extent="unknown"/></xsl:if>
            </xsl:when>
            <xsl:when test="DisplayValue = '...'">
                <gap extent="unknown"/>
            </xsl:when>
            <xsl:otherwise>
        <w>
            <xsl:attribute name="xml:id">
                <xsl:value-of select="concat('tok',@id)"/>
            </xsl:attribute>
            <xsl:variable name="tokID" select="@id"/>
            <xsl:variable name="lbrNode" select="$LBs[tokID = $tokID]"/>
            <xsl:choose>
                <xsl:when test="$lbrNode/graID">
                    <xsl:variable name="transcr">
                        <xsl:call-template name="insertLBTCM">
                            <xsl:with-param name="graIDs" select="current()/Graphemes/link/@id"/>
                            <xsl:with-param name="graTrgID" select="$lbrNode/graID"/>
                            <xsl:with-param name="graIndex" select="1"/>
                            <xsl:with-param name="preString" select="''"/>
                            <xsl:with-param name="postString" select="Transcription"/>
                        </xsl:call-template>
                    </xsl:variable>
                    <!-- process TCM and missing -->
                    <xsl:variable name="transcr1">
                        <xsl:call-template name="replaceAll">
                            <xsl:with-param name="str" select="$transcr"/>
                            <xsl:with-param name="search" select="'ʔi'"/>
                            <xsl:with-param name="sub" select="'ï'"/>
                        </xsl:call-template>
                    </xsl:variable>
                    <xsl:variable name="transcr2">
                        <xsl:call-template name="replaceAll">
                            <xsl:with-param name="str" select="$transcr1"/>
                            <xsl:with-param name="search" select="'ʔu'"/>
                            <xsl:with-param name="sub" select="'ü'"/>
                        </xsl:call-template>
                    </xsl:variable>
                    <xsl:variable name="transcr3">
                        <xsl:call-template name="replaceAll">
                            <xsl:with-param name="str" select="$transcr2"/>
                            <xsl:with-param name="search" select="'ʔ'"/>
                            <xsl:with-param name="sub" select="''"/>
                        </xsl:call-template>
                    </xsl:variable>
                    <xsl:call-template name="convertTCM">
                        <!--xsl:with-param name="tcmString" select="replace(replace(replace($transcr,'ʔi','ï'),'ʔu','ü'),'ʔ','')"/-->
                        <xsl:with-param name="tcmString" select="$transcr3"/>
                        <xsl:with-param name="lbNode" select="$lbrNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:variable name="transcr1">
                        <xsl:call-template name="replaceAll">
                            <xsl:with-param name="str" select="Transcription"/>
                            <xsl:with-param name="search" select="'ʔi'"/>
                            <xsl:with-param name="sub" select="'ï'"/>
                        </xsl:call-template>
                    </xsl:variable>
                    <xsl:variable name="transcr2">
                        <xsl:call-template name="replaceAll">
                            <xsl:with-param name="str" select="$transcr1"/>
                            <xsl:with-param name="search" select="'ʔu'"/>
                            <xsl:with-param name="sub" select="'ü'"/>
                        </xsl:call-template>
                    </xsl:variable>
                    <xsl:variable name="transcr3">
                        <xsl:call-template name="replaceAll">
                            <xsl:with-param name="str" select="$transcr2"/>
                            <xsl:with-param name="search" select="'ʔ'"/>
                            <xsl:with-param name="sub" select="''"/>
                        </xsl:call-template>
                    </xsl:variable>
                    <!-- process TCM and missing -->
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="$transcr3"/>
                    </xsl:call-template>
                </xsl:otherwise>
            </xsl:choose>
            <!-- close any line level nodes still open -->
        </w>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="replaceAll">
        <xsl:param name="str" />
        <xsl:param name="search" />
        <xsl:param name="sub" />
        <xsl:choose>
            <xsl:when test="contains($str, $search)">
                <xsl:value-of select="substring-before($str, $search)"  disable-output-escaping="yes" />
                <xsl:value-of select="$sub"  disable-output-escaping="yes" />
                <xsl:call-template name="replaceAll">
                    <xsl:with-param name="str"
                        select="substring-after($str, $search)"/>
                    <xsl:with-param name="search" select="$search"/>
                    <xsl:with-param name="sub" select="$sub"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$str" disable-output-escaping="yes" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="insertLBTCM">
        <xsl:param name="graIDs"/>
        <xsl:param name="graIndex"/>
        <xsl:param name="graTrgID"/>
        <xsl:param name="preString"/>
        <xsl:param name="postString"/>
        <xsl:variable name="graStr" select="/rml/entities/grapheme[@id = $graIDs[$graIndex]]/Grapheme"></xsl:variable>
        <xsl:if test="$graIDs[$graIndex] = $graTrgID">
            <xsl:choose>
                <xsl:when test="starts-with($postString,$graStr) or $graIndex = 1">
                    <!-- return with  concat(pre,'=',post) -->
                    <xsl:value-of select="concat($preString, '=', $postString)"  disable-output-escaping="yes" />
                </xsl:when>
                <xsl:otherwise>
                    <!-- return with concat(pre, substring-before(post,graStr),'=',graStr, substring-after(post,graStr)) -->
                    <xsl:value-of select="concat($preString, substring-before($postString,$graStr),'=', $graStr,substring-after($postString,$graStr))"  disable-output-escaping="yes" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:if>
        <xsl:if test="$graIDs[$graIndex] != $graTrgID">
            <xsl:choose>
                <xsl:when test="starts-with($postString,$graStr)">
                    <!-- call template with graIndex++ pre = concat(pre, graStr); post = substring-after(post,graStr)-->
                    <xsl:call-template name="insertLBTCM">
                        <xsl:with-param name="graIDs" select="$graIDs"/>
                        <xsl:with-param name="graTrgID" select="$graTrgID"/>
                        <xsl:with-param name="graIndex" select="1 + $graIndex"/>
                        <xsl:with-param name="preString" select="concat($preString, $graStr)"/>
                        <xsl:with-param name="postString" select="substring-after($postString,$graStr)"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:otherwise>
                    <!-- call template with graIndex++  pre = concat(pre, substring-before(post,graStr),graStr); post = substring-after(post,graStr)-->
                    <xsl:call-template name="insertLBTCM">
                        <xsl:with-param name="graIDs" select="$graIDs"/>
                        <xsl:with-param name="graTrgID" select="$graTrgID"/>
                        <xsl:with-param name="graIndex" select="1 + $graIndex"/>
                        <xsl:with-param name="preString" select="concat($preString, substring-before($postString,$graStr),$graStr)"/>
                        <xsl:with-param name="postString" select="substring-after($postString,$graStr)"/>
                    </xsl:call-template>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:if>
    </xsl:template>

    <xsl:template name="convertTCM">
        <xsl:param name="tcmString"/>
        <xsl:param name="lbNode"/>
        <xsl:if test="string-length($tcmString) > 0">
            <xsl:choose>
                <xsl:when test="contains($tcmString,'.')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'.')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <gap reason="lost" unit="akṣarapart"/>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'.')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'_')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'_')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <gap reason="lost" unit="akṣarapart"/>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'_')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'◊')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'◊')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <space/>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'◊')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'⟪')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'⟪')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'⟪')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'⟫')">
                    <add>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'⟫')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    </add>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'⟫')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'{{')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'{{')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'{{')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'}}')">
                    <del>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'}}')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    </del>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'}}')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'{')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'{')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'{')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'}')">
                    <surplus>
                        <xsl:call-template name="convertTCM">
                            <xsl:with-param name="tcmString" select="substring-before($tcmString,'}')"/>
                            <xsl:with-param name="lbNode" select="$lbNode"/>
                        </xsl:call-template>
                    </surplus>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'}')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'[')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'[')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'[')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,']')">
                    <unclear>
                        <xsl:call-template name="convertTCM">
                            <xsl:with-param name="tcmString" select="substring-before($tcmString,']')"/>
                            <xsl:with-param name="lbNode" select="$lbNode"/>
                        </xsl:call-template>
                    </unclear>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,']')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'(*')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'(*')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'(*')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'(')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'(')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'(')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,')')">
                    <supplied reason="lost">
                        <xsl:call-template name="convertTCM">
                            <xsl:with-param name="tcmString" select="substring-before($tcmString,')')"/>
                            <xsl:with-param name="lbNode" select="$lbNode"/>
                        </xsl:call-template>
                    </supplied>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,')')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'⟨*')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'⟨*')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'⟨*')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'⟨')">
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-before($tcmString,'⟨')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'⟨')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'⟩')">
                    <supplied reason="omitted">
                        <xsl:call-template name="convertTCM">
                            <xsl:with-param name="tcmString" select="substring-before($tcmString,'⟩')"/>
                            <xsl:with-param name="lbNode" select="$lbNode"/>
                        </xsl:call-template>
                    </supplied>
                    <xsl:call-template name="convertTCM">
                        <xsl:with-param name="tcmString" select="substring-after($tcmString,'⟩')"/>
                        <xsl:with-param name="lbNode" select="$lbNode"/>
                    </xsl:call-template>
                </xsl:when>
                <xsl:when test="contains($tcmString,'=')">
                    <xsl:value-of select="substring-before($tcmString,'=')"  disable-output-escaping="yes" /><lb><xsl:attribute name="xml:id"><xsl:value-of select="concat('seq',$lbNode/seqID)"/></xsl:attribute><xsl:attribute name="n"><xsl:value-of select="$lbNode/@ord"/></xsl:attribute></lb><xsl:value-of select="substring-after($tcmString,'=')" disable-output-escaping="yes" />
                </xsl:when>
                <xsl:otherwise><xsl:value-of select="$tcmString"  disable-output-escaping="yes" /></xsl:otherwise>
            </xsl:choose>
        </xsl:if>
    </xsl:template>

    <xsl:template name="linkexpander">
        <xsl:param name="link"/>
        <xsl:variable name="id" select="$link/@id"/>
        <xsl:choose>
            <xsl:when test="$link/@entity = 'sequence'">
                <xsl:apply-templates select="/rml/entities/sequence[@id=$id]" mode="dispatch"/>
            </xsl:when>
            <xsl:when test="$link/@entity = 'compound'">
                <xsl:apply-templates select="/rml/entities/compound[@id=$id]" mode="physical"/>
            </xsl:when>
            <xsl:when test="$link/@entity = 'token'">
                <xsl:apply-templates select="/rml/entities/token[@id=$id]" mode="physical"/>
            </xsl:when>
        </xsl:choose>
    </xsl:template>
    <!--  /rml/entities/sequence[@id = /rml/entities/edition/sequences/link/@id and  type/link = 'physical']/Components/link   get physical lines physical edition of text-->
</xsl:stylesheet>