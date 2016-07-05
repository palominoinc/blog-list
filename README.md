# blog-list
List of blog items kept in an editable table view

1. After importing the extension, reload the WebPal UI.
2. Go to **routes** and change the url to the ones on your website
3. Go to **AjaxLoadWeb.php** located under source and change the hardcoded website name stored in variable *$this->web* defined in function *__construct* to your website name
4. If you were inserting a blog node in a page that was not named "blog", go to BlogController.php and change the blog name stored in variable *$this->bloglistXPath* and *$this->blogXPath* defined in function *__construct*
