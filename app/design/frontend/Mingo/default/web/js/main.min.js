function slugify(string) {
    const a = 'àáäâãåèéëêìíïîòóöôùúüûñçßÿœæŕśńṕẃǵǹḿǘẍźḧ·/_,:;'
    const b = 'aaaaaaeeeeiiiioooouuuuncsyoarsnpwgnmuxzh------'
    const p = new RegExp(a.split('').join('|'), 'g')
    return string.toString().toLowerCase()
        .replace(/\s+/g, '-')                       // Replace spaces with
        .replace(p, c => b.charAt(a.indexOf(c)))    // Replace special characters
        .replace(/&/g, '-and-')                     // Replace & with ‘and’
        .replace(/[^\w\-]+/g, '')                   // Remove all non-word characters
        .replace(/\-\-+/g, '-')                     // Replace multiple — with single -
        .replace(/^-+/, '')                         // Trim — from start of text
        .replace(/-+$/, '')                         // Trim — from end of text
}

require(['jquery'], function ($) {
    $(document).ready(function () {
        $.fn.extend({
          animateCss: function(animationName, callback) {
            var animationEnd = (function(el) {
              var animations = {
                animation: 'animationend',
                OAnimation: 'oAnimationEnd',
                MozAnimation: 'mozAnimationEnd',
                WebkitAnimation: 'webkitAnimationEnd',
              };

              for (var t in animations) {
                if (el.style[t] !== undefined) {
                  return animations[t];
                }
              }
            })(document.createElement('div'));

            this.addClass('animated ' + animationName).one(animationEnd, function() {
              $(this).removeClass('animated ' + animationName);

              if (typeof callback === 'function') callback();
            });

            return this;
          },
        });
    });
});
