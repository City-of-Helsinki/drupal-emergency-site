(function ($) {
  Drupal.behaviors.cookieConsent = {
    attach: function (context, settings) {
      const prePreferencesLoadHandler = function (response) {

        // Add social feed iframes if consent for cookies was given.
        if (response.currentStatus && context.cookie.includes('preference')) {
          let iFrame = document.getElementsByTagName('iframe');
          for (let i = 0; i < iFrame.length; i++) {
            let srcValue = iFrame[i].getAttribute('data-cookie-compliance-src')
            iFrame[i].setAttribute('src', srcValue)
            let currentParent = iFrame[i].parentNode
            let socialMediaWrapper = document.createElement('div')
            socialMediaWrapper.className = 'social-media-wrapper'
            currentParent.appendChild(socialMediaWrapper)
            socialMediaWrapper.appendChild(iFrame[i])
          }
        }
        else {
          let iFrame = document.getElementsByTagName('iframe');
          for (let i = 0; i < iFrame.length; i++) {
            let iframeDocument = iFrame[i].contentDocument || iFrame[i].contentWindow.document
            console.log(context)
            let divElement = iframeDocument.createElement("div")
            divElement.innerHTML = Drupal.t("Unable to load the content of this external frame because of your cookie settings." +
              " <br>Please accept or adjust your preferences for our cookie policy in order to view the content.");
            iframeDocument.body.appendChild(divElement);
          }
        }
      };

      Drupal.eu_cookie_compliance('prePreferencesLoad', prePreferencesLoadHandler);
    }
  };
})(jQuery);
