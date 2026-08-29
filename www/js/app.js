;(function () {
  'use strict'

  // Trivial Mithril component proving the no-build frontend is wired (CODE-77 scaffold).
  // Real board/list/task components arrive in CODE-81/82.
  var count = 0

  var Demo = {
    view: function () {
      return m('div', { class: 'space-y-3' }, [
        m('p', { class: 'text-sm text-gray-500' }, 'Mithril is wired. Clicks: ' + count),
        m('button', { class: 'btn', onclick: function () { count++ } }, 'Click me'),
      ])
    },
  }

  var root = document.getElementById('ts-demo')
  if (root) m.mount(root, Demo)
})()
