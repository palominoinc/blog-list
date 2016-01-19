<?php

namespace Bloglist\Source;

use DOMDocument;
use XSLTProcessor;
use DOMXPath;
use WebPal;

function getAttributesAsAssocArray( $xmlElement ){
  $assocArray = array();
  foreach( $xmlElement->attributes as $attribute ){
    $assocArray[ $attribute->nodeName ] = $attribute->nodeValue;
  }
  return $assocArray;
}

function findChildByName( $element, $name ){
  foreach( $element->childNodes as $child ){
    if( $child->nodeName === $name ){
      return $child;
    }
  }
  return null;
}

function buildDelimitedStringFromElementAttributes( $elements, $firstAttributeName, $secondAttributeName = null ){
  $delimitedString = '';

  foreach( $elements as $index => $element ){
    $attributes = getAttributesAsAssocArray( $element );
    $delimitedString .= $attributes[$firstAttributeName];

    if( $secondAttributeName !== null ){
      $delimitedString .= "=".$attributes[$secondAttributeName];
    } 

    if( $index != ($elements->length - 1) ){
      $delimitedString .= ',';
    }
  }

  return $delimitedString;
}

function buildArrayFromElementAttributes( $elements, $firstAttributeName, $assocAttributeName = null ){
  $arr = array();

  foreach( $elements as $index => $element ){
    $attributes = getAttributesAsAssocArray( $element );

    if( $assocAttributeName == null ){

      $attrValue = $attributes[$firstAttributeName];
      if( is_null( $attrValue ) ){
        $attrValue = "";
      }
      $arr[] = $attrValue;
    } else{
      $attrValue = $attributes[$firstAttributeName];
      $attrKey = $attributes[$assocAttributeName];

      if( is_null( $attrValue ) ){
        $attrValue = '';
      }
      if( is_null( $attrKey ) ){
        $attrKey = '';
      }

      //$arr[ $attributes[$assocAttributeName] ] = $attributes[$firstAttributeName];
      $arr[ $attrKey ] = $attrValue;
    }
  }
  return $arr;
}


class AjaxLoadWeb
{
  private $web;
  private $lang;
  private $root;
  private $webRoot;
  private $fileRoot;
  private $webrun;
  private $themeRoot;
  private $sourceWebFile;
  private $localizedWebXPath;

/**
* This file is essentially a stripped down version of web.php.
* It is guarenteed that web.php will be run before this class is even
* called. Considering this, it can be guarenteed that the session
* variable will be set before.
*/
  function __construct(){
  //For some reason $_SESSION variables are not set
  //Currently hardcoding values. I'll mark TODO for any hardcoded value
  
  //TODO: This is hardcoded
  //$this->web = $_SESSION['V']["WEB"];
    $this->web = "palomino-site";
  //TODO: This is hardcoded
  //$this->lang = $_SESSION['V']["LANG"];
    $this->lang = "EN";
  
    if( isset($_SERVER['DOCUMENT_ROOT']) ){
      $this->root = $_SERVER['DOCUMENT_ROOT'];
      $this->webRoot = "{$this->root}";
      $this->fileRoot = "{$this->webRoot}/web/files";
      $this->webrun = "{$this->root}/web_dist";
      $this->themeRoot = "{$this->webRoot}/web/theme";
      if (WebPal::isLive()) {
        $this->sourceWebFile = "{$this->webRoot}/web/web.xml";
      } else {
        $this->sourceWebFile = "{$this->webRoot}/web/web/web.xml";
      }
      
    }
    if ( !isset($this->sourceWebFile) || !file_exists($this->sourceWebFile)) {
      // Indicates that we are running in preview mode
      // Files are not in duplicate, but pulled straight from repository
      if (! empty($_SESSION['DOCUMENT_MANAGER_ROOT'])) {
          $this->root = $_SESSION['DOCUMENT_MANAGER_ROOT'];
      } else {
          $this->root = "{$_SERVER['DOCUMENT_ROOT']}/_files";
      }

      //Not sure what this is being used for, commenting it out for now
      /*
      if (empty($_SESSION['xsig_val']) && file_exists('login.php')) {
          // Not logged in
          header("Location: index");
          exit;
      }
      */
    //For some reason all the $_SESSION variables are not set.
    //Temporarily modifying this if statement to ignore these values
    /*  
    if (empty($this->web) || (empty($_SESSION['xsig_val']))) {
          $error = "No web specified";
      }
      */
      if (empty($this->web)) {
          $error = "No web specified";
      } else {
          $this->webRoot = "{$this->root}/_webs/{$this->web}";
          
          if (!file_exists($this->webRoot)) {
            $error = "Web root folder does not exist";
          }
          else {
              $this->live = false;
              $this->fileRoot = "{$this->root}/_webs/{$this->web}/files";
              $this->pluginRoot = "{$_SERVER['DOCUMENT_ROOT']}/private/plugins/dm/webTree/live";
              $this->webrun = "{$this->pluginRoot}/web_dist";
              $this->themeRoot = "{$this->webRoot}/theme";
            //TODO: This is hardcoded.
            // the cache web file that is edited live
            //$this->sourceWebFile = "{$_SESSION['CMS_WEB_CACHE_DIRECTORY']}/{$this->web}/web.xml";
            $this->sourceWebFile = "{$this->root}/._web_cache/{$this->web}/web.xml";
          }
      }
    }
  //Time to load the proper web
    $sourceDoc = new DOMDocument( '1.0', 'UTF-8' );
    $sourceDoc->loadXML( file_get_contents($this->sourceWebFile) );
  
    $xslMatchLang = new DOMDocument( '1.0', 'UTF-8' );
    $xslMatchLang->loadXML( file_get_contents("{$this->webrun}/xsl/matchlang.xsl") );

    $procMatchLang = new XSLTProcessor();
    $procMatchLang->importStylesheet( $xslMatchLang );
    $procMatchLang->setParameter('', "LANG", $this->lang);
    $theLocalizedWeb = $procMatchLang->transformToDoc($sourceDoc);

    $this->localizedWebXPath = new DOMXPath($theLocalizedWeb);
  }

  public function queryWeb( $xPathQuery, $contextDomNode = null ){
    if( $contextDomNode == null ){
      return $this->localizedWebXPath->query( $xPathQuery );
    } else{
      return $this->localizedWebXPath->query( $xPathQuery, $contextDomNode );
    }
  }
}

?>