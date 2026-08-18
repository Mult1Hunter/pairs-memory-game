/* Settings screen helpers: colour pickers, theme presets, media picker,
   provider-dependent rows. */
(function ($) {
  "use strict";
  $(function () {
    var cfg = window.PairsMGAdmin || {};

    $(".pmg-color").wpColorPicker({
      change: function () {
        // Any manual colour edit means the preset no longer applies.
        var $theme = $(".pmg-theme");
        if ($theme.length && $theme.val() !== "custom") {
          $theme.data("silent", true).val("custom");
        }
      }
    });

    $(".pmg-theme").on("change", function () {
      var preset = cfg.presets && cfg.presets[$(this).val()];
      if (!preset) return;
      Object.keys(preset).forEach(function (key) {
        var $input = $('.pmg-color[data-key="' + key + '"]');
        if ($input.length) $input.wpColorPicker("color", preset[key]);
      });
      // wpColorPicker's change handler above just flipped us to custom;
      // put the chosen preset back.
      $(this).val(Object.keys(cfg.presets).filter(function (k) { return cfg.presets[k] === preset; })[0]);
    });

    function toggleProviderRows() {
      var p = $(".pmg-provider").val();
      $(".pmg-v3-only").toggle(p === "recaptcha_v3");
      var needsKeys = p && p !== "none";
      $("#pmg_captcha_site_key, #pmg_captcha_secret_key").closest("tr").toggle(!!needsKeys);
      $('input[name$="[captcha_test_mode]"]').closest("tr").toggle(!!needsKeys);
    }
    if ($(".pmg-provider").length) {
      $(".pmg-provider").on("change", toggleProviderRows);
      toggleProviderRows();
    }

    var frame = null;
    $("#pmg_pick_back").on("click", function (e) {
      e.preventDefault();
      if (!window.wp || !wp.media) return;
      if (!frame) {
        frame = wp.media({
          title: cfg.chooseImage || "Choose image",
          button: { text: cfg.useImage || "Use this image" },
          multiple: false,
          library: { type: "image" }
        });
        frame.on("select", function () {
          var att = frame.state().get("selection").first().toJSON();
          var url = (att.sizes && att.sizes.medium && att.sizes.medium.url) || att.url;
          $("#pmg_card_back_image_id").val(att.id);
          $("#pmg_back_preview").html($("<img>", { src: url, alt: "" }));
          $("#pmg_clear_back").show();
        });
      }
      frame.open();
    });
    $("#pmg_clear_back").on("click", function (e) {
      e.preventDefault();
      $("#pmg_card_back_image_id").val(0);
      $("#pmg_back_preview").empty();
      $(this).hide();
    });
  });
})(jQuery);
