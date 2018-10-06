<?php
?>
    </div>
<?php
wp_footer();
?>
    <script>
     var youtube_el = document.querySelector('.video__iframe');
     if (youtube_el !== null) {
         var els = document.getElementsByClassName('video__iframe');
         var is_api_load = false;
         Array.prototype.forEach.call(els, function (el) {
                 el.onclick = function () {

                     if (is_api_load === false) {
                         var tag = document.createElement('script');
                         tag.src = "https://www.youtube.com/iframe_api";
                         var firstScriptTag = document.getElementsByTagName('body')[0];
                         firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                     }

                     function onPlayerReady(event) {
                         event.target.playVideo();
                     }

                     function get_get_player(el) {
                         new YT.Player(el, {
                             width: el.offsetWidth,
                             height: el.offsetHeight,
                             videoId: el.getAttribute('data-id'),
                             playerVars: {'autoplay': 1},
                             events: {
                                 'onReady': onPlayerReady,
                             }
                         });
                     }

                     if (is_api_load) {
                         get_get_player(el);
                     } else {
                         window.onYouTubeIframeAPIReady = function () {
                             get_get_player(el);
                         };
                     }
                     is_api_load = true;
                 };
             }
         );
     }
</script>
    </body>
    </html>
<?php
