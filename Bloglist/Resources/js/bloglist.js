$(document).ready(function() {
  //Add the event listener to the newslist
  //and add it to the sidebar
  var bloglist,
    sidebar,
    current_page,
    num_pages,
    button,
    filterRegex,
    hash,
    hashValues,
    filterType,
    filterValue,
    yearValue;
  bloglist = document.querySelector('[data-bloglist-id]');
  sidebar = document.querySelector('.blog-sidebar');
  bloglist.addEventListener('click', filterBlogItems);
  sidebar.addEventListener('click', filterBlogItems);
  //We need to give the pagination its buttons
  //The number of pages is available in the first page's data-num-pages attr.
  current_page = bloglist.querySelector('[data-num-pages]');
  $(current_page).addClass('active');
  num_pages = $(current_page).data('num-pages');
  for (var i = 2; i <= num_pages; i++) {
    button = $(current_page)
      .clone()
      .removeClass('active');
    button.data('page-num', i);
    button.data('num-pages', '');
    button.text(i);
    insertAfter(button[0], current_page);
    current_page = button[0];
  }

  //And then we check the url. If there is a #tag=value, #category=value, or #year=value,
  //then we will initiate a filter
  filterRegex = /^#(tag|category|year)=(.*)$/;
  hashValues = filterRegex.exec(window.location.hash);
  if (hashValues && hashValues.length > 2) {
    filterType = hashValues[1];
    filterValue = hashValues[2];
    console.log(filterType, filterValue);
    switch (filterType) {
      case 'tag':
      //Filtering by tag is the same as category, let category case handle it
      case 'category':
        filterPosts(filterType, filterValue);
        break;
      case 'year':
        yearValue = parseInt(filterValue);
        if (yearValue < 1990 || yearValue > 2030) {
          //No articles before or after these dates
          //These are just some safe boundaries
          return;
        }
        filterPosts(filterType, yearValue);
        break;
      default:
        break;
    }
  }
});
function insertAfter(newNode, referenceNode) {
  referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}
/*jel(): jQuery Element
       *
       *return a jquery object with a new element created from the supplied tag name
       */
function jel(tagName) {
  var el = document.createElement(tagName);
  return $(el);
}
function filterBlogItems(e) {
  var $target, MAX_LEVELS, current_level;
  $target = $(e.target);
  //We check MAX_LEVELS amount of parent elements before
  //we give up and decide they didn't click one of our
  //target elements
  MAX_LEVELS = 2;
  current_level = 0;
  while (current_level < MAX_LEVELS) {
    //If this element has the data-filter-type attr,
    //then we should filter the news items
    if ($target.data('filter-type')) {
      var type, value;
      type = $target.data('filter-type');
      value = $target.data('filter-value');
      console.log('filter these posts!!');
      //Call our filter function
      filterPosts(type, value);
      return;
    } else if ($target.data('page-num')) {
      //If this element has the data-page-num attr,
      //then we should display the news items for that page
      var pageNum, type, value;
      pageNum = $target.data('page-num');
      if ($target.data('filter-type') && $target.data('filter-value')) {
        type = $target.data('filter-type');
        value = $target.data('filter-value');
        console.log(
          'Displaying blog items with [' +
            type +
            '=' +
            value +
            '] on page ' +
            pageNum
        );
        //Call the pagination function
        goToPage(pageNum, type, value);
      } else {
        console.log('Displaying all blog items on page ' + pageNum);
        //Call the pagination function
        goToPage(pageNum);
      }
      return;
    } else {
      $target = $target.parent();
      current_level++;
      if ($target.length < 1) {
        //no more parents to check, give up.
        return;
      }
    }
  }
  //We went up until MAX_LEVELS, just return
  return;
}
/* filterPosts
 * makes an ajax call to our json route asking for all
 * news items where they have the same value for the type supplied.
* parameters:
  @type: tag, category, year
  @value: a string that we are to filter all posts by
*/
function filterPosts(type, value) {
  var url, data, success, $bloglist;
  $bloglist = $('[data-bloglist-id]');
  $bloglist.data('filter-type', type).data('filter-value', value);
  url = window.location.pathname;
  data = {
    type: type,
    value: value,
  };
  success = function(data) {
    updateBlog(JSON.parse(data));
  };
  $.post(url, data, success);
}
/* goToPage
 * makes an ajax call to our json route asking for all
 * news items on the specified page.
 * parameters:
   @pageNum: An integer -- 1 <= pageNum
   @type: optional -- used when changing pages for filtered posts
   @value: optional -- used when changing pages for filtered posts
 */
function goToPage(pageNum, type, value) {
  var url, data, success, $bloglist;
  $bloglist = $('[data-bloglist-id]');
  url = window.location.pathname;
  if ($bloglist.data('filter-type') && $bloglist.data('filter-value')) {
    data = {
      page: pageNum,
      type: $bloglist.data('filter-type'),
      value: $bloglist.data('filter-value'),
    };
  } else if (type && value) {
    data = {
      page: pageNum,
      type: type,
      value: value,
    };
  } else {
    data = {
      page: pageNum,
    };
  }
  success = function(data) {
    updateBlog(JSON.parse(data));
  };
  $.post(url, data, success);
}
function updateBlog(newBlog) {
  var blog = newBlog.blog;
  var article,
    header,
    title,
    titleLink,
    meta,
    metaLeft,
    categorySpan,
    categoryList,
    metaRight,
    tagIcon,
    tagList,
    entry_content,
    entry_row,
    image_container,
    figure,
    image_link,
    blogDateInfo,
    blogMonth,
    blogDay,
    blogYear,
    image,
    summary_container,
    blog_summary,
    summary_text,
    read_more,
    read_more_link,
    container,
    grid,
    gridSizer,
    gridItem,
    gridContent,
    col12,
    metaDate;
  if (blog.length > 0) {
    container = $("div[data-bloglist-id='" + newBlog.id + "']");
    container.html('');
    grid = jel('div')
      .addClass('grid')
      .appendTo(container);
    gridSizer = jel('div')
      .addClass('grid-sizer col-sm-6')
      .appendTo(grid);
  }
  console.log(blog);
  for (var i = 0; i < blog.length; i++) {
    //if (i > 9) {console.log("whwhwhww");return;}
    var blogItem = blog[i];
    //For each article

    gridItem = jel('div').addClass('grid-item col-sm-6');
    gridContent = jel('div')
      .addClass('grid-item-content col-sm-12')
      .appendTo(gridItem);
    article = jel('article')
      .addClass('blogPost')
      .appendTo(gridContent);

    figure = jel('figure')
      .addClass('blog-image row')
      .appendTo(article);

    col12 = jel('div')
      .addClass('col-sm-12')
      .appendTo(article);
    header = jel('div')
      .addClass('blogheader')
      .appendTo(col12);

    title = jel('h2').appendTo(header);
    titleLink = jel('a')
      .attr('href', window.location.pathname + '?node=' + blogItem.name)
      .text(blogItem.title)
      .appendTo(title);

    meta = jel('div')
      .addClass('meta-line clearfix')
      .appendTo(header);

    metaDate = jel('div')
      .addClass('date-row')
      .appendTo(meta);

    dateI = jel('i')
      .addClass('fa fa-calendar')
      .attr('style', 'padding-right: 5px; margin-bottom:10px;')
      .appendTo(metaDate);

    jel('span')
      .text(
        blogItem.date.day +
          ' ' +
          blogItem.date.month +
          ' ' +
          blogItem.date.year +
          ' | '
      )
      .appendTo(metaDate);

    userI = jel('i')
      .addClass('fa fa-user')
      .attr('style', 'padding-right: 5px; margin-bottom:10px;')
      .appendTo(metaDate);

    jel('span')
      .text(blogItem.author)
      .appendTo(metaDate);

    metaLeft = jel('div')
      .addClass('row category-row')
      .appendTo(meta);

    categorySpan = jel('span')
      .addClass('meta-category')
      .text('In:')
      .appendTo(metaLeft);
    categoryList = jel('ul').appendTo(categorySpan);
    //display any main categories this item may have
    for (var j = 0; j < blogItem.main_category.length; j++) {
      var category, category_el, categorylink;
      category = blogItem.main_category[j];
      category_el = jel('li')
        .addClass('main-category')
        .appendTo(categoryList);
      category_link = jel('a')
        .attr('href', window.location.pathname + '#category=' + category)
        .data('filter-type', 'category')
        .data('filter-value', category)
        .text(category)
        .appendTo(category_el);
    }
    //display any categories this item may have
    for (var j = 0; j < blogItem.categories.length; j++) {
      var category, category_el, categorylink;
      category = blogItem.categories[j];
      category_el = jel('li')
        .addClass('category')
        .appendTo(categoryList);
      category_link = jel('a')
        .attr('href', window.location.pathname + '#category=' + category)
        .data('filter-type', 'category')
        .data('filter-value', category)
        .text(category)
        .appendTo(category_el);
    }
    //If the item doesn't have any categories at all, display an indicator
    if (blogItem.categories.length < 1 && blogItem.main_category.length < 1) {
      var placeholder;
      placeholder = jel('i')
        .text('None')
        .appendTo(categoryList);
    }
    //display the item's tags
    metaRight = jel('div')
      .addClass('row tag-row')
      .appendTo(meta);
    tagList = jel('ul')
      .addClass('meta-tags')
      .appendTo(metaRight);
    tagIcon = jel('i')
      .addClass('fa fa-tags')
      .appendTo(tagList);
    for (var j = 0; j < blogItem.tags.length; j++) {
      var tag, tag_el, tag_link;
      tag = blogItem.tags[j];
      tag_el = jel('li')
        .addClass('tag')
        .appendTo(tagList);
      tag_link = jel('a')
        .attr('href', window.location.pathname + '#tag=' + tag)
        .data('filter-type', 'tag')
        .data('filter-value', tag)
        .text(tag)
        .appendTo(tag_el);
    }
    if (blogItem.tags.length < 1) {
      emptyTagText = jel('i')
        .text('None')
        .appendTo(tagList);
    }

    entry_content = jel('div')
      .addClass('entry-content')
      .appendTo(col12);

    entry_row = jel('div')
      .addClass('row')
      .appendTo(entry_content);

    //     image_container = jel("div").addClass("col-md-5").appendTo(entry_row);
    //     figure = jel("figure").addClass("blog-image row").appendTo(image_container);

    image_link = jel('a')
      .attr('href', window.location.pathname + '?node=' + blogItem.name)
      .appendTo(figure);
    //     blogDateInfo = jel("div").addClass("blog-date-info").appendTo(image_link);
    //     blogMonth = jel("span").addClass("blog-month").text(blogItem.date.month).appendTo(blogDateInfo);
    //     blogDay = jel("span").addClass("blog-day").text(blogItem.date.day).appendTo(blogDateInfo);
    //     blogYear = jel("span").addClass("blog-year").text(blogItem.date.year).appendTo(blogDateInfo);
    image = jel('img')
      .attr('src', blogItem.imageSrc)
      .attr('class', 'img-responsive')
      .appendTo(image_link);

    //     summary_container = jel("div").addClass("col-md-7").appendTo(entry_row);
    summary_container = jel('div')
      .addClass('col-sm-12')
      .appendTo(entry_row);
    blog_summary = jel('div')
      .addClass('blog-summary')
      .appendTo(summary_container);
    summary_text = jel('p')
      .text(blogItem.synopsis)
      .appendTo(blog_summary);
    read_more = jel('p')
      .addClass('read-more')
      .appendTo(blog_summary);
    read_more_link = jel('a')
      .attr('href', window.location.pathname + '?node=' + blogItem.name)
      .text('Read More')
      .appendTo(read_more);

    grid.append(gridItem);
  }
  //TODO: Add pagination
  pagination = jel('div')
    .addClass('pagination')
    .appendTo(container);
  //If this is the first page, display a disabled previous button
  if (newBlog.current_page <= 1) {
    prevButton = jel('button').addClass('prev page btn disabled');
  } else {
    prevButton = jel('button')
      .addClass('prev page btn')
      .data('page-num', newBlog.current_page - 1);
  }
  prevButton.html('&lt;').appendTo(pagination);
  //Display each of the page buttons
  for (var j = 1; j <= newBlog.num_pages; j++) {
    var page_button = jel('button')
      .addClass('page btn')
      .data('page-num', j)
      .text(j)
      .appendTo(pagination);
    if (j == newBlog.current_page) {
      page_button.addClass('active');
    }
  }
  //If this is the last page, display a disabled next button
  if (newBlog.current_page >= newBlog.num_pages) {
    nextButton = jel('button').addClass('next page btn disabled');
  } else {
    nextButton = jel('button')
      .addClass('next page btn')
      .data('page-num', newBlog.current_page + 1);
  }
  nextButton.html('&gt;').appendTo(pagination);
  console.log(container);
  //If there are filters applied, then add a button to "show all posts"
  if (container.data('filter-type') && container.data('filter-value')) {
    var removeFilterButton, removeIcon, removeText;
    removeFilterButton = jel('button')
      .addClass('btn')
      .on('click', function() {
        container.data('filter-type', '').data('filter-value', '');
        //Now that no filters are set, we can go to the
        //first page of all the posts by calling goToPage
        goToPage(1);
        //And then we remove the filter from the url
        window.location.hash = '';
      })
      .appendTo(pagination);
    removeIcon = jel('i')
      .addClass('fa fa-times')
      .appendTo(removeFilterButton);
    removeText = jel('span')
      .text(
        container.data('filter-type') + ': ' + container.data('filter-value')
      )
      .appendTo(removeFilterButton);
  }

  $grid = $('.grid');
  triggerMasonry();

  // trigger masonry when images have loaded
  $('.grid')
    .imagesLoaded()
    .done(function() {
      triggerMasonry();
    });

  window.scrollTo(0, 400);
}

function triggerMasonry() {
  // don't proceed if $grid has not been selected
  if (!$grid) {
    return;
  }
  // init Masonry
  $('.grid').masonry({
    itemSelector: '.grid-item', // use a separate class for itemSelector, other than .col-
    columnWidth: '.grid-sizer',
    percentPosition: true,
  });
}
