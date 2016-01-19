<?php

/* NewsController.php
 *
 * Acts as a JSON route to fetch news items
 * Allows filtering of the news items by the following request parameters:
 * - category: all news items matching the given category will be returned
 * - year: all news items matching the given year will be returned
 * - tag: all news items matching the given tag will be returned
 * Also manages the pagination by the following request parameters:
 * - page: the page we want items for
 * - type (optional): the type of filter we are applying to the items
 * - value (optional): the value for the filter we are applying to the items
 */

namespace Bloglist\Controllers;

use Controller;
use Bloglist\Source\AjaxLoadWeb;
use DateTime;

function can_iterate($obj) {
  return is_array($obj) || is_object($obj);
}
function sort_by_date_attr($a, $b) {
  $a_date = new DateTime($a->getAttribute("date"));
  $b_date = new DateTime($b->getAttribute("date"));
  return $a_date < $b_date;
}

class BlogController extends Controller
{
  function __construct() {
    $this->bloglistXPath = "//page[@name='blog']/bloglist";
    $this->blogXPath = "{$this->bloglistXPath}/blog";
    $this->web = new AjaxLoadWeb();
    //TODO: This would benefit greatly from a hash table
  }
  /*queryWeb()
      Essentially just a shorthand to AjaxLoadWeb's queryWeb
   */
  public function queryWeb($xpath) {
    //TODO: Add hash table functionality to this to speed consecutive queries
    //Although I'm not sure of the lifespan of this NewsController, and if
    //it makes sense to do so or not
    return $this->web->queryWeb($xpath);
  }
  /* grabRequestParameters()
    This function checks the $_REQUEST object for any of the params we are
    looking for:
    - page: the page for which we want the news items from
    - type: the type of filter we want to apply
    - value: the value of the filter our items must match
    This function takes care of sanitizing the user input and making sure
    that our params are of the proper type if they are supplied
  */
  public function grabRequestParameters() {
    $params = array();
    //get page
    if (isset($_REQUEST['page'])) {
      $page = intval($_REQUEST['page']);
      //If for some reason the client has 1000 pages of blog posts,
      //then remove this limit or set the number higher
      if ($page > 0 && $page < 1000) {
        $params['page'] = $page;
      }
    }
    //get type
    if (isset($_REQUEST['type'])) {
      $type = trim($_REQUEST['type']);
      //TODO: Any additional sanitization required?
      $params['type'] = $type;
    }
    //only get value if type was supplied
    if (isset($params['type']) && isset($_REQUEST['value'])) {
      $value = trim($_REQUEST['value']);
      //TODO: Any additional sanitization required?
      $params['value'] = $value;
    }
    //if either type or value are missing, then unset them both
    if (!isset($params['type']) || !isset($params['value'])) {
      unset($params['type']);
      unset($params['value']);
    }
    return $params;
  }
  /*getNews_JSON()
      Returns a list of news items in JSON format 
  
  */
  public function getBlogAsJSON() {
    $reqParams = array();
    $reqParams = $this->grabRequestParameters();
    //TODO: Retrieve page from Request parameters
    if (isset($reqParams['page']) && $reqParams['page'] > 0
         && $reqParams['page'] < 1000) {
      $page = $reqParams['page'];
    } else {
      $page = 1;
    }
    //TODO: Retrieve items from max-items attribute in the node
    $itemsPerPage = 10;
    //TODO: Retrieve type (filter type) from Request parameters
    if (isset($reqParams['type']) && isset($reqParams['value'])) {
      $filterType = $reqParams['type'];
      $filterValue = $reqParams['value'];
    }
    //TODO: Retrieve value (fiter value) from Request parameters
    
    $result = array();
    $result["blog"] = array();
    
    if (isset($filterType) && isset($filterValue)) {
      $element_node_list = $this->getFilteredBlogItems($filterType, $filterValue);
    } else {
      $element_node_list = $this->getBlogItems();  
    }
    $elements = iterator_to_array($element_node_list);
    if (!can_iterate($elements)) {
      //return $result;
    }
    usort($elements, 'Bloglist\Controllers\sort_by_date_attr');
    
    $firstItem = ($page-1) * $itemsPerPage;
    $lastItem = $firstItem + $itemsPerPage;
    //We start at -1 because we increase the counter as soon as we enter the loop
    $position = -1;
    $numItems = count($elements);
    foreach ($elements as $element) {
      //If this element is archived, skip it, and decrease our item count by 1
      if ($this->blogIsArchived($element)) {
        $numItems--;
        continue;
      }
      
      $position++;
      if ($position < $firstItem) {
        continue;
      } else if ($position >= $lastItem) {
        break;
      }
      $blog_item = array();

      // Get the name
      $name = $element->getAttribute("name");
      
      // Get the title
      $title = $this->getBlogItemTitle($name);

      // Get the date
      $date = new DateTime($element->getAttribute("date"));

      // Get the categories
      $main_category = $this->getBlogItemMainCategory($name);
      $categories = $this->getBlogItemCategories($name);
      $tags = $this->getBlogItemTags($name);

      
      // Get the text
      $synopsis = $this->getBlogItemSynopsis($name);

      
      // Get the image source
      $img_url = $this->getBlogItemImageURL($name);

      // Get the image date
      $date = strtotime($this->getBlogItemDate($name));
      $day = date('d', $date);
      $month = date('M', $date);
      $year = date('Y', $date);

      
      $blog_item = array();
      $blog_item['name'] = $name;
      $blog_item['title'] = $title;
      $blog_item['main_category'] = $main_category;
      $blog_item['categories'] = $categories;
      $blog_item['tags'] = $tags;
      $blog_item['synopsis'] = $synopsis;
      $blog_item['imageSrc'] = $img_url;
      $blog_item['date'] = array();
      $blog_item['date']['day'] = $day;
      $blog_item['date']['month'] = $month;
      $blog_item['date']['year'] = $year;
      $blog_item['archived'] = $element->getAttribute("archived");
      array_push($result["blog"], $blog_item);
    }
    //Determine how many items and pages there are
    $numPages = ceil($numItems / $itemsPerPage);
    
    $result["total"] = $numItems;
    $result["id"] = $this->getBloglistID();
    $result["current_page"] = $page;
    //Since $firstItem is an index for an array it starts at 0, so add 1
    $result["first_item"] = $firstItem + 1;
    $result["last_item"] = $lastItem;
    $result["num_pages"] = $numPages;
    
    //echo "type: {$filterType} value: {$filterValue}";
    return json_encode($result);
  }
  private function getBloglistID() {
    $node_list = $this->queryWeb($this->bloglistXPath);
    $id = "";
    if (can_iterate($node_list)) {
      foreach ($node_list as $bloglist) {
        $id = $bloglist->getAttribute("id");
      }
    }
    return $id;
  }
  private function getBlogItems() {
    return $this->queryWeb($this->blogXPath);
  }
  /* getFilteredNewsItems
    Returns all of the news items that match the specified filter.
    Parameters:
    $type: the type of filter we are making
    $value: the value of the filter our items must match
   */
  private function getFilteredBlogItems($type, $value) {
    //$value = strtolower(strval($value));
    if ($type == "year") {
      //TODO: Filter by year
      $value = strtolower(strval($value));
      $xpath = $this->blogXPath."[contains(@date, '{$value}')]";
    } else if ($type == "category") {
      $value = strtolower(strval($value));
      $xpath = $this->blogXPath."[translate(categories/category,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$value}' or translate(categories/main-category,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$value}']";
//       $xpath = $this->newsXPath."[categories/category='{$value}']";
    } else if ($type == "tag") {
      //$xpath = $this->newsXPath."[translate(tags/tag,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$value}']";
      $xpath = $this->blogXPath."[tags/tag='{$value}']";

    } else {
      $xpath = $this->blogXPath;
    }
    return $this->queryWeb($xpath);
  }
  /* Given the name of a news item, return an array
    with its main category */
  private function getBlogItemMainCategory($name) {
    $categories_nodes = $this->queryWeb("{$this->blogXPath}[@name='{$name}']/categories/main-category");
    $categories = array();
    if (can_iterate($categories_nodes)) {
        foreach ($categories_nodes as $category) {
          array_push($categories, $category->nodeValue);
        }
      }
    return $categories;
  }
  /* Given the name of a news item, return an array
    of its categories */
  private function getBlogItemCategories($name) {
    $categories_nodes = $this->queryWeb("{$this->blogXPath}[@name='{$name}']/categories/category");
    $categories = array();
    if (can_iterate($categories_nodes)) {
        foreach ($categories_nodes as $category) {
          array_push($categories, $category->nodeValue);
        }
      }
    return $categories;
  }
  /* Given the name of a news item, return an array
    of its tags */
  private function getBlogItemTags($name) {
    $tag_nodes = $this->queryWeb("{$this->blogXPath}[@name='{$name}']/tags/tag");
    $tags = array();
    if (is_array($tag_nodes) || is_object($tag_nodes)) {
        foreach ($tag_nodes as $tag) {
          array_push($tags, $tag->nodeValue);
        }
      }
    return $tags;
  }
  /* Given the name of a news item, return a string
    containing the first 300 characters of its text */
  private function getBlogItemSynopsis($name) {
    $node_list = $this->queryWeb("{$this->blogXPath}[@name='{$name}']/text");
    $text = "";
    if (can_iterate($node_list)) {
    foreach ($node_list as $text_node) {
      $text = $text_node->nodeValue;
      break;
    }
    }
    return substr($text, 0, 300)."...";
  }
  /* Given the name of a news item, return a string
    representing the URL to its image
    The format is the following:
    ?f={src}&resize=330x210
    */
  private function getBlogItemImageURL($name) {
    $node_list = $this->queryWeb("{$this->blogXPath}[@name='{$name}']/image");
    if (can_iterate($node_list)) {
      foreach($node_list as $image_node) {
        $imageURL = $image_node->getAttribute("src");        
        break;
      }
    }
    if (!isset($imageURL)) {
      $imageURL="4098761476254413";
    }

    return "?f={$imageURL}&resize=330x210";
  }
  /* Given the name of a news item, return the date
    of that news item */
  private function getBlogItemDate($name) {
    $node_list = $this->queryWeb("{$this->blogXPath}[@name='{$name}']");
    $date = "";
    if (can_iterate($node_list)) {
      foreach($node_list as $blog_node) {
        $date = $blog_node->getAttribute("date");
        break;
      }
    }
    return $date;
  }
  /* Given the name of a news item, return the title
      of that news item */
  private function getBlogItemTitle($name) {
    $node_list = $this->queryWeb("{$this->blogXPath}[@name='{$name}']/title");
    $title = "";
    if (can_iterate($node_list)) {
      foreach($node_list as $title) {
        $title = $title->nodeValue;
        break;
      }
    }
    return $title;
  }
  /* Given a news item, determine if it is archived
    or not by investigating its archived attribute */
  private function blogIsArchived($blogItem) {
    $isArchived = $blogItem->getAttribute("archived");
    if (isset($isArchived) && $isArchived == "yes") {
      return true;
    } else {
      return false;
    }
  }
}
