const mix = require('laravel-mix')

require('./nova.mix')

mix
  .setPublicPath('dist')
  .js('resources/js/tool.js', 'js')
  .vue({
    version: 3,
    options: {
      compilerOptions: {
        isCustomElement: (tag) => tag.startsWith('trix-'),
      },
    },
  })
  .css('resources/css/tool.css', 'css')
  .nova('opscale-co/nova-comments')
