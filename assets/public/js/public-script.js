(function ($) {
  $(document).ready(function () {
    // Lead generation form start
    $(".wpcf7-submit").on("click", function (e) {
      e.preventDefault(); // Prevent default form submission

      // Get the form data
      const form = $(this).closest("form");

      // Serialize the form data into an array
      let formData = form.serializeArray();

      // Initialize variables
      let firstName = '';
      let lastName = '';
      let email = '';
      let phone = '';
      let city = '';
      let country = '';

      // Loop through the serialized array to extract data
      formData.forEach(function (field) {
        switch (field.name) {
          case "first-name":
            firstName = field.value;
            break;
          case "last-name":
            lastName = field.value;
            break;
          case "email":
            email = field.value;
            break;
          case "phone":
            phone = field.value;
            break;
          case "city":
            city = field.value;
            break;
          case "country":
            country = field.value;
            break;
        }
      });

      // Make the AJAX request
      $.ajax({
        type: "POST",
        url: wpb_public_localize.ajax_url,
        data: {
          action: "lead_generation",
          nonce: wpb_public_localize.nonce,
          first_name: firstName,
          last_name: lastName,
          email: email,
          phone: phone,
          city: city,
          country: country,
        },
        success: function (response) {
          console.log("Success:", response);
        },
        error: function (xhr, status, error) {
          console.log("Error:", error);
        },
      });
    });
    // Lead generation form end
  });
})(jQuery);
