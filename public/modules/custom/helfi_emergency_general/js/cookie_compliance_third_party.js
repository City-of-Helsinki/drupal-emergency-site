(function ($) {
  Drupal.behaviors.cookieConsent = {
    attach: function (context, settings) {
      const prePreferencesLoadHandler = function (response) {
        let facebookIframe;
        let twitterIframe;
        // Add social feed iframes if consent for cookies was given.
        if (response.currentStatus && context.cookie.includes('preference')) {
          const facebookIframeWrapper =  document.getElementsByClassName('social-facebook')[0];
          const twitterIframeWrapper = document.getElementsByClassName('social-twitter')[0];
          if (facebookIframeWrapper) {
            facebookIframe = document.createElement('iframe');
            facebookIframe.classList.add('social-media-iframe');
            facebookIframe.src = 'https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Fhelsinginkaupunki&tabs=timeline&width=540&height=500&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId'
            facebookIframe.width = '800';
            facebookIframe.height = '1200';
            facebookIframe.allowFullscreen = true;
            facebookIframe.allow = 'encrypted-media';
            facebookIframeWrapper.appendChild(facebookIframe);
          }
          if (twitterIframeWrapper) {
            twitterIframe = document.createElement('iframe')
            twitterIframe.classList.add('social-media-iframe');
            twitterIframe.src = 'https://syndication.twitter.com/srv/timeline-profile/screen-name/helsinki?dnt=false&amp;embedId=twitter-widget-0&amp;features=...'; // Replace with your Twitter iframe URL
            twitterIframe.width = '800 ';
            twitterIframe.height = '1200';
            twitterIframe.allowFullscreen = true;
            twitterIframeWrapper.appendChild(twitterIframe);
          }
        }
      };

      Drupal.eu_cookie_compliance('prePreferencesLoad', prePreferencesLoadHandler);
    }
  };
})(jQuery);
