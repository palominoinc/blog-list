function setMetaImg(src){
  var metaString = '<meta property="og:image" content="/_resources/' + src + '?_dm_id=&resize=1000"/>';
  $('head').append(metaString);
} 
