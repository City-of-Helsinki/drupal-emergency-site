(function ($) {
  Drupal.behaviors.cookieIframeConsent = {
    attach: function (context, settings) {
      const prePreferencesLoadHandler = function (response) {

        // Add social feed iframes if consent for cookies was given.
        let iFrames = document.querySelectorAll('iframe[data-cookie-compliance-src]');
        let cookieAccepted = response.currentStatus && context.cookie.includes('preference');
          iFrames.forEach(function (iframe) {
          let currentParent = iframe.parentNode
          let socialMediaWrapper = document.createElement('div')
          socialMediaWrapper.className = 'social-media-iframe-wrapper'
          currentParent.appendChild(socialMediaWrapper)
          socialMediaWrapper.appendChild(iframe);

          if (cookieAccepted) {
            let srcValue = iframe.getAttribute('data-cookie-compliance-src')
            iframe.setAttribute('src', srcValue)
            iframe.removeAttribute('data-cookie-compliance-src');
          }
          else {
            let iframeDocument = iframe.contentDocument || iframe.contentWindow.document
            let divElement = iframeDocument.createElement("div")
            divElement.innerHTML = Drupal.t("Unable to load the content of this external frame because of your cookie settings." +
              " <br>Please accept or adjust your preferences for our cookie policy in order to view the content.");
            iframeDocument.body.appendChild(divElement);
          }
        });
      };

      Drupal.eu_cookie_compliance('prePreferencesLoad', prePreferencesLoadHandler);
    }
  };
})(jQuery);
