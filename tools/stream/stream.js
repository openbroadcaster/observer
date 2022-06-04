(function() {

var videoPlayer = [];

function vs2()
{
    var vs2 = document.createElement("script");
    vs2.type = "text/javascript";
    vs2.src = obstream.url+'tools/stream/videojs/flash/videojs-flash.js';
    vs2.onload = vs3;
    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(vs2);
}

function vs3()
{
    var vs3 = document.createElement("script");
    vs3.type = "text/javascript";
    vs3.src = obstream.url+'tools/stream/videojs/flash/videojs-flashls-source-handler.js';
    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(vs3);
}

function css()
{
  $('head').append('<link rel="stylesheet" href="'+obstream.url+'tools/stream/stream.css" type="text/css" />'); 
}

function main() 
{
  // make sure we have some settings
  if(typeof(obstream)!='object' || typeof(obstream.url)!='string') return;
  if(obstream.url.charAt( obstream.url.length-1 )!='/') obstream.url = obstream.url + '/';

  // add videojs if we don't have it already
  if(typeof(videojs)=='undefined')
  {
    $('body').append('<link href="'+obstream.url+'tools/stream/videojs/core/video-js.min.css" rel="stylesheet">');

    var vs1 = document.createElement("script");
    vs1.type = "text/javascript";
    vs1.src = obstream.url+'tools/stream/videojs/core/video.min.js';
    vs1.onload = vs2;
    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(vs1);
  }

  $('.ob-stream-widget').before('<div class="ob-stream-widget-loading">Loading media...</div>');
  $('.ob-stream-widget').hide();
 
  $('.ob-stream-widget').each(function(widgetIndex,widget)
  {
    // set unique index for this widget
    $(widget).attr('data-index',widgetIndex);
  
    // add base html
    $(widget).append('<!-- Font Awesome Icons Used. See License: '+obstream.url+'tools/stream/fontawesome/LICENSE.txt -->');
    $(widget).append('<div class="ob-stream-widget-metadata"><div class="ob-stream-widget-metadata-inside"><div class="ob-stream-widget-metadata-close"><span>Close</span><img src="'+obstream.url+'tools/stream/fontawesome/times.svg"></div><div class="ob-stream-widget-metadata-content"></div></div></div>');
    $(widget).append('<div class="ob-stream-widget-player"></div>');
    $(widget).append('<div class="ob-stream-widget-modal"><div class="ob-stream-widget-modal-left"><img src="'+obstream.url+'tools/stream/fontawesome/arrow-left-white.svg"></div><div class="ob-stream-widget-modal-right"><img src="'+obstream.url+'tools/stream/fontawesome/arrow-right-white.svg"></div><div class="ob-stream-widget-modal-close"><img src="'+obstream.url+'tools/stream/fontawesome/times-white.svg"><span>Close</span></div></div>');
    $(widget).append('<div class="ob-stream-widget-filters">\
      <select class="ob-stream-widget-filter-genre"><option value="">All</option></select>\
      <input class="ob-stream-widget-filter-name" placeholder="Search" type="text">\
    </div>');
    $(widget).append('<div class="ob-stream-widget-items"></div>');
    $(widget).append('<div class="ob-stream-widget-more"><button>Load More</button></div>');
    $(widget).find('.ob-stream-widget-more button').click(function() { load(widget) });
    
    load(widget);
    
    // livescroll
    if($(widget).attr('data-more')=='auto')
    {
      $(window).scroll(function()
      { 
        // early return if loading not needed or already in progress
        if(!$(widget).find('.ob-stream-widget-more').is(':visible')) return;
      
        var threshold = $(widget).find('.ob-stream-widget-items').offset().top + $(widget).find('.ob-stream-widget-items').height() - 150;
        var current = $(window).scrollTop() + $(window).height();
        
        if(current > threshold)
        {
          load(widget)
        }
      });
    }
  });
  
  // escape to hide modal window
  $(document).keyup(function(event) { 
    if(event.which == 27)
    {
      closeModal();
      closeMetadata();
    }
    else if(event.which == 37)
    {
      modalLeft();
    }
    else if(event.which == 39)
    {
      modalRight();
    }
  }); 
  
  $('.ob-stream-widget .ob-stream-widget-modal-close').on('click', closeModal);
  $('.ob-stream-widget .ob-stream-widget-modal-left').on('click', modalLeft);
  $('.ob-stream-widget .ob-stream-widget-modal-right').on('click', modalRight);
  $('.ob-stream-widget-metadata-close').on('click', closeMetadata);
}

function load(widget)
{
  // prepare get data
  var media_id = $(widget).attr('data-media');
  var genre_id = $(widget).attr('data-genre');
  var category_id = $(widget).attr('data-category');

  if(genre_id) var data = {'genre_id': genre_id};
  else if(category_id) var data = {'category_id': category_id};
  else if(media_id) var data = {'media_id': media_id};
  else return true;

  // handle limit/page
  var limit = Math.max(0,$(widget).attr('data-limit'));
  var offset = Math.max(0,$(widget).attr('data-offset'));

  if(!limit) limit = 0;
  if(!offset) offset = 0;

  if(limit > 0)
  {
    data.limit = limit;
    data.offset = offset;

    // set new offset for next request
    $(widget).attr('data-offset',limit + offset);

    // hide loadmore (might re-show or not)
    $(widget).find('.ob-stream-widget-more').hide();
  }
    
  // get our media and add
  $.get(obstream.url+'tools/stream/api.php', data, function(response)
  {
    var genres = response.genres;
    var media = response.media;
    var media_total = response.media_total;

    // sort media items
    if(obstream.itemSortBy)
    {
      media.sort(function(a,b)
      {
        if(a[obstream.itemSortBy]) var aName = a[obstream.itemSortBy].toLowerCase();
        else var aName = '';

        if(b[obstream.itemSortBy]) var bName = b[obstream.itemSortBy].toLowerCase(); 
        else var bName = '';

        if(obstream.itemSortDir=='asc') return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
        else return ((aName < bName) ? 1 : ((aName > bName) ? -1 : 0));
      });
    }

    $('.ob-stream-widget-loading').remove();
    $('.ob-stream-widget').show();

    $.each(genres, function(index,genre)
    {
      $(widget).find('.ob-stream-widget-filter-genre').append( $('<option></option>').text(genre.name).attr('value',genre.id) );
    });
    $(widget).find('.ob-stream-widget-filter-genre').change(listFilter);
    $(widget).find('.ob-stream-widget-filter-name').keyup(listFilter);

    $.each(media, function(index,item)
    {
      var $item = $('<div class="ob-stream-widget-item"></div>');

      if(obstream.itemDisplay=='artist') var text = item.artist;
      else if(obstream.itemDisplay=='title') var text = item.title;
      else var text = item.artist+' - '+item.title;

      $item.append( $('<span class="ob-stream-widget-item-name"></span>').text(text) );
      $item.attr('data-id',item.id);
      $item.attr('data-type',item.type);
      $item.attr('data-mime',item.mime);
      $item.attr('data-thumbnail',item.thumbnail);
      $item.attr('data-stream',item.stream);
      $item.attr('data-download',item.download);
      $item.attr('data-genre',item.genre_id);
      if(item.captions) $item.attr('data-captions',item.captions);

      if(item.type=='audio') var play = 'listen';
      else if(item.type=='video') var play = 'watch';
      else var play = 'view';

      // thumbnail
      var $thumb = $($('<span class="ob-stream-widget-item-thumb"></span>'));
      if(item.thumbnail) $thumb.append($('<img />').attr('src',obstream.url+item.thumbnail));
      else if(item.type=='audio') $thumb.append($('<img />').attr('src',obstream.url+'/tools/stream/audio.png'));
      $item.append(' ').append($thumb);
      $thumb.click(itemLoad);

      if(item.stream)
      {
        var $play = $( $('<span class="ob-stream-widget-item-play"></span>').text(play) );
        $item.append(' ').append($play);
        $play.click(itemLoad);
        $play.attr('role','button');
      }

      if(item.download)
      {
        var $download = $('<span class="ob-stream-widget-item-download">download</span>');
        $item.append(' ').append($download);
        $download.click(itemDownload);
        $download.attr('role','button');
      }
      
      if($(widget).attr('data-metadata'))
      {
        var $metadata = $('<span class="ob-stream-widget-item-metadata">info</span>');
        $item.append(' ').append($metadata);
        $metadata.click(itemMetadata);
        $metadata.attr('role','button');
      }

      /*
      if(item.captions)
      {
        var $captions = $('<span class="ob-stream-widget-item-captions">captions</span>');
        $item.append(' ').append($captions);
        $captions.click(itemCaptions);
        $captions.attr('role','button');
      }
      */

      $(widget).find('.ob-stream-widget-player').click(itemLoadSingle);
      $(widget).find('.ob-stream-widget-items').append($item);
      
      // store raw metadata for later use
      $item.data('metadata',item);
    });

    // play item if single media and it's an image. otherwise set thumbnail with click player box to play.
    if(media_id)
    {
      if($(widget).find('.ob-stream-widget-item[data-type=image] .ob-stream-widget-item-play').length)
      {
        $(widget).find('.ob-stream-widget-item[data-type=image] .ob-stream-widget-item-play').click();
      }
      else if(media[0].thumbnail)
      {
        $(widget).find('.ob-stream-widget-player').css('background-image','url('+obstream.url + media[0].thumbnail+')');
      }
    }

    // add "load more" if link or auto      
    var more = $(widget).attr('data-more');
    if(parseInt($(widget).attr('data-offset'))<parseInt(media_total) && (more=='link' || more=='auto'))
    {
      $(widget).find('.ob-stream-widget-more').show();
    }

    // if(typeof(obstream.callback)=='function') obstream.callback(widget, response);

  },'json');
}

function closeModal()
{
  var $player = $('.ob-stream-widget[data-modal-player] .ob-stream-widget-player:visible')
  if($player.length)
  {
    var $widget = $player.parents('.ob-stream-widget');
    var widgetIndex = $widget.attr('data-index');
    var hasVideoJS = Boolean($widget.find('.video-js').length);
    $player.hide();
    if(videoPlayer[widgetIndex] && hasVideoJS) videoPlayer[widgetIndex].dispose();
  }
  $('.ob-stream-widget-modal').hide();
}

function modalLeft()
{
  var $widget = $('.ob-stream-widget[data-modal-player] .ob-stream-widget-player:visible').parents('.ob-stream-widget').first();
  var currentId = $widget.attr('data-playing');
  if(!currentId) return;
  
  var $prev = $widget.find('.ob-stream-widget-item[data-id='+currentId+']').prevAll('[data-stream]').first();
  if(!$prev.length) $prev = $widget.find('.ob-stream-widget-item[data-stream]').last();
  
  $prev.find('.ob-stream-widget-item-play').click();
}

function modalRight()
{
  var $widget = $('.ob-stream-widget[data-modal-player] .ob-stream-widget-player:visible').parents('.ob-stream-widget').first();
  var currentId = $widget.attr('data-playing');
  if(!currentId) return;
  
  var $next = $widget.find('.ob-stream-widget-item[data-id='+currentId+']').nextAll('[data-stream]').first();
  if(!$next.length) $next = $widget.find('.ob-stream-widget-item[data-stream]').first();
  
  $next.find('.ob-stream-widget-item-play').click();
}

function listFilter()
{
  var $widget = $(this).parents('.ob-stream-widget');
  var genre_id = $widget.find('.ob-stream-widget-filter-genre').val();
  var search =  $widget.find('.ob-stream-widget-filter-name').val().trim().toUpperCase();

  if(!genre_id) { $widget.find('.ob-stream-widget-item').show(); }
  else
  {
    $widget.find('.ob-stream-widget-item').hide();
    $widget.find('.ob-stream-widget-item[data-genre='+genre_id+']').show();
  }
  
  if(search!=='')
  {
    $widget.find('.ob-stream-widget-item:visible').each(function(index, item)
    {
      if(!$(item).find('.ob-stream-widget-item-name').text().toUpperCase().includes(search)) $(item).hide();
    });
  }
  
  if($widget.find('.ob-stream-widget-item:visible').length) $widget.find('.ob-stream-widget-items').removeClass('empty');
  else $widget.find('.ob-stream-widget-items').addClass('empty');
}

function itemLoadSingle()
{
  var $widget = $(this).parents('.ob-stream-widget');
  if($widget.attr('data-media')===undefined) return;
  if($(this).html()=='') $widget.find('.ob-stream-widget-item-play').click();
}

function itemLoad()
{  
  var $widget = $(this).parents('.ob-stream-widget');
  
  var type = $(this).parent().attr('data-type');
  var mediaId = $(this).parent().attr('data-id');
  var mime = $(this).parent().attr('data-mime');
  var url = $(this).parent().attr('data-stream');
  var captions = $(this).parent().attr('data-captions');
  var widgetIndex = $widget.attr('data-index');
  var hasVideoJS = Boolean($widget.find('.video-js').length);
  var thumbnail = $(this).parent().attr('data-thumbnail');
  
  // keep track of which item we're playing so we can use next/prev
  $widget.attr('data-playing',mediaId);
  
  // properly destroy our video player for this widget if we have it
  if(videoPlayer[widgetIndex] && hasVideoJS) videoPlayer[widgetIndex].dispose();
  
  if(type=='video' || type=='audio')
  {
    var captions_html = '';
    if(captions) captions_html = '<track kind="captions" src="'+obstream.url+captions+'" srclang="en" label="English" default>';
  
    var $videojs = $('<video-js id="ob-stream-widget-player-video-'+widgetIndex+'" class="vjs-default-skin" controls crossorigin="anonymous" preload="auto" width="640" height="268">\
      <source src="'+obstream.url+url+'" type="'+mime+'">\
      '+captions_html+'\
    </video-js>');
    
    if(thumbnail) $videojs.attr('poster',obstream.url+thumbnail);
    
    $widget.find('.ob-stream-widget-player').html($videojs);
  
    videoPlayer[widgetIndex] = videojs('ob-stream-widget-player-video-'+widgetIndex, {
      flash: {
        swf: obstream.url+'/tools/stream/videojs/flash/video-js.swf'
      }
    });

    videoPlayer[widgetIndex].play();
  }
  
  else if(type=='image')
  {
    $widget.find('.ob-stream-widget-player').html('<img src="'+obstream.url+url+'">');
  }

  if($widget.attr('data-modal-player')===undefined) $('html,body').scrollTop($widget.find('.ob-stream-widget-player').offset().top - 10 );
  else { $widget.find('.ob-stream-widget-player').css('display','flex'); $widget.find('.ob-stream-widget-modal').show(); }
}

function itemDownload()
{
  var media_id = $(this).parent().attr('data-id');
  window.location.href = obstream.url+'download.php?media_id='+media_id;
}

function itemMetadata()
{
  var $widget = $(this).parents('.ob-stream-widget').first();  
  var metadata = JSON.parse($widget.attr('data-metadata'));
  var source = $(this).parents('.ob-stream-widget-item').first().data('metadata');
  
  $widget.find('.ob-stream-widget-metadata-content').empty();

  $.each(metadata, function(name, index)
  {
    var $item = $('<div class="ob-stream-widget-metadata-item"></div>');
    $item.append( $('<div></div>').text(name) );
    $item.append( $('<div></div>').text(source[index]) );
    $widget.find('.ob-stream-widget-metadata-content').append($item);
  });
  
  $widget.find('.ob-stream-widget-metadata').css('display','flex');
}

function closeMetadata()
{
  $('.ob-stream-widget-metadata').hide();
}

function itemCaptions()
{
  window.location.href = obstream.url+$(this).parent().attr('data-captions');
}

// http://alexmarandon.com/articles/web_widget_jquery/
var jQuery;
var $;
if (window.jQuery === undefined || window.jQuery.fn.jquery !== '3.3.1') 
{
  var script_tag = document.createElement('script');
  script_tag.setAttribute("type","text/javascript");
  script_tag.setAttribute("src","https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js");
  script_tag.onload = scriptLoadHandler;
  (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);
}
else 
{
  $ = jQuery = window.jQuery;
  main();
}
function scriptLoadHandler() 
{
  $ = jQuery = window.jQuery.noConflict(true);
  main(); 
  css();
}

})();
