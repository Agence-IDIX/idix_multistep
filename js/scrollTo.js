(function ($) {

  $.fn.scrollTo = function (id, delay) {
    var target = document.getElementById(id);
    delay = delay || 50;

    if (target) {
      var timer = setTimeout(function () {
        clearTimeout(timer);
        var bodyRect = document.body.getBoundingClientRect(),
            targetRect = target.getBoundingClientRect(),
            top = targetRect.top - bodyRect.top;

        window.scrollTo({
          top: top,
          left: 0,
          behavior: 'smooth'
        });
      }, delay);
    }
  }

})(jQuery);
