(function ($) {
  $(document).ready(function () {
    // show toast start
    function showToast(config) {
      const { type, timeout, title } = config;

      const icon =
        type === "success"
          ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9 16.17l-3.88-3.88L4 13.41l5 5 10-10-1.41-1.42z"/></svg>'
          : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13 3h-2v10h2zm0 14h-2v2h2z"/></svg>';

      const toast = $(`
              <div class="toast ${type}">
                  <div class="header">
                      <span class="icon">${icon}</span>
                      <span>${title}</span>
                      <span class="close-btn">&times;</span>
                  </div>
                  <div class="progress-bar" style="animation-duration: ${timeout}ms"></div>
              </div>
          `);

      $("#toast-container").append(toast);

      // Remove toast on close button click
      toast.find(".close-btn").on("click", function () {
        toast.remove();
      });

      // Auto-remove toast after timeout
      setTimeout(() => {
        toast.remove();
      }, timeout);
    }
    // show toast end

    // tab start
    $(".tab").click(function () {
      // Remove active class from all tabs
      $(".tab").removeClass("active");
      // Add active class to the clicked tab
      $(this).addClass("active");

      // Hide all tab content
      $(".tab-content").hide();
      // Show the content of the clicked tab
      const tabId = $(this).data("tab");
      $("#" + tabId).fadeIn();
    });
    // tab end

    // copy to clipboard start
    $(".copy-button").on("click", function () {
      // Get the text of the endpoint from the same row
      const endpoint = $(this).closest("tr").find("td:first").text();

      // Create a temporary input element to hold the text
      const tempInput = $("<input>");
      $("body").append(tempInput);
      tempInput.val(endpoint).select();

      // Copy the text to clipboard
      document.execCommand("copy");

      // Remove the temporary input element
      tempInput.remove();

      // show toast message
      showToast({
        type: "success",
        timeout: 2000,
        title: "Copied to clipboard",
      });
    });
    // copy to clipboard end

    // save credentials start
    $("#save_credentials").on("click", function () {
      const api_url = $("#api_url").val();
      const api_key = $("#api_key").val();

      // add loading spinner
      const loader_button = $(".spinner-loader-wrapper");
      $(loader_button).addClass("loader-spinner");

      $.ajax({
        type: "POST",
        url: wpb_admin_localize.ajax_url,
        data: {
          action: "save_credentials",
          api_url: api_url,
          api_key: api_key,
        },
        success: function (response) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          if (true === response.success) {
            showToast({
              type: "success",
              timeout: 2000,
              title: `${response.data}`,
            });
          } else {
            showToast({
              type: "error",
              timeout: 2000,
              title: `${response.data}`,
            });
          }
        },
        error: function (xhr, status, error) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          showToast({
            type: "error",
            timeout: 2000,
            title: `${response.data}`,
          });
        },
      });
    });
    // save credentials end

    // save options start
    $("#save_options").on("click", function () {
      const option1 = $("#option1").val();
      const option2 = $("#option2").val();

      // add loading spinner
      const loader_button = $(".spinner-loader-wrapper");
      $(loader_button).addClass("loader-spinner");

      $.ajax({
        type: "POST",
        url: wpb_admin_localize.ajax_url,
        data: {
          action: "save_options",
          option1: option1,
          option2: option2,
        },
        success: function (response) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          if (true === response.success) {
            showToast({
              type: "success",
              timeout: 2000,
              title: `${response.data}`,
            });
          } else {
            showToast({
              type: "error",
              timeout: 2000,
              title: `${response.data}`,
            });
          }
        },
        error: function (xhr, status, error) {
          // remove loading spinner
          $(loader_button).removeClass("loader-spinner");

          showToast({
            type: "error",
            timeout: 2000,
            title: `${response.data}`,
          });
        },
      });
    });
    // save options end
  });
})(jQuery);
