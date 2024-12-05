(function ($) {
  $(document).ready(function () {
    // lead generation form start
    $(".wpcf7-submit").on("click", function (e) {
      // get form data
      const form = $(this).closest("form");
      // serialize array the form data
      let formData = form.serializeArray();

      $.ajax({
        type: "POST",
        url: wpb_public_localize.ajax_url,
        data: {
          action: "lead_generation",
          nonce: wpb_public_localize.nonce,
          formData: formData,
        },
        success: function (response) {
          console.log(response);
        },
        error: function (xhr, status, error) {
          console.log(error);
        },
      });
    });
    // lead generation form end
  });
})(jQuery);
