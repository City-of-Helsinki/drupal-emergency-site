(function ($) {
  Drupal.behaviors.cookieResetButton = {
    attach: function (context, settings) {
      once('cookieSubmitButtons', '.eu-cookie-compliance-block-form', context).forEach(
        function (form) {
          let saveButton = form.querySelector('input.save');
          let acceptAllButton = form.querySelector('input.accept');
          saveButton.addEventListener("click", function(e) {
            e.preventDefault();
            let selectedCategories = ['essential'];
            let selectedCategInputs = context.querySelectorAll('input.categories:checked');
            if (selectedCategInputs) {
              selectedCategInputs.forEach(function (categ) {
                selectedCategories.push(categ.value);
              });
            }
            Drupal.eu_cookie_compliance.setAcceptedCategories(selectedCategories);
            Drupal.eu_cookie_compliance.setStatus("2");
            Drupal.eu_cookie_compliance.setVersion(drupalSettings.eu_cookie_compliance.cookie_policy_version);
            window.location.reload();
          });

          acceptAllButton.addEventListener("click", function (e) {
            e.preventDefault();
            let allCategories = drupalSettings.eu_cookie_compliance.cookie_categories;
            Drupal.eu_cookie_compliance.setAcceptedCategories(allCategories);
            Drupal.eu_cookie_compliance.setStatus("2");
            Drupal.eu_cookie_compliance.setVersion(drupalSettings.eu_cookie_compliance.cookie_policy_version);
            window.location.reload();
          });
        }
      );
    }
  };
})(jQuery, drupalSettings);
